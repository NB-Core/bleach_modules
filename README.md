# bleach_modules

Bleach-themed modules for [NB-Core/lotgd](https://github.com/NB-Core/lotgd) 2.x
(PHP 8.3+): Shinigami and Hollows, zanpakutō with Shikai and Bankai,
Arrancar with Resurrección, Las Noches and the training grounds of both sides.

The race and specialty engines are **not** part of this repository. Use the
maintained ones from [NB-Core/modules](https://github.com/NB-Core/modules)
(`systems/racesystem`, `systems/specialtysystem`); this repository only
provides the Bleach content for them.

## Requirements

| What | Where |
|---|---|
| NB-Core/lotgd 2.x, PHP 8.3+ | [NB-Core/lotgd](https://github.com/NB-Core/lotgd) |
| `cities` | core module of NB-Core/lotgd |
| `specialtysystem` ≥ 1.03 | NB-Core/modules, `systems/specialtysystem` |
| `racesystem` ≥ 1.01 | NB-Core/modules, `systems/racesystem` |
| `alignment` (optional) | NB-Core/modules, `systems/alignment`; enables the alignment rules of the races |

## Installation

Folders in this repository only group the modules. Copy every `*.php` file
into the game's `modules/` directory and every folder named like a module
(`zanpakutou/zanpakutou/`, `konpachi/konpachi/`) to `modules/<name>/`:

| Repository path | Game path |
|---|---|
| `zanpakutou/zanpakutou.php`, `zanpakutou/zanpakutou_training.php` | `modules/` |
| `zanpakutou/zanpakutou/` (library and images) | `modules/zanpakutou/` |
| `specialtysystem/specialtysystem_*.php` | `modules/` |
| `racesystem/racesystem/races.php` | `modules/racesystem/races.php` (**replaces** the races shipped with racesystem) |
| `training/`, `arrancar/`, `lasnoches/`, `kanjititles/`, `konpachi/` | `modules/` (plus `konpachi/konpachi/` → `modules/konpachi/`) |

Install and activate in this order:

1. `cities`, `specialtysystem`, `racesystem` (and `alignment` if wanted)
2. `lasnoches`
3. `zanpakutou`
4. `specialtysystem_reiatsu`, `specialtysystem_zanpakutou`, `specialtysystem_arrancar`
5. `training_bleach`, `zanpakutou_training`, `arrancar_train`
6. `kanjititles`, `konpachi`

Then refresh the specialty registry: *Grotto → Mechanics → Refresh Specialty
System Add-Ons*.

### Settings

- `racesystem` → *worldname*: the name of your capital (e.g. `Seireitei`);
  Shinigami and Noble Shinigami live there.
- `specialtysystem` → *resourcename*: `Reiatsu`.
- `lasnoches` → *villagename* names the Hollow city; Arrancar and Menos start
  there.
- `kanjititles` expects the dragon kill titles to be the Bleach ranks
  (Junior Student … Sōtaicho, see `kanjititles_map()`).

## Modules

| Module | What it does | Requires |
|---|---|---|
| `racesystem/races.php` | 13 races: Shinigami (Noble, Rukongai, Karakura variants), Quincy, Menos, Arrancar | racesystem |
| `zanpakutou` | The blade: bio, release in combat (Shikai/Resurrección, Bankai/Segunda Etapa), admin editor. Shared library in `zanpakutou/lib/` | specialtysystem |
| `specialtysystem_reiatsu` | Reiatsu techniques, the specialty every race can pick | specialtysystem |
| `specialtysystem_zanpakutou` | One elemental technique per zanpakutō type (fire, water, wind, ice, lightning), usable while released | specialtysystem, zanpakutou |
| `specialtysystem_arrancar` | Bala, Sonido, Hierro, Cero, Gran Rey Cero - Arrancar only | specialtysystem |
| `zanpakutou_training` | Forest awakening of the blade, the master's lessons, Urahara's Bankai training below the lodge | zanpakutou, training_bleach |
| `training_bleach` | Training grounds of Shinigami (from `train.php`) and Hollows (from the Espada training) | zanpakutou |
| `arrancar_train` | Espada masters and level-up for Hollows in Las Noches (own table `masters_arrancar`) | lasnoches |
| `lasnoches` | The city of Las Noches in Hueco Mundo, travel for Hollows and experienced visitors | cities |
| `kanjititles` | Shows the rank title in kanji at the Rock | - |
| `konpachi` | Village event: Kon as Kenpachi plays trick or treat | - |

### Hooks fired by these modules

| Hook | Fired by | Arguments |
|---|---|---|
| `traininggrounds` | training_bleach (Shinigami grounds) | `[]` |
| `arrancargrounds` | training_bleach (Hollow grounds) | `[]` |
| `arrancar-train` | arrancar_train, page start | `[]` |
| `arrancar-footer` | arrancar_train, page end | `[]` |
| `eliteforest` | lasnoches, village navigation | `[]` |
| `training-costs-o`, `training-costs-d` | training_bleach | `['user' => …, 'cost' => [level => gold]]` |

## Development

```sh
for f in $(git ls-files '*.php'); do php -l "$f"; done
```

GitHub Actions lints every file on PHP 8.3 and 8.4 and rejects byte order
marks and closing `?>` tags.
