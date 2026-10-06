<?php

declare(strict_types=1);

use Doctrine\DBAL\ParameterType;
use Lotgd\Forms;
use Lotgd\Http;
use Lotgd\MySQL\Database;
use Lotgd\Security\Escape;

/**
 * Zanpakutou (Bleach)
 *
 * Every Shinigami carries a zanpakutou, every Arrancar a Resurrección. This
 * module stores it, shows it in the bio, lets the player release it in
 * combat and gives admins an editor. Awakening and training happen in
 * zanpakutou_training.
 */

require_once 'modules/zanpakutou/lib/zanpakutou.php';

function zanpakutou_getmoduleinfo(): array
{
    return [
        'name' => 'Zanpakutou (Bleach)',
        'version' => '2.0',
        'author' => '`2Oliver Brendel',
        'category' => 'Zanpakutou',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'requires' => [
            'specialtysystem' => '1.03|Specialty System by Oliver Brendel (NB-Core/modules)',
        ],
        'settings' => [
            'Zanpakutou Settings,title',
            'Image Size restrictions,title',
            'restrictsize' => 'Is the size restricted?,bool|1',
            'maxwidth' => 'Max. width of the zanpakutou image (Pixel),range,20,400,20|200',
            'maxheight' => 'Max. height of the zanpakutou image (Pixel),range,20,400,20|200',
        ],
        'prefs' => [
            'Preferences Zanpakutou,title',
            'zanpakutou' => 'Zanpakutou data (JSON),viewonly',
        ],
    ];
}

function zanpakutou_install(): bool
{
    module_addhook('biostat');
    module_addhook('superuser');
    module_addhook('newday');
    module_addhook('fightnav-specialties');
    module_addhook('apply-specialties');

    return true;
}

function zanpakutou_uninstall(): bool
{
    return true;
}

function zanpakutou_dohook(string $hookname, array $args): array
{
    global $session;

    switch ($hookname) {
        case 'fightnav-specialties':
            zanpakutou_fightnav((string) ($args['script'] ?? ''));
            break;
        case 'apply-specialties':
            $skill = Http::get('skill');
            if ($skill === 'shikairelease' || $skill === 'bankairelease') {
                zanpakutou_release($skill === 'bankairelease');
            }
            break;
        case 'biostat':
            zanpakutou_biostat($args);
            break;
        case 'superuser':
            if (($session['user']['superuser'] & SU_MEGAUSER) == SU_MEGAUSER) {
                addnav('Zanpakutō');
                addnav('Zanpakutō Editor', 'runmodule.php?module=zanpakutou&op=editor');
            }
            break;
        case 'newday':
            // The zanpakutou is the weapon; a bought weapon keeps its damage
            // but takes the name of the blade.
            $zan = zanpakutou_load();
            if ($zan['name'] !== '' && $session['user']['weapon'] !== $zan['name']) {
                $session['user']['weapon'] = $zan['name'];
            }
            break;
    }

    return $args;
}

/**
 * Release navigation in combat.
 */
function zanpakutou_fightnav(string $script): void
{
    $zan = zanpakutou_load();
    if ($zan['name'] === '' || $zan['shikai']['text'] === '' || !is_module_active('specialtysystem')) {
        return;
    }
    require_once 'modules/specialtysystem/functions.php';
    $uses = specialtysystem_availableuses();
    $resource = function_exists('specialtysystem_resourcename') ? specialtysystem_resourcename() : 'Reiatsu';
    $hollow = bleach_zanpakutou_path() === 'arrancar';

    $canShikai = !zanpakutou_is_released() && $uses >= ZANPAKUTOU_SHIKAI_COST;
    $canBankai = $zan['bankai']['text'] !== '' && !has_buff('bankai') && $uses >= ZANPAKUTOU_BANKAI_COST;
    if (!$canShikai && !$canBankai) {
        return;
    }
    addnav($hollow ? 'Resurrección' : 'Zanpakutō');
    if ($canShikai) {
        addnav(
            ['Release: `$%s`) (%s %s)', $zan['shikai']['text'], ZANPAKUTOU_SHIKAI_COST, $resource],
            $script . 'op=fight&skill=shikairelease'
        );
    }
    if ($canBankai) {
        addnav(
            [$hollow ? 'Segunda Etapa: `$%s`) (%s %s)' : 'Final Release: `$%s`) (%s %s)', $zan['bankai']['text'], ZANPAKUTOU_BANKAI_COST, $resource],
            $script . 'op=fight&skill=bankairelease'
        );
    }
}

/**
 * Release the zanpakutou in combat, paying with specialty uses.
 */
function zanpakutou_release(bool $bankai): void
{
    global $session;

    $zan = zanpakutou_load();
    $release = $bankai ? $zan['bankai'] : $zan['shikai'];
    if ($zan['name'] === '' || $release['text'] === '' || !is_module_active('specialtysystem')) {
        return;
    }
    if ($bankai ? has_buff('bankai') : zanpakutou_is_released()) {
        return;
    }
    require_once 'modules/specialtysystem/functions.php';
    $cost = $bankai ? ZANPAKUTOU_BANKAI_COST : ZANPAKUTOU_SHIKAI_COST;
    if (specialtysystem_availableuses() < $cost) {
        output('`$You lack the strength to release your blade.`0`n`n');
        return;
    }

    output_notl('%s`4: "%s`4"...`n`n', $session['user']['name'], $release['text']);
    if ($bankai) {
        strip_buff('shikai');
    }
    apply_buff($bankai ? 'bankai' : 'shikai', zanpakutou_release_buff($zan, $bankai));
    specialtysystem_incrementuses('zanpakutou', $cost);
    set_module_pref('cache', '', 'specialtysystem');
}

/**
 * Zanpakutou lines on the bio page.
 *
 * @param array $target The bio target row (acctid, race, name, ...)
 */
function zanpakutou_biostat(array $target): void
{
    $zan = zanpakutou_load((int) $target['acctid']);
    if ($zan['name'] === '') {
        return;
    }
    $race = (string) ($target['race'] ?? '');
    [$firstRelease, $finalRelease] = zanpakutou_release_names($race);
    $hollow = bleach_zanpakutou_path($race) === 'arrancar';

    output($hollow ? '`^Resurrección: `@%s`0`n' : '`^Zanpakutō: `@%s`0`n', $zan['name']);
    if ($zan['image'] !== '' && $zan['image_validated']) {
        $style = '';
        if (get_module_setting('restrictsize', 'zanpakutou')) {
            $style = sprintf(
                " style='max-width:%dpx;max-height:%dpx'",
                (int) get_module_setting('maxwidth', 'zanpakutou'),
                (int) get_module_setting('maxheight', 'zanpakutou')
            );
        }
        rawoutput("<img src='" . Escape::html($zan['image']) . "' alt='" . Escape::html(sanitize($zan['name'])) . "'$style><br>");
    }
    if ($zan['shikai']['text'] !== '') {
        output('`^%s: `@%s`0 awakens with "%s`0"`n', translate_inline($firstRelease), $zan['shikai']['name'], $zan['shikai']['text']);
    }
    output('`^Type: `@%s`0, `^Form: `@%s`0`n', zanpakutou_type_label($zan), zanpakutou_form_label($zan));
    if ($zan['bankai']['name'] !== '') {
        $recent = $zan['bankai']['achieved'] !== ''
            && strtotime($zan['bankai']['achieved']) > strtotime('-10 days');
        if ($recent) {
            output('%s`$ has recently attained `b`i%s`i`b`0`n', $target['name'], translate_inline($finalRelease));
        } else {
            output('%s`$ has attained `b`i%s`i`b`0`n', $target['name'], translate_inline($finalRelease));
        }
    }
}

function zanpakutou_run(): void
{
    $op = Http::get('op');
    if ($op === 'editor') {
        zanpakutou_editor();
    }
}

/**
 * Admin editor for any player's zanpakutou.
 */
function zanpakutou_editor(): void
{
    check_su_access(SU_MEGAUSER);
    page_header('Zanpakutō Editor');
    require_once 'lib/superusernav.php';
    superusernav();
    addnav('Actions');
    addnav('Find a Zanpakutō', 'runmodule.php?module=zanpakutou&op=editor');

    $subop = (string) Http::get('subop');
    $target = (int) Http::get('target');
    $self = 'runmodule.php?module=zanpakutou&op=editor';

    switch ($subop) {
        case 'save':
            if ($target <= 0) {
                output('`$No player selected.`0');
                break;
            }
            if (Forms::isUnverifiedRequest()) {
                output('`$The form expired, please try again.`0');
                break;
            }
            zanpakutou_save(zanpakutou_editor_collect(zanpakutou_load($target)), $target);
            output('`@The zanpakutō has been saved.`0`n`n');
            // Show the form again with the stored values.
            // no break
        case 'edit':
            if ($target <= 0) {
                output('`$No player selected.`0');
                break;
            }
            zanpakutou_editor_form($target, "$self&subop=save&target=$target");
            break;
        default:
            zanpakutou_editor_search($self);
    }
    page_footer();
}

/**
 * Apply the posted editor fields to a zanpakutou.
 */
function zanpakutou_editor_collect(array $zan): array
{
    $text = static fn (string $field, int $max = 100): string => zanpakutou_clean_text(Http::post($field), $max);

    $zan['name'] = $text('name');
    $zan['type'] = array_key_exists((int) Http::post('type'), zanpakutou_types()) ? (int) Http::post('type') : 0;
    $zan['form'] = array_key_exists((int) Http::post('form'), zanpakutou_forms()) ? (int) Http::post('form') : 0;
    $zan['powerlevel'] = (int) Http::post('powerlevel');
    $image = trim((string) Http::post('image'));
    $zan['image'] = preg_match('#^https?://#i', $image) === 1 ? mb_substr($image, 0, 255) : '';
    $zan['image_validated'] = (bool) Http::post('image_validated');
    foreach (['shikai', 'bankai'] as $release) {
        $zan[$release]['name'] = $text($release . '_name');
        $zan[$release]['text'] = $text($release . '_text', 200);
        $achieved = trim((string) Http::post($release . '_achieved'));
        $zan[$release]['achieved'] = ($achieved === '' || strtotime($achieved) === false) ? '' : date('Y-m-d H:i:s', strtotime($achieved));
    }

    return $zan;
}

/**
 * Editor form for one player.
 */
function zanpakutou_editor_form(int $target, string $action): void
{
    $zan = zanpakutou_load($target);
    $enum = static function (array $options): string {
        $list = ',0,' . translate_inline('unknown');
        foreach ($options as $key => $label) {
            $list .= ',' . $key . ',' . translate_inline($label);
        }

        return $list;
    };

    addnav('', $action);
    rawoutput("<form action='" . Escape::html($action) . "' method='POST'>");
    require_once 'lib/showform.php';
    showform(
        [
            'Zanpakutō Properties,title',
            'name' => 'Name of the weapon,text',
            'form' => 'Form of the weapon,enum' . $enum(zanpakutou_forms()),
            'type' => 'Type of the weapon,enum' . $enum(zanpakutou_types()),
            'powerlevel' => 'Power level,range,1,' . ZANPAKUTOU_MAX_POWERLEVEL . ',1',
            'image' => 'Image URL (http/https),text',
            'image_validated' => 'Is the image validated?,bool',
            'Shikai / Resurrección,title',
            'shikai_name' => 'Name,text',
            'shikai_text' => 'Release phrase,text',
            'shikai_achieved' => 'Achieved on (YYYY-MM-DD HH:MM:SS),text',
            'Bankai / Segunda Etapa,title',
            'bankai_name' => 'Name,text',
            'bankai_text' => 'Release phrase,text',
            'bankai_achieved' => 'Achieved on (YYYY-MM-DD HH:MM:SS),text',
        ],
        [
            'name' => $zan['name'],
            'form' => $zan['form'],
            'type' => $zan['type'],
            'powerlevel' => $zan['powerlevel'],
            'image' => $zan['image'],
            'image_validated' => $zan['image_validated'] ? 1 : 0,
            'shikai_name' => $zan['shikai']['name'],
            'shikai_text' => $zan['shikai']['text'],
            'shikai_achieved' => $zan['shikai']['achieved'],
            'bankai_name' => $zan['bankai']['name'],
            'bankai_text' => $zan['bankai']['text'],
            'bankai_achieved' => $zan['bankai']['achieved'],
        ]
    );
    rawoutput('</form>');
}

/**
 * Player search for the editor.
 */
function zanpakutou_editor_search(string $self): void
{
    $limit = 100;
    $term = trim((string) Http::post('term'));
    output('`c`b`tFind User`0`b`c`n`n');

    if ($term !== '') {
        $like = '%' . addcslashes($term, '%_\\') . '%';
        $rows = Database::getDoctrineConnection()->executeQuery(
            'SELECT acctid, name FROM ' . Database::prefix('accounts')
            . ' WHERE name LIKE :term OR login LIKE :term ORDER BY login LIMIT ' . $limit,
            ['term' => $like],
            ['term' => ParameterType::STRING]
        )->fetchAllAssociative();
        if ($rows === []) {
            output('`$Sorry, I was unable to find anybody with that name!`0`n`n');
        } else {
            rawoutput("<table cellpadding='3' cellspacing='0' border='0'><tr class='trhead'><td>" . Escape::html(translate_inline('Name')) . '</td></tr>');
            $class = 'trdark';
            foreach ($rows as $row) {
                $class = $class === 'trlight' ? 'trdark' : 'trlight';
                $link = "$self&subop=edit&target=" . (int) $row['acctid'];
                addnav('', $link);
                rawoutput("<tr class='$class'><td><a href='" . Escape::html($link) . "'>");
                output_notl('%s', $row['name']);
                rawoutput('</a></td></tr>');
            }
            rawoutput('</table>');
        }
    }
    output('`xWhom are you looking for? Enter part of the login or name (at most %s hits).`0`n`n', $limit);
    addnav('', $self);
    rawoutput("<form action='" . Escape::html($self) . "' method='POST'>"
        . "<input name='term' size='40' value='" . Escape::html($term) . "'> "
        . "<input type='submit' class='button' value='" . Escape::html(translate_inline('Search')) . "'></form>");
}
