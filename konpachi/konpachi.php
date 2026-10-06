<?php

declare(strict_types=1);

use Lotgd\Http;

/**
 * Meet Konpachi: Kon dressed up as Zaraki Kenpachi plays trick or treat in
 * one city. Based on "Azrael the Spook" by Shannon Brown.
 */

function konpachi_getmoduleinfo(): array
{
    return [
        'name' => 'Meet Konpachi',
        'version' => '2.0',
        'author' => 'Azrael the Spook by Shannon Brown, modified by `2Oliver Brendel',
        'category' => 'Village Specials',
        'download' => 'https://github.com/NB-Core/bleach_modules',
        'settings' => [
            'Konpachi - Settings,title',
            'konpachiloc' => 'Where does Zaraki Konpachi appear,location|' . getsetting('villagename', LOCATION_FIELDS),
        ],
    ];
}

function konpachi_install(): bool
{
    module_addhook('changesetting');
    module_addeventhook('village', "require_once 'modules/konpachi.php'; return konpachi_test();");

    return true;
}

function konpachi_uninstall(): bool
{
    return true;
}

function konpachi_dohook(string $hookname, array $args): array
{
    if ($hookname === 'changesetting' && ($args['setting'] ?? '') === 'villagename'
        && ($args['old'] ?? null) === get_module_setting('konpachiloc', 'konpachi')) {
        set_module_setting('konpachiloc', $args['new'], 'konpachi');
    }

    return $args;
}

/**
 * Event chance: Konpachi only haunts his own city.
 */
function konpachi_test(): int
{
    global $session;

    return $session['user']['location'] === get_module_setting('konpachiloc', 'konpachi') ? 100 : 0;
}

function konpachi_runevent(string $type, string $link = 'village.php?'): void
{
    global $session;

    $u = &$session['user'];
    $u['specialinc'] = '';
    $city = (string) get_module_setting('konpachiloc', 'konpachi');
    $name = '`~Z`)araki `qKon`gpachi';
    $op = (string) Http::get('op');
    require_once 'lib/partner.php';
    $partner = get_partner();

    $showImage = !is_module_active('addimages') || get_module_pref('user_addimages', 'addimages');
    if ($showImage) {
        rawoutput("<div style='text-align:center'><img alt='Konpachi' src='modules/konpachi/kon.jpg'></div><br>");
    }

    switch ($op) {
        case '':
            $u['specialinc'] = 'module:konpachi';
            output('`7While you are exploring %s, you are approached by a one-foot-tall, walking... erm... %s.`n`n', $city, $name);
            output('"`&TRICK OR TREAT!`7" he screams in a very deep and threatening voice.`n`n');
            output('You survey him, trying to decide whether Kon is playing a prank or what is going on...');
            if (is_module_active('ghosttown') && $city === get_module_setting('villagename', 'ghosttown')) {
                output('After all, this strange town seems genuinely spooky.');
            }
            output('You cannot help wondering whether there is a konpaku in there, or something far more sinister.`n`n');
            addnav('Trick or Treat');
            if ($u['gold'] > 0) {
                addnav('Treat (give him 1 gold)', $link . 'op=treat');
                output('You could give him some gold...`n`n');
            } else {
                output('You do not have any gold to give him...`n`n');
            }
            output('You could risk letting him play a trick on you... or just ignore him and walk away. What will you do?');
            addnav('Trick (let him play a trick)', $link . 'op=trick');
            addnav(['Ignore %s', $name], $link . 'op=ignore');
            break;
        case 'ignore':
            output('`7You are really not in the mood to let some bratty stuffed animal with an eyepatch demand gold from you, so you turn your back on him and walk away.`n`n');
            output('`7Seconds later you find yourself sprawled on the ground as an immense amount of reiatsu pushes you down... and %s`7 trips you over.', $name);
            output('`7You hear low-pitched laughter, and several nearby visitors smother their laughter behind their hands.`n`n');
            output('`7If only %s`7 could see you now.', $partner);
            output('`7You are rather banged up, and a face full of gravel is not very attractive!`n`n');
            output('`7You `$lose`7 some charm and some of your hitpoints!`n');
            if ($u['charm'] > 0) {
                $u['charm']--;
            }
            if ($u['hitpoints'] > 1) {
                $u['hitpoints'] = (int) round($u['hitpoints'] * 0.8);
            }
            break;
        case 'trick':
            output('`7You are really not in the mood to let some bratty stuffed animal demand gold from you, so you let him play a trick.`n`n');
            output('After all, how bad can a little stuffed animal be?`n`n');
            $bad = e_rand(1, 5);
            if ($bad === 1) {
                output('He stands very still, and his unpatched eye locks on yours. It is more than a little spooky.');
                output('You are starting to feel seriously unnerved, but you wonder whether he is actually going to `bdo`b anything.`n`n');
                output('When you have almost given up waiting, you feel a warm sensation in your hair.`n`n');
                output('As it dawns on you that he has a helper, you catch wind of the concoction being poured over you. Its foul odor is breathtaking.`n`n');
                output('You really got tricked by this little buddy and his... erm... baby `RY`tachiru`7 giggling behind you...`n');
                if ($u['charm'] > 1) {
                    $u['charm'] -= 2;
                    output('`7You `$lose`7 charm!');
                }
                apply_buff('konpachi', [
                    'name' => '`@Trickery Stench',
                    'rounds' => 60,
                    'wearoff' => 'The stench begins to fade.',
                    'defmod' => 1.03,
                    'survivenewday' => 1,
                    'roundmsg' => 'The stench of rotten eggs helps to repel your attacker.',
                    'schema' => 'module-konpachi',
                ]);
            } elseif ($bad === 2) {
                output('A low and guttural moan emanates from the stuffed animal. Chuckling at him, you turn and walk away.`n`n');
                output('You do not get very far before he begins to speak slowly, in a dead voice, about stomping you flat.`n`n');
                output('The hairs on your neck stand on end, and within seconds your legs stiffen, then your arms.`n`n');
                output('`$You are paralyzed by an immense amount of reiatsu!`n`n');
                output('`7You are frozen helplessly on the spot as the creature rifles through your purse, saying you are too weak to even be stomped and he is only interested in strong ones.');
                $takegold = min((int) $u['gold'], (int) $u['level'] * 3);
                $takegems = min((int) $u['gems'], (int) ceil(($u['level'] + 1) / 5));
                if ($takegold === 0 && $takegems === 0) {
                    output('He grunts in disgust at finding it empty.');
                }
                if ($takegold > 0) {
                    output('He helps himself to `^%s gold`7.`n', $takegold);
                    $u['gold'] -= $takegold;
                }
                if ($takegems > 0) {
                    output('`7He also takes `5%s gems`7 before wandering away.`n', $takegems);
                    $u['gems'] -= $takegems;
                }
                debuglog("lost $takegold gold and $takegems gems to Konpachi");
                output('`nAfter a few minutes you are able to begin painfully shifting your aching muscles again.`n');
            } else {
                output('A low and guttural moan emanates from him. Chuckling, you reach out a hand to pat his shoulder.`n`n');
                output('Just as you do, he screams "`@`bB`~an...`@K`~ai!!!!!!`b`7" at the top of his lungs.`n`n');
                output('You almost jump out of your skin! Seeing a Bankai at such close range might be lethal, regardless of how... small... the opponent is.');
                output('Visitors laugh at your reaction, and you feel very embarrassed. If only %s`7 could see you now.`n`n', $partner);
                output('`7You `$lose`7 charm!`n');
                if ($u['charm'] > 0) {
                    $u['charm']--;
                }
            }
            break;
        case 'treat':
            if ($u['gold'] < 1) {
                output('`7You search your pockets, but there is no gold left to give.');
                break;
            }
            output('`7You are in the mood for being nice, so you hand over a piece of gold.`n`n');
            output('%s`7 takes it and runs off to his friends Ririn and the others to show them the gold.`n`n', $name);
            output('You feel really good about his reaction.`n');
            $u['gold']--;
            apply_buff('konpachi', [
                'name' => '`#Feelgood Vibes',
                'rounds' => 60,
                'wearoff' => 'You float back down to earth.',
                'atkmod' => 1.03,
                'survivenewday' => 1,
                'roundmsg' => 'Your good mood helps you hit harder.',
                'schema' => 'module-konpachi',
            ]);
            break;
    }
}
