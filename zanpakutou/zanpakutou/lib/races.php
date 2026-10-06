<?php

declare(strict_types=1);

/**
 * Race groups of the Bleach setting.
 *
 * The race names are the keys of the $races array in racesystem/races.php.
 * Every race that is not listed here is a Shinigami variant (Rukongai,
 * Karakura, Noble, ...).
 */

/**
 * Group of a race: shinigami, arrancar, menos, quincy or none.
 *
 * @param string|null $race Race name; null means the current player
 */
function bleach_race_group(?string $race = null): string
{
    global $session;

    $race = sanitize($race ?? (string) ($session['user']['race'] ?? ''));

    return match ($race) {
        'Arrancar' => 'arrancar',
        'Menos' => 'menos',
        'Quincy' => 'quincy',
        '', '0', RACE_UNKNOWN => 'none',
        default => 'shinigami',
    };
}

/**
 * Hollows (Arrancar and Menos) live in Las Noches.
 */
function bleach_is_hollow(?string $race = null): bool
{
    return in_array(bleach_race_group($race), ['arrancar', 'menos'], true);
}

/**
 * Name of the Hollow city as stored in accounts.location.
 */
function bleach_lasnoches_city(): string
{
    $city = sanitize((string) (get_module_setting('villagename', 'lasnoches') ?? ''));

    return $city !== '' ? $city : 'Las Noches';
}

/**
 * The zanpakutou path a race can walk: "shinigami" (Shikai and Bankai),
 * "arrancar" (Resurrección) or "" for races without a zanpakutou.
 */
function bleach_zanpakutou_path(?string $race = null): string
{
    $group = bleach_race_group($race);

    return in_array($group, ['shinigami', 'arrancar'], true) ? $group : '';
}
