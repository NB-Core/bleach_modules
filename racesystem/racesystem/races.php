<?php

declare(strict_types=1);

/**
 * Bleach races for the racesystem module of NB-Core/modules.
 *
 * Deploy this file as modules/racesystem/races.php; it replaces the races
 * shipped with racesystem. The engine includes it on every hook call, so it
 * must only define data (no functions or constants).
 *
 * Keys are the race names stored in accounts.race. The zanpakutou library
 * (zanpakutou/lib/races.php) groups them: Arrancar, Menos and Quincy by
 * name, every other race is a Shinigami.
 *
 * Mind colour codes in names: they get sanitized, so no two races may share
 * the same name without colours.
 */

$capital = (string) get_module_setting('worldname', 'racesystem');
$rukongai = 'Rukongai';
$karakura = 'Karakura';
$lasnoches = sanitize((string) (get_module_setting('villagename', 'lasnoches') ?? ''));
if ($lasnoches === '') {
    $lasnoches = 'Las Noches';
}

$races = [
    'Shinigami' => [
        'id' => 1,
        'city' => $capital,
        'racedesc' => 'In %s</a>, home to `)Shinigami`0 who were born or brought to this world to serve within the 13 protection squads, to bring justice to the living world and to relieve souls.`n`n',
        'setracedesc' => '`gAs a Shinigami, you get your standard salary of 50 gold pieces each new day!`n`n',
        'raceevalnewday' => "\$session['user']['gold'] += 50;",
        'colour' => '`~',
        'name' => '`~Shinigami',
    ],
    'Quincy' => [
        'id' => 12,
        'city' => $karakura,
        'racedesc' => 'In %s</a>`), you were born as a descendant of the `%Q`euincy`). Being able to sense the unnatural increase of Hollows, you set out to eradicate them while avoiding the Shinigami.`n`n',
        'setracedesc' => '`3As you are an expert in hunting down rogue Hollows, you gain one travel point every day as well as 1 attack point!`n`n',
        'raceeval' => "\$session['user']['attack'] += 1;",
        'raceevalnewday' => "if (is_module_active('cities')) { increment_module_pref('traveltoday', -1, 'cities'); output('`n`nBeing a `%Q`euincy`0, you get 1 more travel for today!`n'); }",
        'colour' => '`%',
        'name' => '`%Q`euincy',
    ],
    'Menos' => [
        'id' => 13,
        'city' => $lasnoches,
        'racedesc' => 'In %s</a>`), you are a higher form of Hollow called a `$M`4eno`)s. You can attain even more power by devouring your fellow Hollows!`n`n',
        'setracedesc' => '`3As a Menos you roam quite a bit: you gain one travel point every day as well as 1 defense point!`n`n',
        'raceeval' => "\$session['user']['defense'] += 1;",
        'raceevalnewday' => "if (is_module_active('cities')) { increment_module_pref('traveltoday', -1, 'cities'); output('`n`nBeing a `\$Menos`0, you get 1 more travel for today!`n'); }",
        'colour' => '`$',
        'name' => '`$M`4eno`)s',
    ],
    'Noble Shinigami' => [
        'id' => 2,
        'city' => $capital,
        'racedesc' => 'In %s</a>, home to `)Shinigami`0 who were born there to live up to their highest ideals.`n`n',
        'setracedesc' => '`lAs a noble Shinigami, you roam more easily through the realms and enjoy quite some advantages!`n`n`vYou gain one free travel every new day!',
        'raceevalnewday' => "if (is_module_active('cities')) { increment_module_pref('traveltoday', -1, 'cities'); output('`n`nBeing `%noble`0, you get 1 more travel for today!`n'); }",
        'colour' => '`)',
        'name' => '`)Noble `~Shinigami',
        'requirements' => [
            'dks' => 5,
            'alignment' => 'LX',
        ],
    ],
    'Rukongai Shinigami' => [
        'id' => 3,
        'city' => $rukongai,
        'racedesc' => 'In %s</a>, you have worked your way up to being a Shinigami... you have no noble heritage and no royal blood, but you have learned to survive your own way.`n`n',
        'setracedesc' => '`lNot being a noble Shinigami, you had to learn things the hard way.`n`n`vYou get 1 attack point!',
        'raceeval' => "\$session['user']['attack']++;",
        'text' => ['`#`c`b%s, home of most other people in Soul Society and the last place to go for the souls of the deceased.`b`c`n`3Beside %s lies the ancient Seireitei that has been ruled by the 13 divisions for centuries.`n', $rukongai, $rukongai],
        'clock' => '`n`3Time does not really matter here, but you can sense it is `#%s`3.`n',
        'calendar' => '`n`3The current date is `#Year %4$s`3, `#%3$s %2$s`3.`nThe current weekday in the living world might be `#%1$s`3.`n`n',
        'title' => ['%s', $rukongai],
        'sayline' => 'converses',
        'talk' => '`n`#Nearby some villagers converse:`n',
        'younewest' => '`n`3Being rather new to this life, you look around in amazement and see experienced Shinigami pass by.',
        'newest' => '`n`3Standing amidst a crowd of people is `#%s`3, obviously fresh from training.',
        'gatenav' => 'Celestial Gates',
        'fightnav' => "Wanderer's Lane",
        'marketnav' => 'Shop Lane',
        'tavernnav' => 'Leisure Lane',
        'colour' => '`v',
        'name' => '`4R`)ukongai `~Shinigami',
        'requirements' => [
            'dks' => 3,
            'alignment' => '!E',
        ],
    ],
    'Rukongai Brute' => [
        'id' => 4,
        'city' => $rukongai,
        'racedesc' => 'In %s</a>, you have worked your way up to being a Shinigami... you have no noble heritage and no royal blood, but you have learned to survive your own way... with fierce force.`n`n',
        'setracedesc' => '`lNot being a noble Shinigami and liking it rough, you learned things the `bvery`b hard way.`n`n`vYou get 2 attack points!',
        'raceeval' => "\$session['user']['attack'] += 2;",
        'colour' => '`$',
        'name' => '`4R`)ukongai `gBrute',
        'requirements' => [
            'dks' => 7,
            'alignment' => 'XE',
        ],
    ],
    'Rukongai Shadowmaster' => [
        'id' => 5,
        'city' => $rukongai,
        'racedesc' => 'In %s</a>, you have hidden in the shadows and avoided confrontation. Yet you have seen too many poor people for the rest of your life and stepped up to be a Shinigami. Kindness is not your middle name.`n`n',
        'setracedesc' => '`3Your talent for kidō lets you draw on `^3`3 more reiatsu points every day!`n`n',
        'raceevalnewday' => "if (is_module_active('specialtysystem')) { increment_module_pref('uses', -3, 'specialtysystem'); output('`n`3Being a Shadowmaster, you gain `^3`3 extra reiatsu points today.`n'); }",
        'colour' => '`~',
        'name' => '`4R`)ukongai `3Shadowmaster',
        'requirements' => [
            'dks' => 12,
            'alignment' => '!G',
        ],
    ],
    'Rukongai Loyalist' => [
        'id' => 6,
        'city' => $rukongai,
        'racedesc' => 'In %s</a>, you have tried to be on the bright side of life and on the side of justice. You helped others and received plenty of honours.`n`n',
        'setracedesc' => '`3Having fought many fights to protect the people of Rukongai, you gain 2 defense points!`n`n',
        'raceeval' => "\$session['user']['defense'] += 2;",
        'colour' => '`^',
        'name' => '`4R`)ukongai `3Loyalist',
        'requirements' => [
            'dks' => 15,
            'alignment' => 'LX',
        ],
    ],
    'Rukongai Benefactor' => [
        'id' => 10,
        'city' => $rukongai,
        'racedesc' => 'In %s</a>, you have tried to be on the bright side of life and were often in conflict with the law when it came to your own moral code. You helped others and received plenty of kudos from poor people.`n`n',
        'setracedesc' => '`3Having fought many fights to protect the people of Rukongai, you gain 2 defense points and one travel every day (running away while carrying others makes you run faster when alone)!`n`n',
        'raceeval' => "\$session['user']['defense'] += 2;",
        'raceevalnewday' => "if (is_module_active('cities')) { increment_module_pref('traveltoday', -1, 'cities'); output('`n`nBeing `va benefactor`0, you get 1 more travel for today!`n'); }",
        'colour' => '`%',
        'name' => '`4R`)ukongai `vBenefactor',
        'requirements' => [
            'dks' => 15,
            'alignment' => 'XG',
        ],
    ],
    'Karakura Shinigami' => [
        'id' => 7,
        'city' => $karakura,
        'racedesc' => 'In %s</a>, you lived your life pretty normally... until you heard whispers and voices... only to discover you could hear and see the souls of the deceased. Along with that, you had to defend yourself as Hollows tracked you down... You have a Zanpakutō that helps you defend yourself, though you are only at the early stages of using it.`n`n',
        'setracedesc' => '`lAs you have had to fight unusually hard, you are more proficient in fighting but less proficient in using reiatsu.`n`n`vYou get 1 attack and 1 defense point!',
        'raceeval' => "\$session['user']['attack']++; \$session['user']['defense']++;",
        'raceevalnewday' => "if (is_module_active('specialtysystem')) { increment_module_pref('uses', 2, 'specialtysystem'); output('`n`3You lose `\$2 reiatsu points`3 for being a Karakura Shinigami.`n'); }",
        'text' => ['`%`b%s, home of the living and of some others who travel between the living and the dead. You, for instance, see much more than normal people do...`b`n', $karakura],
        'clock' => '`n`4A clock nearby tells you it is `#%s`4.`n',
        'calendar' => '`n`4The current date is `#Year %4$s`4, `#%3$s %2$s`4.`nThe current weekday in the living world is `#%1$s`4.`n`n',
        'title' => ['%s', $karakura],
        'sayline' => 'chats',
        'talk' => '`n`#Nearby some people converse:`n',
        'younewest' => '`n`3Being rather new to this life, you look around in amazement and see experienced Shinigami pass by.',
        'newest' => '`n`4Standing amidst a crowd of people is `#%s`4, obviously fresh from the fight.',
        'gatenav' => 'Celestial Gates',
        'fightnav' => 'Fight Street',
        'marketnav' => 'Shopping District',
        'tavernnav' => 'Entertainment District',
        'colour' => '`&',
        'name' => '`gK`)arakura `~Shinigami',
        'requirements' => [
            'dks' => 20,
            'alignment' => '!E',
        ],
    ],
    'Karakura Elementist' => [
        'id' => 8,
        'city' => $karakura,
        'racedesc' => 'In %s</a>, you lived your life pretty normally... until you heard whispers and voices... only to discover you could hear and see the souls of the deceased. `$Yet`0 you did not fight them; you concealed your appearance and got used to your powers protecting you.`n`n',
        'setracedesc' => '`lYou had to keep yourself out of the line of fire and can soften an enemy\'s attack.`n`n`vYou have inherited a natural aura concealing you!',
        'raceevalnewday' => "apply_buff('karakuraaura', ['name' => '`%Elementist Aura`0', 'rounds' => -1, 'badguydmgmod' => 0.85, 'allowinpvp' => 1, 'allowintrain' => 1, 'schema' => 'module-racesystem']);",
        'colour' => '`l',
        'name' => '`gK`)arakura `%Elementist',
        'requirements' => [
            'dks' => 20,
            'alignment' => '!E',
        ],
    ],
    'Karakura Advocatus' => [
        'id' => 9,
        'city' => $karakura,
        'racedesc' => 'In %s</a>, you lived your life pretty normally... until you heard whispers and voices... only to discover you could hear and see the souls of the deceased. `lYet`1 you did not fight them, but allied with them to harvest other souls... to save yours. You found quite some passion for it...`n`n',
        'setracedesc' => '`gYou had to keep yourself out of the line of fire and can empower your attack to swiftly bring down an enemy Shinigami.`n`n`vYou have inherited an unnatural aura aiding you!',
        'raceevalnewday' => "apply_buff('karakuraaura2', ['name' => '`\$Advocati Aura`0', 'rounds' => -1, 'atkmod' => 1.15, 'allowinpvp' => 1, 'allowintrain' => 1, 'schema' => 'module-racesystem']);",
        'colour' => '`t',
        'name' => '`gK`)arakura `~A`vdvocatus',
        'requirements' => [
            'dks' => 25,
            'alignment' => 'XE',
        ],
    ],
    'Arrancar' => [
        'id' => 11,
        'city' => $lasnoches,
        'racedesc' => 'In %s</a>`), you were created as an `$A`)rrancar`), striving to acquire more power every day. You constantly fight to survive against your superiors and gather enough souls by devouring lowly Hollows.`n`n',
        'setracedesc' => '`3As you are an expert in entering other worlds, you gain one travel point every day as well as 1 defense and 1 attack point!`n`n',
        'raceeval' => "\$session['user']['defense'] += 1; \$session['user']['attack'] += 1;",
        'raceevalnewday' => "if (is_module_active('cities')) { increment_module_pref('traveltoday', -1, 'cities'); output('`n`nBeing an `vArrancar`0, you get 1 more travel for today!`n'); }",
        'colour' => '`)',
        'name' => '`$A`)rrancar',
        'requirements' => [
            'dks' => 40,
            'alignment' => '!G',
        ],
    ],
];

// Keys the engine reads without checking. A race without "text" has no
// village of its own: the capital, Karakura and Rukongai are rendered by the
// race that defines the text, Las Noches by the lasnoches module.
$races = array_map(
    static fn (array $race): array => $race + [
        'raceeval' => '',
        'raceevalnewday' => '',
        'text' => '',
        'stablename' => '',
    ],
    $races
);
