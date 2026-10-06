<?php

declare(strict_types=1);

/**
 * Hollow techniques of the Arrancar: Bala, Sonido, Hierro, Cero.
 *
 * Selectable at the new day by Arrancar only (race requirement).
 */

function specialtysystem_arrancar_getmoduleinfo(): array
{
    return [
        'name' => 'Specialty System - Arrancar',
        'author' => '`2Oliver Brendel`0',
        'version' => '2.0',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'category' => 'Specialty System',
        'requires' => [
            'specialtysystem' => '1.03|Specialty System by Oliver Brendel (NB-Core/modules)',
        ],
    ];
}

function specialtysystem_arrancar_install(): bool
{
    module_addhook('specialtysystem-register');

    return true;
}

function specialtysystem_arrancar_uninstall(): bool
{
    require_once 'modules/specialtysystem/uninstall.php';
    specialtysystem_uninstall('specialtysystem_arrancar');

    return true;
}

/**
 * Skills of this specialty: skill => [label, cost, minimum dragon kills].
 *
 * @return array<string, array{0: string, 1: int, 2: int}>
 */
function specialtysystem_arrancar_skills(): array
{
    return [
        'arrancar1' => ['Bala', 1, 0],
        'arrancar2' => ['Sonido', 2, 0],
        'arrancar3' => ['Enhanced Hierro', 3, 70],
        'arrancar4' => ['Cero', 5, 0],
        'arrancar5' => ['Gran Rey Cero', 40, 100],
    ];
}

function specialtysystem_arrancar_fightnav(): array|false
{
    global $session;

    require_once 'modules/specialtysystem/functions.php';
    $uses = specialtysystem_availableuses('specialtysystem_arrancar');
    if ($uses <= 0) {
        return false;
    }
    tlschema('module-specialtysystem_arrancar');
    specialtysystem_addfightheadline('Arrancar Powers', $uses, specialtysystem_getskillpoints('specialtysystem_arrancar'));
    foreach (specialtysystem_arrancar_skills() as $skill => [$label, $cost, $dks]) {
        if ($cost <= $uses && $session['user']['dragonkills'] >= $dks) {
            specialtysystem_addfightnav($label, $skill, $cost);
        }
    }
    tlschema();
    $nav = specialtysystem_getfightnav();

    return is_array($nav) ? $nav : false;
}

function specialtysystem_arrancar_apply(mixed $skillname): void
{
    global $session;

    $skills = specialtysystem_arrancar_skills();
    $skillname = (string) $skillname;
    if (!isset($skills[$skillname])) {
        return;
    }
    [, $cost, $dks] = $skills[$skillname];
    require_once 'modules/specialtysystem/functions.php';
    $u = $session['user'];
    if (specialtysystem_availableuses('specialtysystem_arrancar') < $cost || $u['dragonkills'] < $dks) {
        return;
    }
    $dkbonus = sqrt((float) $u['dragonkills']);
    switch ($skillname) {
        case 'arrancar1':
            apply_buff('arrancar1', [
                'startmsg' => '`i`$`bBala!`b`i`n`qYou `L`bfire a fast blast of reiatsu at your enemy!`b',
                'name' => '`x`bBa`4l`xa`b',
                'rounds' => 1,
                'areadamage' => false,
                'minbadguydamage' => round($u['strength'] * 2 + $u['intelligence'] * 1.7 + $dkbonus),
                'maxbadguydamage' => round($u['strength'] * 3 + $u['intelligence'] * 2.7 + $dkbonus),
                'minioncount' => 1,
                'effectmsg' => '`q{badguy}`q suffers {damage} damage!',
                'effectnodmgmsg' => '`qThe bala was neutralized.',
                'schema' => 'module-specialtysystem_arrancar',
            ]);
            break;
        case 'arrancar2':
            apply_buff('arrancar2', [
                'startmsg' => '`i`QYou `7implement a high-speed movement technique`i. `n`${badguy}`7 is unable to keep up completely with your speed.',
                'name' => '`4So`$ni`4do',
                'rounds' => 10,
                'wearoff' => 'You stop using Sonido.',
                'badguyatkmod' => 0.82,
                'roundmsg' => '`&{badguy}`& cannot attack you well!',
                'schema' => 'module-specialtysystem_arrancar',
            ]);
            break;
        case 'arrancar3':
            apply_buff('arrancar3', [
                'startmsg' => '`i`QYou `7harden your reiatsu around your skin, increasing your defense!`i',
                'name' => '`4Enhanced `)Hi`ver`)ro',
                'rounds' => 10,
                'wearoff' => 'Your defense returns to normal.',
                'badguyatkmod' => 0.65,
                'roundmsg' => '`&{badguy}`&\'s attacks deflect off your Hierro!',
                'schema' => 'module-specialtysystem_arrancar',
            ]);
            break;
        case 'arrancar4':
            apply_buff('arrancar4', [
                'startmsg' => '`i`$`bCero!`b`i`n`qYou `L`bfire a huge blast of reiatsu at your enemy!`b',
                'name' => '`x`bCero`b',
                'rounds' => 1,
                'areadamage' => false,
                'minbadguydamage' => round($u['strength'] * 4 + $u['intelligence'] * 2 + $dkbonus),
                'maxbadguydamage' => round($u['strength'] * 4 + $u['intelligence'] * 4 + $dkbonus),
                'minioncount' => 1,
                'effectmsg' => '`q{badguy}`q is hit by your Cero for {damage} damage!',
                'effectnodmgmsg' => '`qThe Cero was neutralized.',
                'schema' => 'module-specialtysystem_arrancar',
            ]);
            break;
        case 'arrancar5':
            apply_buff('arrancar5', [
                'startmsg' => '`i`$`bGran Rey Cero!`b`i`n`qYou `L`bfire the ultimate form of the Cero, blasting away your enemy!`b',
                'name' => 'Gran Rey Cero',
                'rounds' => 1,
                'wearoff' => 'The immense force of the reiatsu heavily wounds {badguy}.',
                'badguyatkmod' => 0,
                'areadamage' => true,
                'minbadguydamage' => round($u['strength'] * 5 + $u['intelligence'] * 4 + $dkbonus),
                'maxbadguydamage' => round($u['strength'] * 5 + $u['intelligence'] * 6.2 + $dkbonus),
                'minioncount' => 3,
                'effectmsg' => '`7{badguy}`7 is rooted in fear from the immense reiatsu and takes {damage} damage from the powerful blast!',
                'effectnodmgmsg' => '`7The Cero missed.',
                'schema' => 'module-specialtysystem_arrancar',
            ]);
            break;
    }
    specialtysystem_incrementuses('specialtysystem_arrancar', $cost);
}

function specialtysystem_arrancar_dohook(string $hookname, array $args): array
{
    if ($hookname === 'specialtysystem-register') {
        $args[] = [
            'spec_name' => 'Arrancar Powers',
            'spec_colour' => '`x',
            'spec_shortdescription' => '`$The vile hollow power that destroys!',
            'spec_longdescription' => '`5As a hollow being, you have inherited powers that let you perform great feats. They manifest in different ways, from firing reiatsu to hardening your own body.',
            'modulename' => 'specialtysystem_arrancar',
            'fightnav_active' => 1,
            'newday_active' => 0,
            'dragonkill_active' => 0,
            'dragonkill_minimum_requirement' => 0,
            'stat_requirements' => [],
            'race_requirements' => ['Arrancar'],
            'noaddskillpoints' => 0,
            'basic_uses' => 0,
        ];
    }

    return $args;
}

function specialtysystem_arrancar_run(): void
{
}
