<?php

declare(strict_types=1);

use Doctrine\DBAL\ParameterType;
use Lotgd\MySQL\Database;

/**
 * Hueco Mundo: the city of Las Noches, home of the Arrancar and Menos.
 *
 * Based on "City - Amwayr" by Billie Kennedy. Hollows start here (see
 * racesystem/races.php) and can always travel home; everybody else needs the
 * configured dragon kills and has to set out from one of the gate cities.
 */

function lasnoches_getmoduleinfo(): array
{
    return [
        'name' => 'Hueco Mundo (Las Noches)',
        'version' => '2.0',
        'author' => '`2Oliver Brendel',
        'category' => 'Cities',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'requires' => [
            'cities' => '1.0|Eric Stevens, part of the core download',
        ],
        'settings' => [
            'Las Noches Settings,title',
            'villagename' => 'Name of the city|`4L`)as `7N`$oches',
            'showforest' => 'Is the forest available from here?,bool|0',
            'travelfrom' => 'Gate city to Las Noches,location|' . getsetting('villagename', LOCATION_FIELDS),
            'travelto' => 'Second gate city to Las Noches,location|' . getsetting('villagename', LOCATION_FIELDS),
            'travelcost' => 'Travel points the trip to Las Noches costs,int|5',
            'mindk' => 'Dragon kills a non-Hollow needs to travel to Las Noches,int|20',
        ],
    ];
}

function lasnoches_install(): bool
{
    module_addhook('villagetext');
    module_addhook('village');
    module_addhook('travel');
    module_addhook('travel-cost');
    module_addhook('validlocation');
    module_addhook('moderate');
    module_addhook('changesetting');
    module_addhook('scrylocation');

    return true;
}

function lasnoches_uninstall(): bool
{
    global $session;

    $city = lasnoches_city();
    $capital = getsetting('villagename', LOCATION_FIELDS);
    lasnoches_move_players($city, $capital);
    if ($session['user']['location'] === $city) {
        $session['user']['location'] = $capital;
    }

    return true;
}

/**
 * Name of the city as stored in accounts.location (without colour codes).
 */
function lasnoches_city(): string
{
    $city = sanitize((string) get_module_setting('villagename', 'lasnoches'));

    return $city !== '' ? $city : 'Las Noches';
}

/**
 * Move every player from one location to another.
 */
function lasnoches_move_players(string $from, string $to): void
{
    Database::getDoctrineConnection()->executeStatement(
        'UPDATE ' . Database::prefix('accounts') . ' SET location = :to WHERE location = :from',
        ['to' => $to, 'from' => $from],
        ['to' => ParameterType::STRING, 'from' => ParameterType::STRING]
    );
}

function lasnoches_dohook(string $hookname, array $args): array
{
    global $session;

    $city = lasnoches_city();
    $here = $session['user']['location'] === $city;

    switch ($hookname) {
        case 'scrylocation':
            // Nobody can scry into Hueco Mundo.
            unset($args[$city]);
            break;
        case 'changesetting':
            if (($args['module'] ?? '') === 'lasnoches' && ($args['setting'] ?? '') === 'villagename') {
                $old = sanitize((string) $args['old']);
                $new = sanitize((string) $args['new']);
                lasnoches_move_players($old, $new);
                if ($session['user']['location'] === $old) {
                    $session['user']['location'] = $new;
                }
            }
            break;
        case 'validlocation':
            if (is_module_active('cities')) {
                $args[$city] = 'village-lasnoches';
            }
            break;
        case 'moderate':
            if (is_module_active('cities')) {
                tlschema('commentary');
                $args['lasnoches'] = sprintf_translate('%s', $city);
                tlschema();
            }
            break;
        case 'travel-cost':
            if (($args['to'] ?? '') === $city) {
                $args['cost'] = max((int) ($args['cost'] ?? 0), (int) get_module_setting('travelcost', 'lasnoches'));
            }
            break;
        case 'travel':
            if (!$here) {
                lasnoches_travelnav($city);
            }
            break;
        case 'villagetext':
            if ($here) {
                $args = lasnoches_villagetext($args, $city);
            }
            break;
        case 'village':
            if ($here) {
                tlschema($args['schemas']['gatenav'] ?? 'module-lasnoches');
                addnav($args['gatenav']);
                tlschema();
                addnav('Visit the Healing Faculty', 'healer.php?return=village.php');
                modulehook('eliteforest');
            }
            break;
    }

    return $args;
}

/**
 * Travel to Las Noches: Hollows always find their way home, others need
 * experience and one of the gate cities.
 */
function lasnoches_travelnav(string $city): void
{
    global $session;

    $hollow = false;
    if (is_file('modules/zanpakutou/lib/races.php')) {
        require_once 'modules/zanpakutou/lib/races.php';
        $hollow = bleach_is_hollow();
    }
    $location = $session['user']['location'];
    $gates = [(string) get_module_setting('travelfrom', 'lasnoches'), (string) get_module_setting('travelto', 'lasnoches')];
    $allowed = $hollow
        || ($session['user']['dragonkills'] >= (int) get_module_setting('mindk', 'lasnoches') && in_array($location, $gates, true));
    $link = 'runmodule.php?module=cities&op=travel&city=' . urlencode($city);

    tlschema('module-cities');
    if ($allowed) {
        addnav('More Dangerous Travel');
        addnav(['%s?Go to %s', substr($city, 0, 1), $city], "$link&d=1");
    }
    if ($session['user']['superuser'] & SU_EDIT_USERS) {
        addnav('Superuser');
        addnav(['%s?Go to %s', substr($city, 0, 1), $city], "$link&su=1");
    }
    tlschema();
}

/**
 * Village texts of Las Noches.
 */
function lasnoches_villagetext(array $args, string $city): array
{
    $texts = [
        'text' => '`$`c`@`bYou stand in the middle of Las Noches - the capital of the world of shadows and Hollows. Here, Arrancar reside and battle constantly to gain more strength.`b`c`n`nThe place looks deserted except for the few buildings known to belong to `$Aizen`@ as leader of the Espada.`n`nYou get the feeling you are surrounded by powerful beings that watch your every step.`n`n',
        'clock' => '`n`7Having no day or night cycle, you can only guess the time to be around `&%s`7.`n',
        'title' => ['%s', $city],
        'sayline' => 'whispers',
        'talk' => '`n`&You sense:`n',
        'newest' => '',
        'gatenav' => 'Hollow Gates',
        'fightnav' => 'Nearby Plains',
        'marketnav' => 'Market Square',
        'tavernnav' => 'Indulgence Lane',
        'infonav' => 'Espada Council',
    ];
    if (is_module_active('calendar')) {
        $texts['calendar'] = '`n`2Secret voices whisper it is `&%s`2, `&%s %s %s`2.`n';
    }
    foreach ($texts as $key => $value) {
        $args[$key] = $value;
        $args['schemas'][$key] = 'module-lasnoches';
    }
    $args['section'] = 'lasnoches';

    // Hueco Mundo has no use for the trappings of the living.
    blocknav('train.php');
    blocknav('pvp.php');
    blocknav('mercenarycamp.php');
    if (!get_module_setting('showforest', 'lasnoches')) {
        blocknav('forest.php');
    }
    foreach (['questbasics', 'house', 'klutz', 'abigail', 'crazyaudrey', 'zoo', 'beggarslane'] as $module) {
        if (is_module_active($module)) {
            blockmodule($module);
        }
    }

    return $args;
}

function lasnoches_run(): void
{
}
