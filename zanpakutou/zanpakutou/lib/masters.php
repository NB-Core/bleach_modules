<?php

declare(strict_types=1);

/**
 * Zanpakutou masters: the sparring partners of the awakening fight who then
 * teach the player.
 *
 * The list order is the master id stored in the player's prefs, so append
 * new masters at the end.
 */

/**
 * Masters per path ("shinigami" or "arrancar"): [regular, special], each a
 * list of [name, gender, weapon].
 *
 * @return array{0: list<array{0: string, 1: int, 2: string}>, 1: list<array{0: string, 1: int, 2: string}>}
 */
function zanpakutou_masters(string $path): array
{
    if ($path === 'arrancar') {
        return [
            [
                ['Aaroniero Arruruerie', SEX_MALE, '`)Glotonería'],
                ['Luppi Antenor', SEX_FEMALE, '`^Trepadora'],
                ['Zommari Leroux', SEX_MALE, '`^Brujería'],
                ['Szayel Aporro Granz', SEX_MALE, '`lFornicarás'],
                ['Cirucci Sanderwicci', SEX_FEMALE, '`yGolondrina'],
            ],
            [
                ['Grimmjow Jaegerjaquez', SEX_MALE, '`%P`$antera'],
                ['Nnoitra Jiruga', SEX_MALE, '`)Santa `~Teresa'],
                ['Nelliel Tu Odelschwanck', SEX_FEMALE, '`%G`$a`%m`$u`%sa'],
                ['Tia Harribel', SEX_FEMALE, '`&T`giburón'],
                ['Baraggan Luisenbarn', SEX_MALE, '`3Arrogante'],
                ['Coyote Starrk', SEX_MALE, '`xL`gos `xL`gobos'],
                ['Yammy Riyalgo', SEX_MALE, '`xIra'],
                ['Ulquiorra Cifer', SEX_MALE, '`yMurciélago'],
                ['Kaname Tōsen', SEX_MALE, '`2Grillar Grillo'],
            ],
        ];
    }

    return [
        [
            ['Matsumoto Rangiku', SEX_FEMALE, '`)Haineko'],
            ['Hitsugaya Toushiro', SEX_MALE, '`^Hyōrinmaru'],
            ['Madarame Ikkaku', SEX_MALE, '`^Hōzukimaru'],
            ['Ukitake Jūshirō', SEX_MALE, '`lSōgyo no Kotowari'],
            ['Komamura Saijin', SEX_MALE, '`yT`4enken'],
        ],
        [
            ['Urahara Kisuke', SEX_MALE, '`%B`$enihime'],
            ['Kurosaki Ichigo', SEX_MALE, '`)Z`~angetsu'],
            ['Kuchiki Byakuya', SEX_MALE, '`%Z`$enbon`$z`%akura'],
            ['Kyōraku Shunsui', SEX_MALE, '`&K`gaten `&K`gyōkotsu'],
            ['Zaraki Kenpachi', SEX_MALE, '`3Blunt Zanpakutō'],
            ['Kurotsuchi Mayuri', SEX_MALE, '`xA`gshisogi `gJ`xizō'],
            ['Hinamori Momo', SEX_FEMALE, '`xTobiume'],
            ['Soifon', SEX_FEMALE, '`yS`vuzumebachi'],
            ['Shihōin Yoruichi', SEX_FEMALE, '???'],
        ],
    ];
}

/**
 * A master by id.
 *
 * @return array{name: string, gender: int, weapon: string, id: int, special: int}|null
 */
function zanpakutou_master(string $path, int $special, int $id): ?array
{
    $list = zanpakutou_masters($path)[$special ? 1 : 0];
    if (!isset($list[$id])) {
        return null;
    }
    [$name, $gender, $weapon] = $list[$id];

    return ['name' => $name, 'gender' => $gender, 'weapon' => $weapon, 'id' => $id, 'special' => $special ? 1 : 0];
}

/**
 * A random master; $specialChance percent come from the special list.
 *
 * @return array{name: string, gender: int, weapon: string, id: int, special: int}
 */
function zanpakutou_random_master(string $path, int $specialChance = 20): array
{
    $special = e_rand(1, 100) <= max(0, min(100, $specialChance)) ? 1 : 0;
    $list = zanpakutou_masters($path)[$special];

    return zanpakutou_master($path, $special, e_rand(0, count($list) - 1));
}
