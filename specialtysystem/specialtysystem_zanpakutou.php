<?php

declare(strict_types=1);

/**
 * Elemental zanpakutou techniques (fire, water, wind, ice, lightning).
 *
 * One technique per element. A player only sees the technique of their own
 * zanpakutou type and can only use it while the blade is released. Hidden
 * from the specialty selection.
 */

function specialtysystem_zanpakutou_getmoduleinfo(): array
{
    return [
        'name' => 'Specialty System - Zanpakutou Techniques',
        'author' => '`2Oliver Brendel`0',
        'version' => '2.0',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'category' => 'Specialty System',
        'requires' => [
            'specialtysystem' => '1.03|Specialty System by Oliver Brendel (NB-Core/modules)',
            'zanpakutou' => '2.0|Zanpakutou (Bleach) by Oliver Brendel',
        ],
    ];
}

function specialtysystem_zanpakutou_install(): bool
{
    module_addhook('specialtysystem-register');

    return true;
}

function specialtysystem_zanpakutou_uninstall(): bool
{
    require_once 'modules/specialtysystem/uninstall.php';
    specialtysystem_uninstall('specialtysystem_zanpakutou');

    return true;
}

/**
 * Element techniques keyed by zanpakutou type.
 *
 * Damage is stat * [min, max] factor + level + sqrt(dragon kills).
 *
 * @return array<int, array{skill: string, label: string, buffname: string, startmsg: string, stat: string, factor: array{0: float, 1: float}}>
 */
function specialtysystem_zanpakutou_elements(): array
{
    return [
        3 => [
            'skill' => 'fire',
            'label' => '`$Fire Bolt',
            'buffname' => '`$Teppō Nadegiri',
            'startmsg' => '`$`iTeppō Nadegiri!`i`nYou fire a bolt of fire against {badguy}.',
            'stat' => 'constitution',
            'factor' => [3.5, 4.5],
        ],
        4 => [
            'skill' => 'water',
            'label' => '`1Water Bolt',
            'buffname' => '`1Teppō Mizuchi',
            'startmsg' => '`1`iTeppō Mizuchi!`i`nYou fire a bolt of water against {badguy}.',
            'stat' => 'wisdom',
            'factor' => [3.5, 5.5],
        ],
        5 => [
            'skill' => 'wind',
            'label' => '`3Slicing Wind',
            'buffname' => '`3Kaze Messā',
            'startmsg' => '`3`iKaze Messā!`i`nYou slice {badguy} up in wicked winds.',
            'stat' => 'constitution',
            'factor' => [3.5, 4.5],
        ],
        6 => [
            'skill' => 'ice',
            'label' => '`tIce Bolt',
            'buffname' => '`t次の舞・白漣, tsugi no mai',
            'startmsg' => '`t`i次の舞・白漣, tsugi no mai, hakuren!`i`nYou fire a bolt of ice against {badguy}.',
            'stat' => 'dexterity',
            'factor' => [2.5, 3.5],
        ],
        7 => [
            'skill' => 'lightning',
            'label' => '`#Lightning Bolt',
            'buffname' => '`#Raikiri',
            'startmsg' => '`#`iRaikiri!`i`nYou blast a bolt of lightning against {badguy}.',
            'stat' => 'intelligence',
            'factor' => [3.5, 4.5],
        ],
    ];
}

/**
 * Cost of every element technique in specialty uses.
 */
const SPECIALTYSYSTEM_ZANPAKUTOU_COST = 1;

/**
 * The element technique of the current player's zanpakutou, if any.
 */
function specialtysystem_zanpakutou_element(): ?array
{
    require_once 'modules/zanpakutou/lib/zanpakutou.php';
    $zan = zanpakutou_load();
    if ($zan['name'] === '') {
        return null;
    }

    return specialtysystem_zanpakutou_elements()[$zan['type']] ?? null;
}

function specialtysystem_zanpakutou_fightnav(): array|false
{
    $element = specialtysystem_zanpakutou_element();
    if ($element === null) {
        return false;
    }
    require_once 'modules/specialtysystem/functions.php';
    $uses = specialtysystem_availableuses();
    if ($uses < SPECIALTYSYSTEM_ZANPAKUTOU_COST) {
        return false;
    }
    [$firstRelease] = zanpakutou_release_names();
    tlschema('module-specialtysystem_zanpakutou');
    specialtysystem_addfightheadline($firstRelease . ' Techniques');
    specialtysystem_addfightnav($element['label'], $element['skill'], SPECIALTYSYSTEM_ZANPAKUTOU_COST);
    tlschema();
    $nav = specialtysystem_getfightnav();

    return is_array($nav) ? $nav : false;
}

function specialtysystem_zanpakutou_apply(mixed $skillname): void
{
    global $session;

    $element = specialtysystem_zanpakutou_element();
    if ($element === null || $element['skill'] !== (string) $skillname) {
        return;
    }
    if (!zanpakutou_is_released()) {
        [$firstRelease] = zanpakutou_release_names();
        output('`$You cannot use this technique before your blade is released in %s form!`0`n`n', translate_inline($firstRelease, 'module-zanpakutou'));

        return;
    }
    require_once 'modules/specialtysystem/functions.php';
    if (specialtysystem_availableuses() < SPECIALTYSYSTEM_ZANPAKUTOU_COST) {
        return;
    }
    $u = $session['user'];
    $base = $u['level'] + sqrt((float) $u['dragonkills']);
    apply_buff('zanpakutou_' . $element['skill'], [
        'startmsg' => $element['startmsg'],
        'name' => $element['buffname'],
        'rounds' => 1,
        'minioncount' => 1,
        'effectmsg' => '{badguy} takes some serious damage ({damage} points)!',
        'minbadguydamage' => round($u[$element['stat']] * $element['factor'][0] + $base),
        'maxbadguydamage' => round($u[$element['stat']] * $element['factor'][1] + $base),
        'schema' => 'module-specialtysystem_zanpakutou',
    ]);
    specialtysystem_incrementuses('specialtysystem_zanpakutou', SPECIALTYSYSTEM_ZANPAKUTOU_COST);
}

function specialtysystem_zanpakutou_dohook(string $hookname, array $args): array
{
    if ($hookname === 'specialtysystem-register') {
        $args[] = [
            'spec_name' => 'Zanpakutō Techniques',
            'spec_colour' => '`Q',
            'spec_shortdescription' => '-internal-',
            'spec_longdescription' => '-internal-',
            'modulename' => 'specialtysystem_zanpakutou',
            'fightnav_active' => 1,
            'newday_active' => 0,
            'dragonkill_active' => 0,
            'dragonkill_minimum_requirement' => -1,
            'stat_requirements' => [],
            'race_requirements' => [],
            'noaddskillpoints' => 1,
            'basic_uses' => 0,
        ];
    }

    return $args;
}

function specialtysystem_zanpakutou_run(): void
{
}
