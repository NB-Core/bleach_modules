<?php

declare(strict_types=1);

use Lotgd\Forms;
use Lotgd\Http;

/**
 * Zanpakutou Training (Bleach)
 *
 * The path of the blade for Shinigami and Arrancar:
 * - a forest event in which the zanpakutou reveals its name while sparring
 *   with a master (the Arrancar forge their Resurrección),
 * - the master's lessons on the training grounds: Shikai/Resurrección at
 *   power level 5, Bankai at power level 10,
 * - Urahara's Bankai training below the lodge for power levels 5 to 9
 *   (Shinigami only).
 *
 * The power level rises by one per dragon kill up to 5.
 */

require_once 'modules/zanpakutou/lib/zanpakutou.php';
require_once 'modules/zanpakutou/lib/masters.php';

const ZANPAKUTOU_TRAINING_TURNS = 10;
const ZANPAKUTOU_TRAINING_BANKAI_GOLD = 870;
const ZANPAKUTOU_TRAINING_BANKAI_GEMS = 75;
const ZANPAKUTOU_TRAINING_BANKAI_CHANCE = 3;

function zanpakutou_training_getmoduleinfo(): array
{
    return [
        'name' => 'Zanpakutou Training (Bleach)',
        'version' => '2.0',
        'author' => '`2Oliver Brendel',
        'category' => 'Zanpakutou',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'requires' => [
            'specialtysystem' => '1.03|Specialty System by Oliver Brendel (NB-Core/modules)',
            'zanpakutou' => '2.0|Zanpakutou (Bleach) by Oliver Brendel',
            'training_bleach' => '2.0|Training Grounds (Bleach) by Oliver Brendel',
        ],
        'settings' => [
            'Zanpakutou Training Settings,title',
            'awakeningdk' => 'Dragon kills needed before the zanpakutou awakens,int|20',
            'bankaidk' => 'Dragon kills needed for the Bankai training,int|25',
        ],
        'prefs' => [
            'Preferences Zanpakutou Training,title',
            'master' => 'Id of the master,int',
            'special' => 'Master from the special list?,bool',
            'awakened' => 'Has the zanpakutou started to awaken?,bool',
            'awakened_counter' => 'Fight rounds towards the awakening,int',
        ],
    ];
}

function zanpakutou_training_install(): bool
{
    module_addhook('traininggrounds');
    module_addhook('arrancargrounds');
    module_addhook('dragonkill');
    module_addhook('lodge-desc');
    module_addeventhook('forest', "require_once 'modules/zanpakutou_training.php'; return zanpakutou_training_eventchance();");

    return true;
}

function zanpakutou_training_uninstall(): bool
{
    return true;
}

/**
 * Whether the current player can awaken a zanpakutou in the forest.
 */
function zanpakutou_training_can_awaken(): bool
{
    global $session;

    return bleach_zanpakutou_path() !== ''
        && $session['user']['dragonkills'] >= (int) get_module_setting('awakeningdk', 'zanpakutou_training')
        && zanpakutou_load()['name'] === '';
}

/**
 * Forest event chance: only for players whose blade is still silent.
 */
function zanpakutou_training_eventchance(): int
{
    return zanpakutou_training_can_awaken() ? 100 : 0;
}

/**
 * Texts and places that differ between the Shinigami and the Arrancar path.
 */
function zanpakutou_training_path_info(string $path): array
{
    if ($path === 'arrancar') {
        return [
            'title' => 'Training Grounds - Resurrección',
            'grounds' => 'runmodule.php?module=training_bleach',
            'image' => 'modules/zanpakutou/training2.jpg',
            'intro' => 'This place looks almost deserted - except for the signs of decay and destruction that hover over the entire area. It is used for special training indeed.',
            'who' => ['Nnoitra', 'Starrk', 'Baraggan', 'Cirucci (^^)', 'Tesla', 'Grimmjow', 'Luppi', 'a random Fracción', 'the toothless hollow floor cleaner'],
            'glance' => '`nThough many do not seem to notice you, `%%s`3 gives you a short glance and looks away in disgust.',
        ];
    }

    return [
        'title' => 'Training Grounds - Zanpakutō',
        'grounds' => 'runmodule.php?module=training_bleach',
        'image' => 'modules/zanpakutou/training.jpg',
        'intro' => 'Many Shinigami novices and higher ranks train here to improve themselves or simply to complete their tasks perfectly and rise even higher in rank.',
        'who' => ['Zaraki Kenpachi', 'Madarame Ikkaku', 'Hisagi Shūhei', 'Matsumoto Rangiku (^^)', 'Abarai Renji', 'Kira Izuru', 'Kuchiki Rukia', 'the Training Master', 'the toothless floor cleaner'],
        'glance' => '`nThough many do not seem to notice you, `%%s`3 gives you a short glance and nods.',
    ];
}

function zanpakutou_training_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'dragonkill':
            // The blade grows with its wielder up to the level of Shikai.
            $zan = zanpakutou_load();
            if ($zan['powerlevel'] < 5) {
                $zan['powerlevel']++;
                zanpakutou_save($zan);
                if ($zan['name'] !== '') {
                    output('`n`2%s`q has gained a power level and is now at power level %s!`n', $zan['name'], $zan['powerlevel']);
                }
            }
            break;
        case 'traininggrounds':
            if (bleach_zanpakutou_path() === 'shinigami') {
                addnav('Zanpakutō');
                addnav('Zanpakutō Training', 'runmodule.php?module=zanpakutou_training');
            }
            break;
        case 'arrancargrounds':
            if (bleach_zanpakutou_path() === 'arrancar') {
                addnav('Resurrección');
                addnav('Resurrección Training', 'runmodule.php?module=zanpakutou_training');
            }
            break;
        case 'lodge-desc':
            if (bleach_zanpakutou_path() !== 'shinigami') {
                break;
            }
            $zan = zanpakutou_load();
            $bankaidk = (int) get_module_setting('bankaidk', 'zanpakutou_training');
            if ($zan['bankai']['text'] !== '') {
                output('`nUrahara also offers Bankai training, which you have already completed.`n`n');
            } elseif ($zan['shikai']['text'] !== '' && $zan['powerlevel'] >= 5 && $zan['powerlevel'] < 10 && $session['user']['dragonkills'] >= $bankaidk) {
                if ($zan['powerlevel'] === 5) {
                    output('`n`2"`$I might have an offer to train you for Bankai - if you are interested, of course... you seem able to make it. It will be costly, but worth your time.`2"`n`n');
                } else {
                    output('`n`2"`$If you want to continue your Bankai training, I am all ears!`2"`n`n');
                }
                addnav('Bankai Training');
                addnav('Improve your Bankai summoning skills', 'runmodule.php?module=zanpakutou_training&center=bankai');
            } else {
                output('`nUrahara also offers Bankai training... but you do not qualify for it yet. You need your Shikai, at least power level 5 and %s final tests.`n`n', $bankaidk);
            }
            break;
    }

    return $args;
}

/**
 * The player's master, assigned at random if the awakening left none.
 */
function zanpakutou_training_master(string $path): array
{
    $master = zanpakutou_master($path, (int) get_module_pref('special'), (int) get_module_pref('master'));
    if ($master === null || !get_module_pref('awakened')) {
        $master = zanpakutou_random_master($path, 25);
        set_module_pref('master', $master['id']);
        set_module_pref('special', $master['special']);
        set_module_pref('awakened', 1);
    }

    return $master;
}

/**
 * The awakening in the forest: spar with a master until the blade speaks.
 */
function zanpakutou_training_runevent(string $type, string $link): void
{
    global $session;

    $path = bleach_zanpakutou_path();
    $zan = zanpakutou_load();
    $op = (string) Http::get('op');
    $session['user']['specialinc'] = 'module:zanpakutou_training';

    switch ($op) {
        case 'initialcall':
            if ($zan['name'] === '' && has_buff('shikai_awaken')) {
                zanpakutou_training_name_form($link, $path);
                return;
            }
            addnav('Back to the fight', $link . 'op=fight');
            return;
        case 'setname':
            if ($zan['name'] === '' && has_buff('shikai_awaken') && !Forms::isUnverifiedRequest()) {
                $name = zanpakutou_clean_text(Http::post('zanpakutou'), 50);
                if ($name === '') {
                    output('`x"`$Silly you... I have a name!`x"`n`n');
                    zanpakutou_training_name_form($link, $path);
                    return;
                }
                $zan['name'] = $name;
                $zan['type'] = e_rand(1, count(zanpakutou_types()));
                $zan['form'] = e_rand(1, count(zanpakutou_forms()));
                zanpakutou_save($zan);
                $session['user']['weapon'] = $name;
                strip_buff('shikai_awaken');
                apply_buff('zanpakutou_awakened', zanpakutou_awakening_buff($zan));
                output('`xAs you call out `$%s`x, you also realize the form and type of your blade...`n`n', $name);
                output('It is formed like a `$%s`x and its type is `$%s`x...`n`n', zanpakutou_form_label($zan), zanpakutou_type_label($zan));
            }
            addnav('Back to the fight', $link . 'op=fight');
            return;
        case 'escape':
            if ($session['user']['superuser'] & SU_DEVELOPER) {
                output('Due to your powers as a god you teleport yourself out of it.');
                $session['user']['specialinc'] = '';
                $session['user']['badguy'] = '';
                return;
            }
            zanpakutou_training_fight($link, $path);
            return;
        case 'fight':
        case 'run':
            zanpakutou_training_fight($link, $path);
            return;
        default:
            if (!zanpakutou_training_can_awaken()) {
                output('`2You hear a faint whisper from your blade, but it fades before you can make out a word.`n');
                $session['user']['specialinc'] = '';
                return;
            }
            require_once 'lib/playerfunctions.php';
            $master = zanpakutou_random_master($path, 25);
            $extra = e_rand(1, max(1, (int) $session['user']['level']));
            $badguy = [
                'creaturename' => $master['name'],
                'creaturelevel' => $session['user']['level'] + e_rand(1, 3),
                'creatureweapon' => $master['weapon'],
                'creatureattack' => get_player_attack() + $extra,
                'creaturedefense' => get_player_defense() + $extra,
                'creaturehealth' => $session['user']['level'] * 10 + 450 + $extra * 20,
                'creatureexp' => 1,
                'creaturegold' => 0,
                'hidehitpoints' => true,
                'masterid' => $master['id'],
                'masterspecial' => $master['special'],
                'diddamage' => 0,
            ];
            $session['user']['badguy'] = createstring(['enemies' => [$badguy], 'options' => ['type' => 'forest']]);
            output('`2You run into `^%s`2, who is looking for a sparring partner and challenges you right away.`n`n', $master['name']);
            zanpakutou_training_fight($link, $path);
    }
}

/**
 * The name form shown when the blade calls out to its wielder.
 */
function zanpakutou_training_name_form(string $link, string $path): void
{
    if ($path === 'arrancar') {
        output('`xYou sense a tingling sensation coming from your blade that echoes through your entire body.`n`nTime is meaningless - you realize that you can finally compress the hidden energy you built up devouring Hollows into a form...`n`n');
        output('"`$What are you waiting for?`x" echoes in your mind. Release the power you acquired to annihilate your enemies!`n`n');
    } else {
        output('`xYou sense a tingling sensation coming from your blade that echoes through your entire body.`n`nTime seems to stand still as a cloaked figure appears before your inner eye, vague in shape and form, but possibly human. Or?`n`n');
        output('"`$What are you waiting for?`nYou are one, the enemy is one.`nWhat is there to fear?`x"`n`n');
    }
    output('`$Cast off your fear`nLook forward`nGo forward`nNever stand still`nRetreat and you will age`nHesitate and you will die`n`nShout! My name is...`0`n');
    $action = $link . 'op=setname';
    addnav('', $action);
    rawoutput("<form action='" . htmlspecialchars($action, ENT_QUOTES) . "' method='POST'>" . Forms::csrfField());
    rawoutput(Forms::previewField('zanpakutou', false, '', false, false, false));
    rawoutput("<input type='submit' class='button' value='" . htmlspecialchars(translate_inline('Shout!'), ENT_QUOTES) . "'></form>");
    output('`i`4Colour codes and special characters can be used; italic, bold and centering are removed.`i`n');
    addnav('Back to the fight', $link . 'op=fight');
}

/**
 * One round of the awakening fight.
 */
function zanpakutou_training_fight(string $link, string $path): void
{
    global $session;

    // Roughly every other round brings the blade closer to awakening.
    if (e_rand(0, 20) > 0) {
        increment_module_pref('awakened_counter', 1);
    }
    if ((int) get_module_pref('awakened_counter') > 20 && zanpakutou_load()['name'] === '' && !has_buff('shikai_awaken')) {
        apply_buff('shikai_awaken', [
            'name' => '`$Z`4anpakutō',
            'rounds' => 50,
            'atkmod' => 1.1,
            'defmod' => 1.3,
            'expireafterfight' => 1,
            'roundmsg' => '`$Call out my name...',
            'schema' => 'module-zanpakutou_training',
        ]);
    }

    $victory = false;
    $defeat = false;
    include 'battle.php';

    if ($victory || $defeat) {
        global $newenemies;

        require_once 'lib/forestoutcomes.php';
        $enemy = $newenemies[0] ?? [];
        $session['user']['specialinc'] = '';
        if ($victory) {
            forestvictory($newenemies);
        } else {
            forestdefeat($newenemies);
        }
        if (zanpakutou_load()['name'] !== '' && isset($enemy['masterid'])) {
            set_module_pref('master', (int) $enemy['masterid']);
            set_module_pref('special', (int) $enemy['masterspecial']);
            set_module_pref('awakened', 1);
            $grounds = $path === 'arrancar' ? 'Las Noches' : 'Training Grounds';
            output('`n`n`l%s`l says, "Your blade has revealed its name to you... this is quite some excitement. Visit me in the `$%s`l %sand we will work on it some more.`n`nThanks for the sparring..."', $enemy['creaturename'], $grounds, $defeat ? translate_inline('once you are alive again ') : '');
        }
        $session['user']['badguy'] = '';
        return;
    }

    // No auto-fight: the blade only speaks between single rounds.
    $script = str_contains($link, '?') ? (str_ends_with($link, '&') ? $link : $link . '&') : $link . '?';
    blocknav($script . 'op=fight&auto=', true);
    fightnav(true, false, $link);
    if (has_buff('shikai_awaken') && e_rand(0, 2) === 1) {
        addnav('Zanpakutō');
        addnav('`$Call out thy name...', $link . 'op=initialcall');
    }
    if ($session['user']['superuser'] & SU_DEVELOPER) {
        addnav('Superuser');
        addnav('Escape the sparring', $link . 'op=escape');
    }
}

function zanpakutou_training_run(): void
{
    if (Http::get('center') === 'bankai') {
        zanpakutou_training_bankai_center();
    } else {
        zanpakutou_training_grounds();
    }
}

/**
 * Lessons with the master on the training grounds.
 */
function zanpakutou_training_grounds(): void
{
    global $session;

    $path = bleach_zanpakutou_path();
    $info = zanpakutou_training_path_info($path);
    [$firstRelease, $finalRelease] = zanpakutou_release_names();
    page_header($info['title']);
    addnav('Navigation');
    addnav('Back to the Training Grounds', $info['grounds']);
    output('`#`b`c`n`2%s`0`c`b`n`n', translate_inline($info['title']));

    $zan = zanpakutou_load();
    if ($path === '' || $zan['name'] === '') {
        output('`3Your blade is still silent. Fight on - one day it will speak to you.');
        page_footer();
        return;
    }
    $master = zanpakutou_training_master($path);
    $self = 'runmodule.php?module=zanpakutou_training';
    $canShikai = $zan['shikai']['text'] === '' && $zan['powerlevel'] >= 5;
    $canBankai = $path === 'shinigami' && $zan['shikai']['text'] !== '' && $zan['bankai']['text'] === '' && $zan['powerlevel'] >= ZANPAKUTOU_MAX_POWERLEVEL;
    $op = (string) Http::get('op');

    switch ($op) {
        case 'asktypes':
            output('`%%s`t says, "`gThere are different types of blades and each relies on another attribute of the wielder.`n`n', $master['name']);
            output('`$Fire`g requires Constitution to wield.`n`1Water`g requires Wisdom to control.`n`3Wind`g requires Constitution to endure.`n`#Lightning`g requires Intelligence to channel.`n`qPower`g requires Strength to enforce.`n`%Kidō`g requires Intelligence to manifest.`n`xIce`g requires Dexterity to conjure.`t"`n');
            break;
        case 'askpowerlevel':
            output('`%%s`t says, "`gWell, your %s`g currently has an estimated power level of `$%s`g...`t"`n`n', $master['name'], $zan['name'], $zan['powerlevel']);
            if ($zan['shikai']['text'] !== '') {
                output('You already command your %s.`n', translate_inline($firstRelease));
            } elseif ($canShikai) {
                output('"`gYou have enough skill to draw out your powers. Doing so takes %s full turns of meditation. Then your blade will reveal its first true form to you.`t"`n', ZANPAKUTOU_TRAINING_TURNS);
                if ($session['user']['turns'] >= ZANPAKUTOU_TRAINING_TURNS) {
                    addnav($firstRelease);
                    addnav(['Acquire your %s', $firstRelease], "$self&op=train&release=shikai");
                }
            } else {
                output('"`gYou are on your way to release your blade at will. At power level 5 you will be able to manifest your %s for some of your reiatsu.`t"`n', translate_inline($firstRelease));
            }
            if ($path === 'shinigami') {
                if ($zan['bankai']['text'] !== '') {
                    output('You already command your %s.`n', translate_inline($finalRelease));
                } elseif ($canBankai) {
                    output('"`gYou have enough skill to finalize your Bankai. Doing so takes %s full turns of meditation. Then your blade will reveal its final form to you and accept a name for the final release.`t"`n', ZANPAKUTOU_TRAINING_TURNS);
                    if ($session['user']['turns'] >= ZANPAKUTOU_TRAINING_TURNS) {
                        addnav($finalRelease);
                        addnav(['Acquire your %s', $finalRelease], "$self&op=train&release=bankai");
                    }
                } elseif ($zan['powerlevel'] >= 5) {
                    output('"`gYou are on your way to your Bankai. At power level %s you will be able to manifest it for some of your reiatsu - Urahara can help you get there.`t"`n', ZANPAKUTOU_MAX_POWERLEVEL);
                }
            }
            break;
        case 'train':
        case 'setname':
            $bankai = Http::get('release') === 'bankai';
            $label = $bankai ? $finalRelease : $firstRelease;
            if (!($bankai ? $canBankai : $canShikai) || $session['user']['turns'] < ZANPAKUTOU_TRAINING_TURNS) {
                output('`%%s`t shakes their head. "`gYou are not ready for this yet.`t"`n', $master['name']);
                break;
            }
            $action = "$self&op=setname&release=" . ($bankai ? 'bankai' : 'shikai');
            if ($op === 'setname' && !Forms::isUnverifiedRequest()) {
                $name = zanpakutou_clean_text(Http::post('release_name'), 50);
                $phrase = zanpakutou_clean_text(Http::post('release_phrase'), 200);
                if ($name !== '' && $phrase !== '') {
                    $key = $bankai ? 'bankai' : 'shikai';
                    $zan[$key] = ['name' => $name, 'text' => $phrase, 'achieved' => date('Y-m-d H:i:s')];
                    zanpakutou_save($zan);
                    $session['user']['turns'] -= ZANPAKUTOU_TRAINING_TURNS;
                    output('`xAfter long meditation you name your %s `x"%s`x" and call it with "%s`x"...`n`n', translate_inline($label), $name, $phrase);
                    output('It is formed like a `$%s`x and its type is `$%s`x.`n', zanpakutou_form_label($zan), zanpakutou_type_label($zan));
                    break;
                }
                output('`x"`$Silly you... I need some letters here!`x"`n`n');
            }
            addnav('', $action);
            output('`TEnter the name of your %s:`0`n', translate_inline($label));
            rawoutput("<form action='" . htmlspecialchars($action, ENT_QUOTES) . "' method='POST'>" . Forms::csrfField());
            rawoutput(Forms::previewField('release_name', false, '', false, false, false));
            output('`TEnter the phrase that releases it:`0`n');
            rawoutput(Forms::previewField('release_phrase', false, '', false, false, false));
            rawoutput("<input type='submit' class='button' value='" . htmlspecialchars(translate_inline('Meditate'), ENT_QUOTES) . "'></form>");
            output('`n`i`4Meditating costs %s turns. Colour codes and special characters can be used; italic, bold and centering are removed.`i`n', ZANPAKUTOU_TRAINING_TURNS);
            break;
        default:
            if (is_module_active('addimages')) {
                rawoutput("<div style='text-align:center'><img src='" . htmlspecialchars($info['image'], ENT_QUOTES) . "' alt=''></div>");
            }
            output_notl('`3');
            output($info['intro']);
            output($info['glance'], $info['who'][array_rand($info['who'])]);
            output('`n`n`vYour master `%%s`v is here. What do you want to do?', $master['name']);
    }

    addnav(['Master %s', sanitize($master['name'])]);
    addnav(['`tAsk %s`t about your power', $master['name']], "$self&op=askpowerlevel");
    addnav(['`tAsk %s`t about the types of blades', $master['name']], "$self&op=asktypes");
    page_footer();
}

/**
 * Urahara's Bankai training for Shinigami at power levels 5 to 9.
 */
function zanpakutou_training_bankai_center(): void
{
    global $session;

    require_once 'lib/battle-functions.php';
    $self = 'runmodule.php?module=zanpakutou_training&center=bankai';
    $trainer = '`2Tessai';
    $u = &$session['user'];
    $zan = zanpakutou_load();
    $level = $zan['powerlevel'];
    $gold = $level * ZANPAKUTOU_TRAINING_BANKAI_GOLD;
    $young = translate_inline($u['sex'] ? 'mistress' : 'master');
    [$spiritName, $spiritWeapon] = zanpakutou_bankai_spirit($zan);
    $op = (string) Http::get('op');

    page_header('Urahara - Zanpakutō Training');
    output('`#`b`c`n`2Bankai Training Grounds - `vZ`lanpakutō`0`c`b`n`n');

    $qualified = bleach_zanpakutou_path() === 'shinigami' && $zan['shikai']['text'] !== '' && $level >= 5
        && $u['dragonkills'] >= (int) get_module_setting('bankaidk', 'zanpakutou_training');
    if (!$qualified && $op !== 'fight' && $op !== 'run') {
        output('%s`4 looks at you and shakes his head. "`xYou are not ready for this place.`4"', $trainer);
        addnav('Navigation');
        addnav('Back to the shop', 'lodge.php');
        page_footer();
        return;
    }

    switch ($op) {
        case 'standardtrain':
            if ($level < 5 || $level > 7 || $u['gold'] < $gold) {
                output('%s`4 says: "`xNot like this, young %s.`4"`n`n', $trainer, $young);
                break;
            }
            $u['gold'] -= $gold;
            debuglog("paid $gold gold for Bankai training at power level $level");
            if (e_rand(1, 100) <= ZANPAKUTOU_TRAINING_BANKAI_CHANCE) {
                $zan['powerlevel']++;
                zanpakutou_save($zan);
                output('`4You have `$SUCCESSFULLY`4 improved yourself on your way to Bankai... you gain a power level!`n`nYou are now at power level %s!`n`n', $zan['powerlevel']);
            } else {
                output('`4You have `lFAILED`4 to improve yourself on your way to Bankai...`n`nYou stay at power level %s!`n`n', $level);
            }
            break;
        case 'preptrain':
            if ($level !== 8 || $u['gems'] < ZANPAKUTOU_TRAINING_BANKAI_GEMS) {
                output('%s`4 says: "`xNot like this, young %s.`4"`n`n', $trainer, $young);
                break;
            }
            $u['gems'] -= ZANPAKUTOU_TRAINING_BANKAI_GEMS;
            $zan['powerlevel']++;
            zanpakutou_save($zan);
            debuglog('paid ' . ZANPAKUTOU_TRAINING_BANKAI_GEMS . ' gems for the Bankai preparation');
            output('`4You have `$SUCCESSFULLY`4 prepared yourself for the final Bankai challenge... you gain a power level!`n`nYou are now at power level %s!`n`n', $zan['powerlevel']);
            break;
        case 'prefight':
            if ($level !== 9) {
                break;
            }
            require_once 'lib/playerfunctions.php';
            $badguy = [
                'creaturename' => $spiritName,
                'creaturelevel' => $u['level'] + 3,
                'creatureweapon' => $spiritWeapon,
                'creatureattack' => get_player_attack() * 4,
                'creaturedefense' => round(get_player_defense() * 1.95),
                'creaturehealth' => round($u['maxhitpoints'] * 7),
                'creatureexp' => 0,
                'creaturegold' => 0,
                'hidehitpoints' => true,
                'diddamage' => 0,
            ];
            $u['badguy'] = createstring(['enemies' => [$badguy], 'options' => ['type' => 'quest']]);
            if (has_buff('mount')) {
                suspend_buff_by_name('mount', '`3You will certainly miss your fellow comrade...');
            }
            suspend_companions('bankai');
            // no break
        case 'fight':
        case 'run':
            zanpakutou_training_bankai_fight($self, $trainer, $young);
            page_footer();
            return;
    }

    addnav('Navigation');
    addnav('Back to the shop', 'lodge.php');
    addnav('Actions');
    $level = zanpakutou_load()['powerlevel'];
    $gold = $level * ZANPAKUTOU_TRAINING_BANKAI_GOLD;
    output('`4Here in the underground extra-dimensional compound you see many signs of bone-grinding training towards the final form all Shinigami desire.`n`nAs supervisor (and fee-taker), %s`4 is here to assist you.`n`nYou are currently at power level %s.`n`n', $trainer, $level);
    if ($level >= ZANPAKUTOU_MAX_POWERLEVEL) {
        output('%s`4 says: "`xYoung %s, what are you looking for? Did you lose something here? Visit your master if you want to improve further.`4"', $trainer, $young);
    } elseif ($level === 9) {
        output('%s`4 says: "`xYoung %s, you are one step shy of Bankai. The stage is set... enter if you dare...`4"', $trainer, $young);
        addnav(['Challenge %s', $spiritName], "$self&op=prefight");
    } elseif ($level === 8) {
        output('%s`4 says: "`xYoung %s, there is nothing more you can learn by training yourself. You now need access to the most sacred battleground, and you need to summon your Bankai\'s true form. All of this will cost you %s gems - a one-time fee whether you succeed or not.`4"', $trainer, $young, ZANPAKUTOU_TRAINING_BANKAI_GEMS);
        if ($u['gems'] >= ZANPAKUTOU_TRAINING_BANKAI_GEMS) {
            addnav(['Prep Training (%s gems)', ZANPAKUTOU_TRAINING_BANKAI_GEMS], "$self&op=preptrain");
        } else {
            addnav('Prep Training (not enough gems!)', '');
        }
    } else {
        output('%s`4 says: "`xYoung %s, you still need the basic training to challenge your Zanpakutō. For %s gold pieces you may use this facility. Success is not guaranteed: only %s out of 100 make a step forward.`4"', $trainer, $young, $gold, ZANPAKUTOU_TRAINING_BANKAI_CHANCE);
        if ($u['gold'] >= $gold) {
            addnav(['Standard Training (%s gold)', $gold], "$self&op=standardtrain");
        } else {
            addnav('Standard Training (not enough gold!)', '');
        }
    }
    page_footer();
}

/**
 * The final Bankai challenge against the spirit of the blade.
 */
function zanpakutou_training_bankai_fight(string $self, string $trainer, string $young): void
{
    global $session;

    $victory = false;
    $defeat = false;
    include 'battle.php';

    if ($victory) {
        $zan = zanpakutou_load();
        $zan['powerlevel'] = ZANPAKUTOU_MAX_POWERLEVEL;
        zanpakutou_save($zan);
        $session['user']['badguy'] = '';
        output('`4%s`4 rushes to you: "`xYoung %s! You did it! You have bested the legendary power all Shinigami seek!`n`nQuick now: rush to your master and give your new form its name!`4"`n`n', $trainer, $young);
        output('`$You can now acquire `4B`xan`4K`xai`$, the final release of your blade. Use it wisely...');
        debuglog('won the final Bankai challenge');
        if (has_buff('mount')) {
            unsuspend_buff_by_name('mount', '`3You feel full of new inspiration along with your mount.');
        }
        unsuspend_companions('bankai');
        addnav('Victory!');
        addnav('Back to the shop', 'lodge.php');
        addnav('Back to Tessai', $self);
    } elseif ($defeat) {
        $session['user']['badguy'] = '';
        $session['user']['gold'] = 0;
        $session['user']['experience'] = (int) round($session['user']['experience'] * 0.9);
        $session['user']['alive'] = 0;
        $session['user']['hitpoints'] = 0;
        debuglog('was killed by the spirit of their Bankai');
        addnews('%s`v\'s body turned up, torn to shreds!', $session['user']['name']);
        output('`4The spirit of your blade has torn you apart. You lose all gold on hand and 10% of your experience.');
        if (has_buff('mount')) {
            unsuspend_buff_by_name('mount');
        }
        unsuspend_companions('bankai');
        addnav('Death...');
        addnav('To the shades', 'shades.php');
    } else {
        fightnav(true, false, "$self&");
    }
}
