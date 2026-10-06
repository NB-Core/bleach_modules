<?php

declare(strict_types=1);

use Doctrine\DBAL\ParameterType;
use Lotgd\Http;
use Lotgd\MySQL\Database;
use Lotgd\PlayerFunctions;

/**
 * Masters & Level-up for Hollows
 *
 * The Espada train Arrancar and Menos in Las Noches. Works like train.php of
 * the core with its own table of masters (masters_arrancar); the level-up
 * itself is the core's (PlayerFunctions::levelUp).
 */

require_once 'modules/zanpakutou/lib/races.php';

function arrancar_train_getmoduleinfo(): array
{
    return [
        'name' => 'Masters & Level-up for Hollows',
        'version' => '2.0',
        'author' => '`2Oliver Brendel',
        'category' => 'Arrancar',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'requires' => [
            'lasnoches' => '2.0|Hueco Mundo (Las Noches) by Oliver Brendel',
        ],
    ];
}

function arrancar_train_install(): bool
{
    module_addhook('village');
    require_once 'lib/tabledescriptor.php';
    synctable(Database::prefix('masters_arrancar'), [
        'creatureid' => ['name' => 'creatureid', 'type' => 'int(11) unsigned', 'extra' => 'auto_increment'],
        'creaturename' => ['name' => 'creaturename', 'type' => 'varchar(50)', 'null' => '1'],
        'creaturelevel' => ['name' => 'creaturelevel', 'type' => 'int(11)', 'null' => '1'],
        'creatureweapon' => ['name' => 'creatureweapon', 'type' => 'varchar(50)', 'null' => '1'],
        'creaturelose' => ['name' => 'creaturelose', 'type' => 'varchar(120)', 'null' => '1'],
        'creaturewin' => ['name' => 'creaturewin', 'type' => 'varchar(120)', 'null' => '1'],
        'creaturegold' => ['name' => 'creaturegold', 'type' => 'int(11)', 'null' => '1'],
        'creatureexp' => ['name' => 'creatureexp', 'type' => 'int(11)', 'null' => '1'],
        'creaturehealth' => ['name' => 'creaturehealth', 'type' => 'int(11)', 'null' => '1'],
        'creatureattack' => ['name' => 'creatureattack', 'type' => 'int(11)', 'null' => '1'],
        'creaturedefense' => ['name' => 'creaturedefense', 'type' => 'int(11)', 'null' => '1'],
        'key-PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary key', 'unique' => '1', 'columns' => 'creatureid'],
    ], true);

    $conn = Database::getDoctrineConnection();
    $table = Database::prefix('masters_arrancar');
    if ((int) $conn->fetchOne("SELECT COUNT(*) FROM $table") === 0) {
        foreach (arrancar_train_default_masters() as $master) {
            $conn->insert($table, $master);
        }
    }

    return true;
}

function arrancar_train_uninstall(): bool
{
    Database::getDoctrineConnection()->executeStatement('DROP TABLE IF EXISTS ' . Database::prefix('masters_arrancar'));

    return true;
}

/**
 * The Espada who train the Hollows, one per level.
 *
 * @return list<array<string, string|int>>
 */
function arrancar_train_default_masters(): array
{
    $masters = [
        ['Tia Harribel', 'Tiburón', 'Impossible! I am supposed to be stronger than you!', 'You are an embarrassment. I will make sure you cannot make this embarrassment worse.', 12, 2],
        ['Zommari Leroux', 'Brujería Body Control', 'You are a genius, one might say.', 'Your base are belong to us. *snicker*', 22, 4],
        ['Lilynette Gingerback', 'Unbelievably sharp tongue', 'Starrk! That meanie hit me!', "It's over. Wolf wins.", 33, 6],
        ['Gantenbainne Mosqueda', 'Dragra', 'I was careless...', 'Did my light cut you down or was it your mean hairstyle?', 44, 8],
        ['Aaroniero Arruruerie', 'Glotonería', 'Good - you managed to defeat your memories after all.', 'Still pondering about the past? Conquer your memories and get stronger than them.', 55, 10],
        ['Ulquiorra Cifer', 'Murciélago', 'Ts, you may pass.', 'This is a waste of my time.', 66, 12],
        ['Yammy Riyalgo', 'Ira', "That was fun. Let's do it again as a little warm-up.", 'Is that all you got? Next time bring along somebody stronger.', 77, 14],
        ['Baraggan Luisenbarn', 'Arrogante', 'Already over? You did surprisingly well.', 'Someone like you is a thousand years too early to be standing before me.', 88, 16],
        ['Grimmjow Jaegerjaquez', 'Pantera', 'I commend you, nice play.', 'You are unworthy of my time.', 99, 19],
    ];
    $rows = [];
    foreach ($masters as $index => [$name, $weapon, $lose, $win, $health, $stat]) {
        $rows[] = [
            'creatureid' => $index + 1,
            'creaturename' => $name,
            'creaturelevel' => $index + 1,
            'creatureweapon' => $weapon,
            'creaturelose' => $lose,
            'creaturewin' => $win,
            'creaturehealth' => $health,
            'creatureattack' => $stat,
            'creaturedefense' => $stat,
        ];
    }

    return $rows;
}

function arrancar_train_dohook(string $hookname, array $args): array
{
    global $session;

    if ($hookname === 'village' && bleach_is_hollow() && $session['user']['location'] === bleach_lasnoches_city()) {
        tlschema($args['schemas']['fightnav'] ?? 'village');
        addnav($args['fightnav'] ?? 'Blades');
        tlschema();
        addnav('Espada Training', 'runmodule.php?module=arrancar_train');
    }

    return $args;
}

/**
 * The master to face: by id, or a random one of the highest level the
 * player may challenge.
 */
function arrancar_train_master(int $mid, int $level): ?array
{
    $conn = Database::getDoctrineConnection();
    $table = Database::prefix('masters_arrancar');
    if ($mid > 0) {
        $row = $conn->executeQuery("SELECT * FROM $table WHERE creatureid = :id", ['id' => $mid], ['id' => ParameterType::INTEGER])->fetchAssociative();
    } else {
        $top = (int) $conn->executeQuery("SELECT MAX(creaturelevel) FROM $table WHERE creaturelevel <= :level", ['level' => $level], ['level' => ParameterType::INTEGER])->fetchOne();
        $rows = $conn->executeQuery("SELECT * FROM $table WHERE creaturelevel = :level", ['level' => $top], ['level' => ParameterType::INTEGER])->fetchAllAssociative();
        $row = $rows === [] ? false : $rows[array_rand($rows)];
    }
    if ($row === false) {
        return null;
    }
    foreach (['creaturegold', 'creatureexp'] as $key) {
        $row[$key] = (int) ($row[$key] ?? 0);
    }

    return $row;
}

/**
 * Navigation of the training page.
 */
function arrancar_train_nav(int $mid, bool $withMaster = true): void
{
    global $session;

    $self = 'runmodule.php?module=arrancar_train';
    addnav('Navigation');
    villagenav();
    addnav('Actions');
    if (!$withMaster) {
        return;
    }
    addnav('Question Master', "$self&op=question&master=$mid");
    addnav('M?Challenge Master', "$self&op=challenge&master=$mid");
    if ($session['user']['superuser'] & SU_DEVELOPER) {
        addnav('Superuser Gain level', "$self&op=challenge&victory=1&master=$mid");
    }
}

function arrancar_train_run(): void
{
    global $session, $companions;

    require_once 'lib/battle-functions.php';
    require_once 'lib/experience.php';
    require_once 'lib/substitute.php';
    require_once 'lib/taunt.php';
    require_once 'lib/increment_specialty.php';

    $u = &$session['user'];
    $self = 'runmodule.php?module=arrancar_train';
    page_header('Espada Training');
    output('`b`cEspada Training`c`b');
    modulehook('arrancar-train', []);

    if (!bleach_is_hollow()) {
        output('`n`4The Espada do not waste their time on the likes of you.');
        arrancar_train_nav(0, false);
        page_footer();
        return;
    }

    $maxlevel = (int) getsetting('maxlevel', 15);
    $master = $u['level'] < $maxlevel ? arrancar_train_master((int) Http::get('master'), (int) $u['level']) : null;
    if ($master === null) {
        checkday();
        output('`nYou stroll into the battle grounds.`n`n');
        output('Younger Arrancar huddle together and point as you pass by. You know this place well. There is nothing left for you here but memories.');
        output('You remain a moment longer and look at the tiny little wimps in training before you return to your business.');
        arrancar_train_nav(0, false);
        modulehook('arrancar-footer', []);
        page_footer();
        return;
    }

    $mid = (int) $master['creatureid'];
    $exprequired = exp_for_next_level($u['level'], $u['dragonkills']);
    $op = (string) Http::get('op');
    $battle = false;
    $victory = false;
    $defeat = false;
    $instant = false;

    switch ($op) {
        case '':
            checkday();
            output('`nThe sound of conflict surrounds you. The clash of blades in grisly battle stirs your hollow heart.');
            output('`n`n`^%s stands ready to evaluate you.`0', $master['creaturename']);
            arrancar_train_nav($mid);
            break;
        case 'question':
            checkday();
            arrancar_train_nav($mid);
            output('`nYou approach `^%s`0 and inquire about your standing.', $master['creaturename']);
            if ($u['experience'] >= $exprequired) {
                output('`n`n`^%s`0 says, "Your spiritual pressure is getting bigger..."', $master['creaturename']);
            } else {
                output('`n`n`^%s`0 states that you need `%%s`0 more experience before you are ready for a challenge.', $master['creaturename'], number_format($exprequired - $u['experience'], 0, getsetting('moneydecimalpoint', '.'), getsetting('moneythousandssep', ',')));
            }
            break;
        case 'autochallenge':
            addnav('Fight Your Master', "$self&op=challenge&master=$mid");
            output('`n`^%s`0 has heard rumours that you think you are so much more powerful that you do not even need to fight to prove anything.', $master['creaturename']);
            output('`^%s`0 demands an immediate battle, and your own pride prevents you from refusing.', $master['creaturename']);
            if ($u['hitpoints'] < $u['maxhitpoints']) {
                output('`n`nTo make it fair, you are healed before the fight begins.');
                $u['hitpoints'] = $u['maxhitpoints'];
            }
            modulehook('master-autochallenge');
            if (getsetting('displaymasternews', 1)) {
                addnews('`3%s`3 was hunted down by their master, `^%s`3, for being truant.', $u['name'], $master['creaturename']);
            }
            break;
        case 'challenge':
            if (Http::get('victory') && ($u['superuser'] & SU_DEVELOPER)) {
                $instant = true;
                $u['experience'] = max((int) $u['experience'], (int) $exprequired);
                $u['seenmaster'] = 0;
            }
            if ($u['seenmaster']) {
                output('`nYou think that perhaps you have seen enough of your master for today.');
                arrancar_train_nav(0, false);
                break;
            }
            $u['seenmaster'] = 1;
            if ($u['experience'] < $exprequired) {
                output('`nYou ready your %s`0 and approach `^%s`0.`n`n', $u['weapon'], $master['creaturename']);
                output('You lunge with all your might, only to realize that `^%s`0 is holding your weapon.', $master['creaturename']);
                output('Meekly you retrieve your %s`0 and slink out of the grounds to the sound of mocking laughter.', $u['weapon']);
                arrancar_train_nav(0, false);
                break;
            }
            restore_buff_fields();
            $dk = (int) round(get_player_dragonkillmod(true) * 0.33);
            $atkflux = min(e_rand(0, $dk), (int) round($dk * .25));
            $defflux = min(e_rand(0, $dk - $atkflux), (int) round($dk * .25));
            calculate_buff_fields();
            $master['creatureattack'] += $atkflux;
            $master['creaturedefense'] += $defflux;
            $master['creaturehealth'] += ($dk - ($atkflux + $defflux)) * 5;
            $u['badguy'] = createstring(['enemies' => [$master], 'options' => ['type' => 'train']]);
            $battle = true;
            if ($instant) {
                $victory = true;
                output('`nWith a flurry of blows you dispatch your master.`n');
            }
            break;
        case 'run':
            output('`$Your pride prevents you from running from this conflict!`0');
            // no break
        case 'fight':
            Http::set('op', 'fight');
            $battle = true;
            break;
    }

    if ($battle) {
        suspend_buffs('allowintrain', '`&Your pride prevents you from using extra abilities during the fight!`0`n');
        suspend_companions('allowintrain');
        if ($instant) {
            $badguy = $master;
        } else {
            include 'battle.php';
        }
        if ($victory) {
            arrancar_train_victory($badguy, $mid);
        } elseif ($defeat) {
            if (getsetting('displaymasternews', 1)) {
                addnews('`%%s`5 has challenged their master, %s, and lost!`n%s', $u['name'], $badguy['creaturename'], select_taunt_array());
            }
            $u['hitpoints'] = $u['maxhitpoints'];
            output('`&`bYou have been defeated by `%%s`&!`b`n', $badguy['creaturename']);
            output('`%%s`$ halts just before delivering the final blow, helps you to your feet and heals your wounds.`n', $badguy['creaturename']);
            output_notl('`^`b%s`b`0`n', substitute_array((string) $badguy['creaturewin']));
            arrancar_train_nav($mid);
            modulehook('training-defeat', $badguy);
        } else {
            fightnav(false, false, "$self&master=$mid");
        }
        if ($victory || $defeat) {
            unsuspend_buffs('allowintrain', '`&You now feel free to make use of your buffs again!`0`n');
            unsuspend_companions('allowintrain');
            $u['badguy'] = '';
            if (is_array($companions)) {
                $u['companions'] = createstring($companions);
            }
        }
    }
    modulehook('arrancar-footer', []);
    page_footer();
}

/**
 * Level up after beating the master, as train.php does it.
 */
function arrancar_train_victory(array $badguy, int $mid): void
{
    global $session, $companions;

    $u = &$session['user'];
    output_notl('`b`&%s`0`b`n', substitute_array((string) $badguy['creaturelose']));
    output('`b`$You have defeated %s!`0`b`n', $badguy['creaturename']);

    $multimaster = (int) getsetting('multimaster', 1) === 1;
    $session['user'] = PlayerFunctions::levelUp($session['user'], $multimaster);
    output('`#You advance to level `^%s`#!`n', $u['level']);
    output('Your maximum hitpoints are now `^%s`#!`n', $u['maxhitpoints']);
    output('You gain an attack point!`nYou gain a defense point!`n');
    if ($u['level'] < (int) getsetting('maxlevel', 15)) {
        output('You have a new master.`n');
    } else {
        output('None in Hueco Mundo are mightier than you!`n');
    }
    if ($u['referer'] > 0 && ($u['level'] >= (int) getsetting('referminlevel', 4) || $u['dragonkills'] > 0) && $u['refererawarded'] < 1) {
        $award = (int) getsetting('refereraward', 25);
        Database::getDoctrineConnection()->executeStatement(
            'UPDATE ' . Database::prefix('accounts') . ' SET donation = donation + :award WHERE acctid = :acctid',
            ['award' => $award, 'acctid' => (int) $u['referer']],
            ['award' => ParameterType::INTEGER, 'acctid' => ParameterType::INTEGER]
        );
        $u['refererawarded'] = 1;
        require_once 'lib/systemmail.php';
        systemmail((int) $u['referer'], ['`%One of your referrals advanced!`0'], ['`&%s`# has advanced to level `^%s`#, and so you have earned `^%s`# points!', $u['name'], $u['level'], $award]);
    }
    increment_specialty('`^');
    if (getsetting('companionslevelup', 1) && is_array($companions)) {
        foreach ($companions as $name => $companion) {
            $companions[$name] = PlayerFunctions::levelUpCompanion($companion);
        }
    }
    invalidatedatacache('list.php-warsonline');
    if (getsetting('displaymasternews', 1)) {
        addnews('`%%s`3 has defeated their master, `%%s`3, to advance to level `^%s`3 after `^%s`3 days!!', $u['name'], $badguy['creaturename'], $u['level'], max(1, (int) $u['age']));
    }
    $u['hitpoints'] = max((int) $u['hitpoints'], (int) $u['maxhitpoints']);
    arrancar_train_nav(0);
    modulehook('training-victory', $badguy);
}
