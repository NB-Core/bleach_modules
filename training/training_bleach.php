<?php

declare(strict_types=1);

use Lotgd\Forms;
use Lotgd\Http;

/**
 * Training Grounds (Bleach)
 *
 * Training grounds next to the masters: buy attack and defense training,
 * switch the specialty, rest or recall the mount and listen to a wise man.
 * Shinigami and Quincy reach them from train.php, Hollows (Arrancar, Menos)
 * from the Espada training of arrancar_train.
 *
 * Fires "traininggrounds" (Shinigami grounds) or "arrancargrounds" (Hollow
 * grounds) so other modules can add their navigation.
 */

require_once 'modules/zanpakutou/lib/races.php';

function training_bleach_getmoduleinfo(): array
{
    return [
        'name' => 'Training Grounds (Bleach)',
        'version' => '2.0',
        'author' => '`2Oliver Brendel',
        'category' => 'Training',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'requires' => [
            'specialtysystem' => '1.03|Specialty System by Oliver Brendel (NB-Core/modules)',
            'zanpakutou' => '2.0|Zanpakutou (Bleach) by Oliver Brendel',
        ],
    ];
}

function training_bleach_install(): bool
{
    module_addhook('footer-train');
    module_addhook('arrancar-footer');

    return true;
}

function training_bleach_uninstall(): bool
{
    return true;
}

function training_bleach_dohook(string $hookname, array $args): array
{
    $op = (string) Http::get('op');
    if ($op !== '' && $op !== 'question') {
        return $args;
    }
    $hollow = bleach_is_hollow();
    if (($hookname === 'footer-train' && !$hollow) || ($hookname === 'arrancar-footer' && $hollow)) {
        addnav('Training');
        addnav('Training Grounds', 'runmodule.php?module=training_bleach');
    }

    return $args;
}

/**
 * Gold cost of the next attack or defense training, indexed by level.
 *
 * @return list<int>
 */
function training_bleach_costs(): array
{
    return [48, 225, 585, 990, 1575, 2250, 2790, 3420, 4230, 5040, 5850, 6840, 8010, 9000, 10350, 11500, 13775, 15850, 17030, 18270, 20020, 21150, 22500, 25550, 30000, 32000, 34000, 38000];
}

/**
 * Texts and people of the Shinigami or the Hollow grounds.
 */
function training_bleach_flavour(bool $hollow): array
{
    if ($hollow) {
        return [
            'back' => 'runmodule.php?module=arrancar_train',
            'hook' => 'arrancargrounds',
            'trainers' => ['Nnoitra', 'Starrk', 'Baraggan', 'Cirucci (^^)', 'Tesla', 'Grimmjow', 'Luppi', 'Szayel Aporro', 'Ulquiorra'],
            'healers' => ['Nnoitra', 'Starrk', 'Baraggan', 'Nelliel (^^)', 'Tesla', 'Grimmjow', 'Luppi', 'Szayel Aporro', 'Ulquiorra'],
            'idle' => ['Nnoitra', 'Starrk', 'Baraggan', 'Cirucci (^^)', 'Tesla', 'Grimmjow', 'Luppi', 'a random Fracción', 'the toothless hollow floor cleaner'],
            'intro' => 'This place looks almost deserted - except for the signs of decay and destruction that hover over the entire area. It is used for special training indeed.',
            'glance' => '`nThough many do not seem to notice you, `%%s`3 gives you a short glance and looks away in disgust.',
            'offer' => '"`tDisgusting ant... it will cost you `^%s gold`t to improve your current skills, which are at level %s.`3"',
            'favour' => '`2You also feel you have satisfied Death pretty well!',
            'wise' => 'Wise Hollow',
            'quotes' => [
                'Resolve is hard like a diamond, sharper than steel and clearer than the sun in the sky... either you do the crushing, or you are crushed.',
                'You need to grow more.',
                'Death is only the beginning.',
                "Don't eat too late. And don't eat yellow hollows.",
                'Treat other souls with little respect.',
                'Prey on the weak. Then again, they might become strong one day - better wait and harvest later.',
                'Being self-sufficient is good when out alone in Hueco Mundo. But think about leaving this desert some day to find Shinigami and take them out.',
                'We all are actors on a gigantic stage. Try to be in the spotlight.',
                'Death is just a game. But with great graphics...',
                'A proper meal consists of at least one healthy vegan soul.',
                'Meat eaters give fatty souls.',
            ],
        ];
    }

    return [
        'back' => 'train.php',
        'hook' => 'traininggrounds',
        'trainers' => ['Zaraki Kenpachi', 'Madarame Ikkaku', 'Hisagi Shūhei', 'Matsumoto Rangiku (^^)', 'Abarai Renji', 'Kira Izuru', 'Kuchiki Rukia'],
        'healers' => ['Unohana Retsu', 'Kyōraku Shunsui', 'Komamura Saijin', 'Ukitake Jūshirō', 'Kusajishi Yachiru', 'Kuchiki Rukia'],
        'idle' => ['Zaraki Kenpachi', 'Madarame Ikkaku', 'Hisagi Shūhei', 'Matsumoto Rangiku (^^)', 'Abarai Renji', 'Kira Izuru', 'Kuchiki Rukia', 'the Training Master', 'the toothless floor cleaner'],
        'intro' => 'Many Shinigami novices and higher ranks train here to improve themselves or simply to complete their tasks perfectly and rise even higher in rank.',
        'glance' => '`nThough many do not seem to notice you, `%%s`3 gives you a short glance and nods.',
        'offer' => '"`tWell, if you are up for a little training... it will cost you `^%s gold`t to improve your current skills, which are at level %s.`3"',
        'favour' => '`2You also feel you have satisfied your zanpakutō pretty well!',
        'wise' => 'Wise Man',
        'quotes' => [
            'Resolve is hard like a diamond, sharper than steel and clearer than the sun in the sky... either you do the crushing, or you are crushed.',
            'You need to grow more.',
            'Death is only the beginning.',
            "Don't eat too late.",
            'Treat other souls with respect.',
            'Wash your hands after visiting the restroom.',
            "Don't prey on the weak. Imagine they become strong one day.",
            'Being self-sufficient is good when out alone in the desert. But also think about leaving this desert some day.',
            'We all are actors on a gigantic stage.',
            'Life is just a game. But with great graphics...',
        ],
    ];
}

/**
 * Specialties the player may switch to here.
 *
 * @return array<string, string> module name => translated specialty name
 */
function training_bleach_switchable_specialties(): array
{
    global $session;

    require_once 'modules/specialtysystem/datafunctions.php';
    // The race check lives in the engine's module file.
    require_once 'modules/specialtysystem.php';
    $active = specialtysystem_get('active');
    $choices = [];
    foreach (specialtysystem_getspecs() as $module => $data) {
        $minimum = (int) $data['dragonkill_minimum_requirement'];
        if ($minimum === -1 || $minimum > $session['user']['dragonkills'] || $module === $active) {
            continue;
        }
        [$allowed] = specialtysystem_check_races((string) ($data['race_requirements'] ?? ''), (string) $session['user']['race']);
        if (!$allowed) {
            continue;
        }
        $choices[$module] = translate_inline($data['spec_name'], 'module-' . $module);
    }

    return $choices;
}

function training_bleach_run(): void
{
    global $session;

    $u = &$session['user'];
    $hollow = bleach_is_hollow();
    $flavour = training_bleach_flavour($hollow);
    $self = 'runmodule.php?module=training_bleach';
    $gold = (int) $u['level'] * 75;
    $op = (string) Http::get('op');

    page_header('Training Grounds');
    addnav('Navigation');
    addnav('Back to the Main Grounds', $flavour['back']);
    output('`#`b`c`n`2Training Grounds`0`c`b`n`n');
    modulehook($flavour['hook'], []);

    switch ($op) {
        case 'mountsummon':
        case 'mountsummonexecute':
            $summon = !empty($session['bufflist']['mount']['suspended']);
            $price = (int) round($gold / 2);
            $who = $flavour['trainers'][array_rand($flavour['trainers'])];
            addnav('Back to the Training Grounds', $self);
            if (!has_buff('mount')) {
                output('`3You have no mount to call.');
                break;
            }
            if ($op === 'mountsummonexecute') {
                if ($u['gold'] < $price) {
                    output('`3Shame on you! You do not have enough gold with you!');
                    break;
                }
                $u['gold'] -= $price;
                if ($summon) {
                    output('`%%s`3 intones some strange syllables... you join in, and together you call your mount back from its rest...`n`n', $who);
                    unsuspend_buff_by_name('mount', '`3You feel full of new inspiration along with your mount.');
                } else {
                    output('`%%s`3 intones some strange syllables... you join in, and together you send your mount to rest for some time...`n`n', $who);
                    suspend_buff_by_name('mount', '`3You will certainly miss your fellow comrade...');
                }
                break;
            }
            global $playermount;
            $action = translate_inline($summon ? 'summon' : 'unsummon');
            output('`3You decide to %s your mount... you could do this on your own, too, but it is always convenient to have somebody helping you.', $action);
            output('`n`nKnowing it would take more time all by yourself, you ask `%%s`3 for help.`n`n', $who);
            output('`3"`tSo... you want to %s `v%s`t? No big deal, this won\'t take much time, so it costs only `^%s gold`t to relieve me from duty.`3"`n`n', $action, $playermount['mountname'] ?? '', $price);
            addnav('Actions');
            addnav($summon ? 'Summon your mount' : 'Unsummon your mount', "$self&op=mountsummonexecute");
            break;
        case 'specialty':
        case 'setspecialty':
            addnav('Back to the Training Grounds', $self);
            if ($hollow || !is_module_active('specialtysystem')) {
                output('`3Nobody here can help you with that.');
                break;
            }
            $choices = training_bleach_switchable_specialties();
            if ($op === 'setspecialty' && Http::postIsset('ssystem') && !Forms::isUnverifiedRequest()) {
                $choice = (string) Http::post('ssystem');
                if (!isset($choices[$choice])) {
                    output('`3"`tI cannot teach you that.`3"');
                    break;
                }
                if ($u['gold'] < $gold) {
                    output('`3Shame on you! You do not have enough gold with you!');
                    break;
                }
                $u['gold'] -= $gold;
                specialtysystem_set(['active' => $choice]);
                $u['specialty'] = 'SS';
                set_module_pref('cache', '', 'specialtysystem');
                output('`3"`tYou are now working towards %s`t... good luck!`3"`n`n', $choices[$choice]);
                break;
            }
            $who = $flavour['trainers'][array_rand($flavour['trainers'])];
            output('`3You decide to change your specialty and to work on a new kind of technique from now on.');
            output('`n`nKnowing it would take time to do this all by yourself, you ask `%%s`3 for help.`n`n', $who);
            output('`3"`tSo... you want to switch your techniques... I can help you. But you need to pay me off for my other duties: currently `^%s gold pieces`t.`3"`n`n', $gold);
            if ($choices === []) {
                output('`3"`tSorry, there is nothing else I can teach you right now.`3"');
                break;
            }
            $action = "$self&op=setspecialty";
            addnav('', $action);
            $options = '';
            foreach ($choices as $module => $name) {
                $options .= "<option value='" . htmlspecialchars($module, ENT_QUOTES) . "'>" . htmlspecialchars(sanitize($name), ENT_QUOTES) . '</option>';
            }
            rawoutput("<form action='" . htmlspecialchars($action, ENT_QUOTES) . "' method='POST'>" . Forms::csrfField()
                . "<select name='ssystem'>$options</select> "
                . "<input type='submit' class='button' value='" . htmlspecialchars(translate_inline('Switch'), ENT_QUOTES) . "'></form>");
            output('`n`n`lPS: You keep the knowledge of your current specialties. You simply gain new skill points in the new specialty you select here.');
            break;
        case 'trainoffensive':
        case 'traindefensive':
            $offensive = $op === 'trainoffensive';
            $field = $offensive ? 'weapondmg' : 'armordef';
            $people = $offensive ? $flavour['trainers'] : $flavour['healers'];
            $who = $people[array_rand($people)];
            addnav('Back to the Training Grounds', $self);
            addnav('Actions');
            output($offensive ? '`c`b`1~~~ `$Offensive Training`1 ~~~`b`c`n`n' : '`c`b`1~~~ `$Defensive Training`1 ~~~`b`c`n`n');
            $lev = (int) $u[$field];
            $hook = modulehook($offensive ? 'training-costs-o' : 'training-costs-d', ['user' => $u, 'cost' => training_bleach_costs()]);
            $price = (int) ($hook['cost'][$lev] ?? 0);
            if (!training_bleach_can_train($field) || $price <= 0) {
                output('`3"`tThere is nothing more I can teach you.`3"');
                break;
            }
            if (Http::get('action') === 'train') {
                if ($u['gold'] < $price) {
                    output('`3Shame on you! You do not have enough gold with you!');
                    break;
                }
                $u['gold'] -= $price;
                $u[$field] = $lev + 1;
                if ($offensive) {
                    $u['attack']++;
                    output('`1You have gained `%one attack point`1 through harsh and rigorous training!`n`n');
                } else {
                    $u['defense']++;
                    output('`1You have gained `%one defense point`1 through harsh and rigorous training!`n`n');
                }
                debuglog(sprintf('trained %s to level %d, paid %d gold', $field, $lev + 1, $price));
                if (e_rand(0, 10) === 10) {
                    $favour = e_rand(2, 20);
                    output($flavour['favour']);
                    output('`n`~(You gain %s favour)`n', $favour);
                    $u['deathpower'] += $favour;
                }
                break;
            }
            output('`3You look for somebody with enough time to teach you... %s`3 is currently free.`n`n', $who);
            output($flavour['offer'], $price, $lev);
            addnav('Train Yourself', $u['gold'] >= $price ? "$self&op=$op&action=train" : '');
            break;
        case 'wiseman':
            addnav('Back to the Training Grounds', $self);
            $quotes = $flavour['quotes'];
            output_notl('`$');
            output($quotes[(int) date('z') % count($quotes)]);
            break;
        default:
            if (!$hollow && is_module_active('addimages')) {
                rawoutput("<div style='text-align:center'><img src='modules/addimages/header-train.gif' alt=''></div>");
            }
            output('`3You enter the vast training grounds you know so well.`n`n');
            output($flavour['intro']);
            output($flavour['glance'], $flavour['idle'][array_rand($flavour['idle'])]);
            output('`n`n`vWhat do you want to do?');
            addnav('Actions');
            if (!$hollow && is_module_active('specialtysystem')) {
                addnav('Switch your specialty', "$self&op=specialty");
            }
            if (has_buff('mount')) {
                addnav(!empty($session['bufflist']['mount']['suspended']) ? 'Summon your mount' : 'Unsummon your mount', "$self&op=mountsummon");
            }
            if (training_bleach_can_train('weapondmg')) {
                addnav('Train your Offensive Skills', "$self&op=trainoffensive");
            }
            if (training_bleach_can_train('armordef')) {
                addnav('Train your Defensive Skills', "$self&op=traindefensive");
            }
            addnav($flavour['wise']);
            addnav('Ask...', "$self&op=wiseman");
    }
    page_footer();
}

/**
 * Up to 25 levels per skill and 30 in total.
 */
function training_bleach_can_train(string $field): bool
{
    global $session;

    $u = $session['user'];

    return (int) $u[$field] < 25 && (int) $u['weapondmg'] + (int) $u['armordef'] < 30;
}
