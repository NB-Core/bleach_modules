<?php

declare(strict_types=1);

/**
 * Reiatsu techniques: the specialty every Bleach character can choose.
 *
 * Every player also gets its basic use, whatever specialty they pick.
 */

function specialtysystem_reiatsu_getmoduleinfo(): array
{
    return [
        'name' => 'Specialty System - Reiatsu Techniques',
        'author' => '`2Oliver Brendel`0',
        'version' => '2.0',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'category' => 'Specialty System',
        'requires' => [
            'specialtysystem' => '1.03|Specialty System by Oliver Brendel (NB-Core/modules)',
        ],
    ];
}

function specialtysystem_reiatsu_install(): bool
{
    module_addhook('specialtysystem-register');

    return true;
}

function specialtysystem_reiatsu_uninstall(): bool
{
    require_once 'modules/specialtysystem/uninstall.php';
    specialtysystem_uninstall('specialtysystem_reiatsu');

    return true;
}

/**
 * Skills of this specialty: skill => [label, cost].
 *
 * @return array<string, array{0: string, 1: int}>
 */
function specialtysystem_reiatsu_skills(): array
{
    return [
        'reiatsu1' => ['Empowered Attack', 1],
        'reiatsu2' => ['Empowered Defense', 1],
        'reiatsu3' => ['`$Spirit Force Oppression', 40],
    ];
}

function specialtysystem_reiatsu_fightnav(): array|false
{
    require_once 'modules/specialtysystem/functions.php';
    $uses = specialtysystem_availableuses('specialtysystem_reiatsu');
    if ($uses <= 0) {
        return false;
    }
    tlschema('module-specialtysystem_reiatsu');
    specialtysystem_addfightheadline('Reiatsu Techniques', $uses, specialtysystem_getskillpoints('specialtysystem_reiatsu'));
    foreach (specialtysystem_reiatsu_skills() as $skill => [$label, $cost]) {
        if ($cost <= $uses) {
            specialtysystem_addfightnav($label, $skill, $cost);
        }
    }
    tlschema();
    $nav = specialtysystem_getfightnav();

    return is_array($nav) ? $nav : false;
}

function specialtysystem_reiatsu_apply(mixed $skillname): void
{
    global $session;

    $skills = specialtysystem_reiatsu_skills();
    $skillname = (string) $skillname;
    if (!isset($skills[$skillname])) {
        return;
    }
    require_once 'modules/specialtysystem/functions.php';
    $cost = $skills[$skillname][1];
    if (specialtysystem_availableuses('specialtysystem_reiatsu') < $cost) {
        return;
    }
    $rounds = e_rand(0, intdiv((int) $session['user']['level'], 3)) + 2;
    switch ($skillname) {
        case 'reiatsu1':
            apply_buff('reiatsu1', [
                'startmsg' => '`v`iPower Slash!`i`n`tYou `qempower your next attacks.',
                'name' => '`vPower Slash',
                'rounds' => $rounds,
                'atkmod' => 1.1,
                'schema' => 'module-specialtysystem_reiatsu',
            ]);
            break;
        case 'reiatsu2':
            apply_buff('reiatsu2', [
                'startmsg' => '`v`iIron Defense!`i`n`tYou `qrelease reiatsu to aid your defense.',
                'name' => '`vIron Defense',
                'rounds' => $rounds,
                'wearoff' => 'Your reiatsu settles down.',
                'badguyatkmod' => 0.8,
                'defmod' => 1.1,
                'roundmsg' => '{badguy} is hindered by your reiatsu!',
                'schema' => 'module-specialtysystem_reiatsu',
            ]);
            break;
        case 'reiatsu3':
            // Weakens the enemy by up to 40%, reached at 50 dragon kills.
            $defmod = 1 - min(0.4, round($session['user']['dragonkills'] * 0.008, 2));
            apply_buff('reiatsu3', [
                'startmsg' => '`v`iSpirit Force Oppression!`i`n`tYou `qrelease an amount of your reiatsu to show your power to your enemy...',
                'name' => '`vSpirit Force Oppression',
                'rounds' => 5,
                'wearoff' => 'You stop releasing reiatsu.',
                'badguydefmod' => $defmod,
                'badguyatkmod' => $defmod - 0.1,
                'roundmsg' => 'Your released reiatsu startles the enemy quite a bit!',
                'schema' => 'module-specialtysystem_reiatsu',
            ]);
            break;
    }
    specialtysystem_incrementuses('specialtysystem_reiatsu', $cost);
}

function specialtysystem_reiatsu_dohook(string $hookname, array $args): array
{
    if ($hookname === 'specialtysystem-register') {
        $args[] = [
            'spec_name' => 'Reiatsu Techniques',
            'spec_colour' => '`v',
            'spec_shortdescription' => '`vChannel your spiritual pressure into raw power.',
            'spec_longdescription' => '`vEvery soul with spiritual power can learn to control its reiatsu. You train to strengthen your attacks, to harden your defense and, one day, to crush your enemies with the sheer weight of your spirit.',
            'modulename' => 'specialtysystem_reiatsu',
            'fightnav_active' => 1,
            'newday_active' => 0,
            'dragonkill_active' => 0,
            'dragonkill_minimum_requirement' => 0,
            'stat_requirements' => [],
            'race_requirements' => [],
            'noaddskillpoints' => 0,
            'basic_uses' => 1,
        ];
    }

    return $args;
}

function specialtysystem_reiatsu_run(): void
{
}
