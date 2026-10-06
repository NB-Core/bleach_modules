<?php

declare(strict_types=1);

/**
 * Zanpakutou data shared by all Bleach modules.
 *
 * A zanpakutou is a plain array stored as JSON in the "zanpakutou" pref of
 * the zanpakutou module:
 *
 *   name, type (1-7), form (1-6), powerlevel (1-10), image, image_validated,
 *   shikai => [name, text, achieved], bankai => [name, text, achieved]
 *
 * Arrancar use the same data; their Shikai is the Resurrección and their
 * Bankai the Segunda Etapa.
 */

require_once __DIR__ . '/races.php';

const ZANPAKUTOU_SHIKAI_COST = 3;
const ZANPAKUTOU_BANKAI_COST = 10;
const ZANPAKUTOU_MAX_POWERLEVEL = 10;

/**
 * Types of zanpakutou, untranslated (translate in schema module-zanpakutou).
 *
 * @return array<int, string>
 */
function zanpakutou_types(): array
{
    return [
        1 => 'Power (Melee)',
        2 => 'Kidō',
        3 => 'Fire',
        4 => 'Water',
        5 => 'Wind',
        6 => 'Ice',
        7 => 'Lightning',
    ];
}

/**
 * Forms of zanpakutou, untranslated (translate in schema module-zanpakutou).
 *
 * @return array<int, string>
 */
function zanpakutou_forms(): array
{
    return [
        1 => 'Katana',
        2 => 'Spear',
        3 => 'Sai',
        4 => 'Wakizashi',
        5 => 'Morning Star',
        6 => 'Dagger',
    ];
}

/**
 * Translated label of the zanpakutou type.
 */
function zanpakutou_type_label(array $zan): string
{
    $types = zanpakutou_types();

    return translate_inline($types[$zan['type']] ?? 'unknown', 'module-zanpakutou');
}

/**
 * Translated label of the zanpakutou form.
 */
function zanpakutou_form_label(array $zan): string
{
    $forms = zanpakutou_forms();

    return translate_inline($forms[$zan['form']] ?? 'unknown', 'module-zanpakutou');
}

/**
 * An empty zanpakutou.
 */
function zanpakutou_default(): array
{
    return [
        'name' => '',
        'type' => 0,
        'form' => 0,
        'powerlevel' => 1,
        'image' => '',
        'image_validated' => false,
        'shikai' => ['name' => '', 'text' => '', 'achieved' => ''],
        'bankai' => ['name' => '', 'text' => '', 'achieved' => ''],
    ];
}

/**
 * Bring stored or posted data into the canonical shape with proper types.
 */
function zanpakutou_normalize(array $data): array
{
    $zan = zanpakutou_default();
    $zan['name'] = (string) ($data['name'] ?? '');
    $zan['type'] = (int) ($data['type'] ?? 0);
    $zan['form'] = (int) ($data['form'] ?? 0);
    $zan['powerlevel'] = max(1, min(ZANPAKUTOU_MAX_POWERLEVEL, (int) ($data['powerlevel'] ?? 1)));
    $zan['image'] = (string) ($data['image'] ?? '');
    $zan['image_validated'] = (bool) ($data['image_validated'] ?? false);
    foreach (['shikai', 'bankai'] as $release) {
        $part = is_array($data[$release] ?? null) ? $data[$release] : [];
        foreach (['name', 'text', 'achieved'] as $key) {
            $zan[$release][$key] = (string) ($part[$key] ?? '');
        }
    }

    return $zan;
}

/**
 * Load a zanpakutou.
 *
 * @param int|null $acctid Account id; null means the current player
 */
function zanpakutou_load(?int $acctid = null): array
{
    $raw = get_module_pref('zanpakutou', 'zanpakutou', $acctid);
    $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

    return zanpakutou_normalize(is_array($data) ? $data : []);
}

/**
 * Store a zanpakutou.
 *
 * @param int|null $acctid Account id; null means the current player
 */
function zanpakutou_save(array $zan, ?int $acctid = null): void
{
    $json = json_encode(zanpakutou_normalize($zan), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    set_module_pref('zanpakutou', $json, 'zanpakutou', $acctid);
    // The cached fight navigation depends on the zanpakutou type.
    if (is_module_active('specialtysystem')) {
        set_module_pref('cache', '', 'specialtysystem', $acctid);
    }
}

/**
 * Clean a name or phrase a player typed in.
 *
 * Colour codes stay; layout codes (`c, `i, `b), line breaks and angle
 * brackets go. Output escaping still happens on display.
 */
function zanpakutou_clean_text(mixed $text, int $maxLength = 100): string
{
    $text = is_string($text) ? $text : '';
    $text = str_replace(["\r\n", "\r", "\n", '`c', '`i', '`b', '`n', '<', '>'], ' ', $text);

    return trim(mb_substr(trim($text), 0, $maxLength));
}

/**
 * Whether a Shikai/Resurrección or Bankai/Segunda Etapa is active right now.
 */
function zanpakutou_is_released(): bool
{
    return has_buff('shikai') || has_buff('bankai');
}

/**
 * Release names for a race: [first release, final release].
 *
 * @return array{0: string, 1: string}
 */
function zanpakutou_release_names(?string $race = null): array
{
    if (bleach_zanpakutou_path($race) === 'arrancar') {
        return ['Resurrección', 'Segunda Etapa'];
    }

    return ['Shikai', 'Bankai'];
}

/**
 * Percent modifiers granted by type and form when releasing.
 *
 * A form entry replaces the type entry for the same modifier.
 *
 * @return array<string, float>
 */
function zanpakutou_release_mods(array $zan, bool $bankai): array
{
    $typeMods = $bankai
        ? [
            1 => ['dmgmod' => 40, 'dmgshield' => 20],
            2 => ['defmod' => 60],
            3 => ['atkmod' => 60],
            4 => ['atkmod' => 30, 'defmod' => 30],
            5 => ['dmgmod' => 60],
            6 => ['defmod' => 30, 'dmgmod' => 30],
            7 => ['dmgshield' => 30, 'dmgmod' => 30],
        ]
        : [
            1 => ['atkmod' => 5, 'dmgmod' => 5],
            2 => ['defmod' => 10],
            3 => ['atkmod' => 10],
            4 => ['atkmod' => 5, 'defmod' => 5],
            5 => ['dmgmod' => 10],
            6 => ['dmgmod' => 5, 'defmod' => 5],
            7 => ['dmgmod' => 5, 'dmgshield' => 5],
        ];
    $formMods = $bankai
        ? [
            1 => ['atkmod' => 80],
            2 => ['atkmod' => 40, 'dmgmod' => 40],
            3 => ['defmod' => 80],
            4 => ['atkmod' => 40, 'defmod' => 40],
            5 => ['dmgmod' => 80],
            6 => ['defmod' => 40, 'dmgmod' => 40],
        ]
        : [
            1 => ['atkmod' => 10],
            2 => ['atkmod' => 5, 'dmgmod' => 5],
            3 => ['defmod' => 10],
            4 => ['atkmod' => 5, 'defmod' => 5],
            5 => ['dmgmod' => 10],
            6 => ['dmgmod' => 5, 'defmod' => 5],
        ];

    $mods = array_merge($typeMods[$zan['type']] ?? [], $formMods[$zan['form']] ?? []);
    $buff = [];
    foreach ($mods as $key => $percent) {
        $buff[$key] = $key === 'dmgshield' ? $percent / 100 : 1 + $percent / 100;
    }

    return $buff;
}

/**
 * The buff of a Shikai/Resurrección or Bankai/Segunda Etapa release.
 */
function zanpakutou_release_buff(array $zan, bool $bankai, ?string $race = null): array
{
    $hollow = bleach_zanpakutou_path($race) === 'arrancar';
    if ($bankai) {
        $name = $hollow ? '`)%s `$Segunda Etapa' : '`)%s `$Bankai';
        $wearoff = $hollow ? '`$Your Segunda Etapa recedes...' : '`$Your Bankai recedes...';
    } else {
        $name = $hollow ? '`)%s `$Resurrección' : '`)%s `$Shikai';
        $wearoff = $hollow ? '`$Your Resurrección recedes...' : '`$Your Shikai recedes...';
    }

    return array_merge([
        'name' => [$name, $zan['name']],
        'rounds' => $zan['powerlevel'] * 10,
        'wearoff' => $wearoff,
        'roundmsg' => ['%s`@ supports you...', $zan['name']],
        'minioncount' => 1,
        'schema' => 'module-zanpakutou',
    ], zanpakutou_release_mods($zan, $bankai));
}

/**
 * One-fight buff granted when the zanpakutou reveals its name.
 */
function zanpakutou_awakening_buff(array $zan): array
{
    global $session;

    $u = $session['user'];
    $name = $zan['name'];
    $form = zanpakutou_form_label($zan);

    switch ($zan['type']) {
        case 7:
            $buff = [
                'name' => '`qLightning Release!',
                'atkmod' => 1.5,
                'dmgshield' => min(round($u['intelligence'] / 100, 2), 0.5),
                'minioncount' => max(1, (int) $u['wisdom']),
                'effectmsg' => ['`)%s`): Your %s`) throws {damage} back at {badguy}!', $name, $form],
                'roundmsg' => ['`)%s`): Your %s`) entangles the enemy with lightning and surrounds you with electricity!', $name, $form],
            ];
            break;
        case 6:
            $buff = [
                'name' => '`qIce Release!',
                'minbadguydamage' => max(2, min($u['wisdom'] - 20, 3)),
                'maxbadguydamage' => min(20, $u['dexterity']) + round($u['wisdom'] / 3),
                'minioncount' => max(1, (int) $u['dexterity']),
                'effectmsg' => ['`)%s`): Your %s`) hits {badguy} for {damage} damage with an ice needle!', $name, $form],
            ];
            break;
        case 5:
            $buff = [
                'name' => '`qWind Release!',
                'minbadguydamage' => max(2, min($u['wisdom'] - 20, 3)),
                'maxbadguydamage' => min(20, $u['dexterity']) + round($u['wisdom'] / 3),
                'minioncount' => max(1, (int) $u['wisdom']),
                'effectmsg' => ['`)%s`): Your %s`) sends a slicing air wave at {badguy} that deals {damage} damage!', $name, $form],
            ];
            break;
        case 4:
            $buff = [
                'name' => '`qWater Release!',
                'minbadguydamage' => max(2, min($u['wisdom'] - 20, 3)),
                'maxbadguydamage' => min(20, $u['dexterity']) + round($u['wisdom'] / 3),
                'minioncount' => max(1, (int) $u['wisdom']),
                'effectmsg' => ['`)%s`): Water that splashes out from your %s`) hits {badguy} for {damage} damage!', $name, $form],
            ];
            break;
        case 3:
            $buff = [
                'name' => '`qFire Release!',
                'minbadguydamage' => max(2, min($u['dexterity'] - 10, 20)),
                'maxbadguydamage' => min(20, $u['dexterity']) + $u['strength'],
                'minioncount' => 1,
                'dmgshield' => 0.1,
                'roundmsg' => 'You are surrounded by fire.',
                'effectmsg' => ['`)%s`): Your %s`) hits {badguy} with {damage} fire damage!', $name, $form],
            ];
            break;
        case 2:
            $buff = [
                'name' => '`qKidō Release!',
                'badguyatkmod' => 0.7,
                'badguydefmod' => 0.7,
                'minioncount' => 1,
                'roundmsg' => ['`)%s`): Your %s`) enthralls {badguy} who has a hard time trying to hit you!', $name, $form],
            ];
            break;
        default:
            $buff = [
                'name' => '`qPower Release!',
                'minbadguydamage' => $u['strength'],
                'maxbadguydamage' => 5 * $u['strength'] + 20,
                'minioncount' => max(1, (int) round(get_player_speed() / 10)),
                'effectmsg' => ['`)%s`): A blast from your %s`) hits {badguy} for {damage} damage!', $name, $form],
            ];
    }

    return $buff + [
        'rounds' => -1,
        'expireafterfight' => 1,
        'schema' => 'module-zanpakutou',
    ];
}

/**
 * The Bankai spirit fought in the final Bankai challenge: [name, weapon].
 *
 * @return array{0: string, 1: string}
 */
function zanpakutou_bankai_spirit(array $zan): array
{
    $spirits = [
        1 => ['`^Golden Samurai', '`^Golden Katana'],
        2 => ['`%Illusion Weaver', '`%Nightmares'],
        3 => ['`$Fire Cat', '`$Fiery Claws'],
        4 => ['`!Water Elemental', '`!Water Sickles'],
        5 => ['`QHurricane Spirit', '`QCutting Winds'],
        6 => ['`1Mystic Ice Dragon', '`1Razorsharp Iceshards'],
        7 => ['`6Thunder God Raijin', '`6Lightning'],
    ];

    return $spirits[$zan['type']] ?? ['Void Form', 'Void Claws'];
}
