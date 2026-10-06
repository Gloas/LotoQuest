# LotoQuest

[![Tests](https://github.com/Gloas/lotoquest/actions/workflows/tests.yml/badge.svg)](https://github.com/Gloas/lotoquest/actions/workflows/tests.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

LotoQuest runs a charity bingo night (*loto*) on a projector: number drawing, the 1–90 board, the prizes of each round,
intermissions, a show and the closing thanks. It was written for the loto of the APE (parents' association) of
Valleiry and used for the 2024, 2025 and 2026 editions.

Everything comes from a single CSV file exported by the donation sorting tool. There is no database: drawn numbers
are stored in small CSV files, one per game and per browser session.

The user interface is in French.

## Features

| Screen | URL | What it shows |
| --- | --- | --- |
| Home | `/` | Wall of every donor's logo (masonry, reshuffled every 30 s), *reset draws* button |
| Round | `/adulte/1/quine` | Prizes of the round, last number in huge font, 1–90 board, *Tirer un nombre* button |
| Draw | `/adulte/1/quine/random` | Draws a number not yet drawn, stores it, redirects to the round |
| Surprise | `/adulte/surprise_rouge/carton` | Single *carton* round, prizes hidden until *Voir les lots ?* is clicked |
| Gros lot, Pas de bol | `/adulte/gros_lot/carton` | Single *carton* round with its own background |
| Entracte | `/adulte/entracte_d_10_20` | Card price, countdown timer, wall of logos |
| Spectacle | `/adulte/spectacle` | Title, text and picture of the show, music with fireworks |
| Outro | `/adulte/outro` | Thank-you message and donors / volunteers word clouds |

On the board, the current and previous numbers are highlighted, buttons reveal the whole board or only the drawn
numbers, and the percentage of drawn numbers is shown. The three rounds of a game (*quine*, *double quine*,
*carton*) share the same draws. The draw button stays disabled 4 s (adults) or 6.5 s (kids) after each draw to avoid
double clicks.

Each browser has its own session cookie, hence its own draws: testing on another computer does not disturb the
game. *Réinitialiser les tirages* removes the draws of the current session only.

## Quick start

### Docker

```bash
git clone https://github.com/Gloas/lotoquest.git
cd lotoquest
docker compose up -d --build
```

Open http://localhost:8080. `public/assets` is mounted into the container, so `loto.csv` and logos can be changed
without rebuilding. Drawn numbers live in the `draws` Docker volume.

### Composer

Requires PHP 8.2 or newer.

```bash
composer install
composer start
```

Open http://localhost:8080.

### Apache hosting

Run `composer install --no-dev`, point the document root to `public/` (or to the project root: the root
`.htaccess` forwards to `public/`), enable `mod_rewrite` and make `csv/` and `logs/` writable by the web server.

### Configuration

All environment variables are optional.

| Variable | Default | Purpose |
| --- | --- | --- |
| `LOTOQUEST_LOTO_CSV` | `public/assets/loto.csv` | Prize sheet to use |
| `LOTOQUEST_DRAWS_DIR` | `csv/` | Where drawn numbers are stored |
| `LOTOQUEST_BASE_PATH` | *(empty)* | URL prefix when served from a sub-directory, e.g. `/lotoquest` |
| `LOTOQUEST_DEBUG` | `0` | `1` displays error details; never in production |

## The `loto.csv` file

The first column of each row tells LotoQuest where it is. Rows before the first `Parties …` row are ignored.

| First column | Meaning | Other columns |
| --- | --- | --- |
| `Parties Adulte` | Starts a loto; the next word becomes its id (`adulte`) | |
| `Partie Adulte n°1` | Starts a numbered game | |
| `Quine`, `Double-quine`, `Carton` | Starts a round | |
| `Mise de` | Ends the prize list of the round | total and target (not displayed) |
| any other text | A prize of the current round | donor, volunteer, value, description, audience, gender, logo file |
| `Surprise rouge`, `Gros lot`, `Pas de bol` | Special game with a single *carton* round | |
| `Entracte` | Intermission | number, card price, minutes |
| `Spectacle` | Show screen | title, text (`\n` = line break), picture in the 7th column |
| `Outro` | Closing screen | message, donors image, volunteers image |

Known surprise colours: rouge, bleu, verte, jaune, rose, turquoise, violet, violette. Logos are looked up in
`public/assets/logo/`, outro images in `public/assets/`.

```csv
Parties Adulte,,,,,,
Partie Adulte n°1,,,,,,
Quine,,,,,,
Boulangerie du coin,Alice,"20,00€",bon pour un gâteau,Adulte,Mix,boulangerie.jpg
Mise de,,"20,00€",/ 20€ pour cette manche,,,
Entracte,1,"10,00€",20,,,
Outro,Merci à tous !,thanks_donators.png,thanks_volunteers.png,,,
```

A complete example lives in [`tests/fixtures/loto.csv`](tests/fixtures/loto.csv).

## Development

```bash
composer test       # PHPUnit
composer analyse    # PHPStan
composer lint       # PHP_CodeSniffer (PSR-12)
docker compose run --rm tests   # the test suite without a local PHP
```

The tests run against `tests/fixtures/loto.csv` and a temporary draws directory, so they never touch the real
prize sheet nor the draws of an ongoing game.

### Layout

| Path | Content |
| --- | --- |
| `app/` | Slim bootstrap: routes, settings, dependencies, middleware |
| `public/` | Front controller, CSS, JS, fonts, logos, music, `loto.csv` |
| `src/Application/Library/LotoQuest.php` | Renders every screen and handles draws |
| `src/Application/Library/SortedDonations.php` | Reads `loto.csv`: lotos, games, prizes per round |
| `src/Application/Library/Partie.php` | One game row: id, label, entracte, show and outro settings |
| `src/Application/Library/Paths.php` | Prize sheet and draws locations |
| `csv/<loto>/<session>_<game>.csv` | Drawn numbers, one per line |
| `tests/` | PHPUnit tests and the example `loto.csv` |

Built on [Slim 4](https://www.slimframework.com/), PHP-DI, Monolog, Bootstrap 5, Font Awesome and Masonry.

### Known limitations

- Surprise colours and special games are listed in the code (`LotoQuest::_showRoundsMenu()` and
  `SortedDonations`); a new colour needs a code change.
- Draws are tied to the browser session: switching computer during the night starts a new board.
- Some ideas are listed in [TODO.md](TODO.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

[MIT](LICENSE)
