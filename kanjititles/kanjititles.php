<?php

declare(strict_types=1);

use Lotgd\Http;

/**
 * Kanji Titles
 *
 * At the Rock, Tetsubo writes a player's dragon kill title in kanji. The
 * choice is remembered and applied to every later title. Expects the titles
 * table to use the Bleach ranks listed in kanjititles_map().
 */

function kanjititles_getmoduleinfo(): array
{
    return [
        'name' => 'Kanji Titles',
        'version' => '2.0',
        'author' => '`2Oliver Brendel',
        'category' => 'Titles',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'prefs' => [
            'Kanji Titles,title',
            'japanese' => 'Show the dragon kill title in kanji,bool|0',
        ],
    ];
}

function kanjititles_install(): bool
{
    module_addhook('dragonkilltext');
    module_addhook_priority('setrace', INT_MAX);
    module_addhook('rock');

    return true;
}

function kanjititles_uninstall(): bool
{
    return true;
}

/**
 * Rōmaji title => kanji.
 *
 * @return array<string, string>
 */
function kanjititles_map(): array
{
    return [
        'Junior Student' => '院生',
        'Senior Student' => '上級院生',
        'Shinigami' => '死神',
        'Junior Officer' => '後輩死神',
        'Senior Officer' => '先輩死神',
        'Ranked Officer' => '席官',
        'Fukutaicho' => '副隊長',
        'Taicho' => '隊長',
        '`$S`4ō`$T`4aicho' => '総隊長',
    ];
}

/**
 * The player's standard dragon kill title, in Rōmaji or kanji.
 */
function kanjititles_title(bool $japanese): string
{
    global $session;

    require_once 'lib/titles.php';
    $title = (string) get_dk_title($session['user']['dragonkills'], $session['user']['sex']);

    return $japanese ? strtr($title, kanjititles_map()) : $title;
}

/**
 * Set the player's title if they still carry the standard one.
 */
function kanjititles_apply(bool $japanese): bool
{
    global $session;

    $current = (string) $session['user']['title'];
    if ($current !== kanjititles_title(false) && $current !== kanjititles_title(true)) {
        return false;
    }
    require_once 'lib/names.php';
    $title = kanjititles_title($japanese);
    $session['user']['name'] = change_player_title($title);
    $session['user']['title'] = $title;

    return true;
}

function kanjititles_dohook(string $hookname, array $args): array
{
    switch ($hookname) {
        case 'setrace':
        case 'dragonkilltext':
            if (get_module_pref('japanese')) {
                kanjititles_apply(true);
            }
            break;
        case 'rock':
            addnav('Tetsubo');
            addnav('Title in Kanji', 'runmodule.php?module=kanjititles&op=titles');
            break;
    }

    return $args;
}

function kanjititles_run(): void
{
    global $session;

    $self = 'runmodule.php?module=kanjititles';
    $name = '`gT`xe`gt`xsu`tbo';
    page_header('Tetsubo');
    addnav('Navigation');
    addnav('Back to the Rock', 'rock.php');
    addnav('Actions');

    switch ((string) Http::get('op')) {
        case 'changetitle':
            $japanese = Http::get('to') === 'kanji';
            if (kanjititles_apply($japanese)) {
                set_module_pref('japanese', $japanese ? 1 : 0);
                output('`yAll set! Your title is now %s`y. Come back any time...', $session['user']['title']);
            } else {
                output('`ySadly, you do not carry the standard title for your rank - a custom title takes priority.');
            }
            addnav('Back to the titles', "$self&op=titles");
            break;
        case 'overview':
            output('`yYou ask %s`y which Japanese titles exist and what they mean....`n`n', $name);
            rawoutput("<table cellpadding='3' cellspacing='0' border='0'><tr class='trhead'><td>"
                . htmlspecialchars(translate_inline('Rōmaji'), ENT_QUOTES) . '</td><td>'
                . htmlspecialchars(translate_inline('Kanji'), ENT_QUOTES) . '</td></tr>');
            $class = 'trdark';
            foreach (kanjititles_map() as $romaji => $kanji) {
                $class = $class === 'trlight' ? 'trdark' : 'trlight';
                rawoutput("<tr class='$class'><td>");
                output_notl('`@%s', $romaji);
                rawoutput('</td><td>');
                output_notl('`2%s', $kanji);
                rawoutput('</td></tr>');
            }
            rawoutput('</table>');
            addnav('Back to the titles', "$self&op=titles");
            break;
        default:
            output('`yYou ask %s`y about Japanese titles. Like most Japanese things in the game, your rank is written in Rōmaji, but you may switch it to its kanji at any time. This only affects your rank title - a custom title takes priority.`n`n', $name);
            $current = (string) $session['user']['title'];
            if ($current === kanjititles_title(false) && $current !== kanjititles_title(true)) {
                output('`yPreview:`n`n`iBefore`i: %s`n`y`iAfter`i: %s', $current, kanjititles_title(true));
                addnav('`xChange it to kanji', "$self&op=changetitle&to=kanji");
            } elseif ($current === kanjititles_title(true) && $current !== kanjititles_title(false)) {
                output('`yYour title is written in kanji: %s`y.', $current);
                addnav('`xChange it back to Rōmaji', "$self&op=changetitle&to=romaji");
            } else {
                output('`ySadly, you do not carry the standard title for your rank, or it has no kanji...');
            }
            addnav('Overview of available titles', "$self&op=overview");
    }
    page_footer();
}
