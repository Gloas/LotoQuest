# LotoQuest

[![Tests](https://github.com/Gloas/lotoquest/actions/workflows/tests.yml/badge.svg)](https://github.com/Gloas/lotoquest/actions/workflows/tests.yml)
[![Licence : MIT](https://img.shields.io/badge/licence-MIT-blue.svg)](LICENSE)

*[English version](README.md)*

LotoQuest anime une soirée loto caritative sur vidéoprojecteur : tirage des numéros, grille de 1 à 90, lots de chaque
manche, entractes, spectacle et remerciements. L'application a été écrite pour le loto de l'APE de Valleiry et a servi
aux éditions 2024, 2025 et 2026.

Tout le contenu vient d'un seul fichier CSV exporté par l'outil de tri des dons. Il n'y a pas de base de données : les
numéros tirés sont stockés dans de petits fichiers CSV, un par partie et par session de navigateur.

## Fonctionnalités

| Écran | URL | Ce qui s'affiche |
| --- | --- | --- |
| Accueil | `/` | Mur des logos de tous les donateurs (mosaïque mélangée toutes les 30 s), bouton « Réinitialiser les tirages » |
| Manche | `/adulte/1/quine` | Lots de la manche, dernier numéro en très grand, grille de 1 à 90, bouton « Tirer un nombre » |
| Tirage | `/adulte/1/quine/random` | Tire un numéro pas encore sorti, l'enregistre, puis redirige vers la manche |
| Surprise | `/adulte/surprise_rouge/carton` | Carton seul ; lots cachés jusqu'au clic sur « Voir les lots ? » |
| Gros lot, Pas de bol | `/adulte/gros_lot/carton` | Carton seul, avec un fond dédié |
| Entracte | `/adulte/entracte_d_10_20` | Prix du carton, compte à rebours, mur des logos |
| Spectacle | `/adulte/spectacle` | Titre, texte et photo du spectacle, musique avec feu d'artifice |
| Outro | `/adulte/outro` | Message de remerciement et nuages de mots donateurs / bénévoles |

Sur la grille, le numéro courant et le précédent sont mis en évidence. Un bouton affiche toute la grille, un autre
seulement les numéros tirés, et le pourcentage de numéros sortis est indiqué. Les trois manches d'une partie (quine,
double quine, carton) partagent les mêmes tirages. Le bouton de tirage reste grisé 4 s (adultes) ou 6,5 s (enfants)
après chaque tirage pour éviter les doubles clics.

Chaque navigateur a son propre cookie de session, donc ses propres tirages : un test sur un autre poste ne perturbe
pas le loto. « Réinitialiser les tirages » efface uniquement les tirages de la session courante.

## Démarrage rapide

### Docker

```bash
git clone https://github.com/Gloas/lotoquest.git
cd lotoquest
docker compose up -d --build
```

Ouvrir http://localhost:8080. Le dossier `public/assets` est monté dans le conteneur : on peut modifier `loto.csv` et
ajouter des logos sans reconstruire l'image. Les tirages sont conservés dans le volume Docker `draws`.

### Composer

PHP 8.2 ou plus.

```bash
composer install
composer start
```

Ouvrir http://localhost:8080.

### Hébergement Apache

Lancer `composer install --no-dev`, faire pointer la racine du site sur `public/` (ou sur le projet entier : le
`.htaccess` racine redirige vers `public/`), activer `mod_rewrite` et rendre `csv/` et `logs/` accessibles en
écriture au serveur web.

### Configuration

Toutes les variables d'environnement sont facultatives.

| Variable | Défaut | Rôle |
| --- | --- | --- |
| `LOTOQUEST_LOTO_CSV` | `public/assets/loto.csv` | Fichier des lots à utiliser |
| `LOTOQUEST_DRAWS_DIR` | `csv/` | Dossier des numéros tirés |
| `LOTOQUEST_BASE_PATH` | *(vide)* | Préfixe d'URL si l'appli est dans un sous-dossier, par exemple `/lotoquest` |
| `LOTOQUEST_DEBUG` | `0` | `1` affiche le détail des erreurs ; jamais en production |

## Le fichier `loto.csv`

La première colonne de chaque ligne indique à LotoQuest où il se trouve. Les lignes avant la première ligne
`Parties …` sont ignorées.

| Première colonne | Rôle | Autres colonnes |
| --- | --- | --- |
| `Parties Adulte` | Début d'un loto ; le mot suivant devient son identifiant (`adulte`) | |
| `Partie Adulte n°1` | Début d'une partie numérotée | |
| `Quine`, `Double-quine`, `Carton` | Début d'une manche | |
| `Mise de` | Fin de la liste des lots de la manche | total et objectif (non affichés) |
| tout autre texte | Un lot de la manche en cours | donateur, bénévole, valeur, description, public, genre, logo |
| `Surprise rouge`, `Gros lot`, `Pas de bol` | Partie spéciale à carton unique | |
| `Entracte` | Pause | numéro, prix du carton, minutes |
| `Spectacle` | Écran de spectacle | titre, texte (`\n` = saut de ligne), photo en 7e colonne |
| `Outro` | Écran de fin | message, image donateurs, image bénévoles |

Couleurs de surprise reconnues : rouge, bleu, verte, jaune, rose, turquoise, violet, violette. Les logos sont cherchés
dans `public/assets/logo/`, les images de l'outro dans `public/assets/`.

```csv
Parties Adulte,,,,,,
Partie Adulte n°1,,,,,,
Quine,,,,,,
Boulangerie du coin,Alice,"20,00€",bon pour un gâteau,Adulte,Mix,boulangerie.jpg
Mise de,,"20,00€",/ 20€ pour cette manche,,,
Entracte,1,"10,00€",20,,,
Outro,Merci à tous !,thanks_donators.png,thanks_volunteers.png,,,
```

Un exemple complet se trouve dans [`tests/fixtures/loto.csv`](tests/fixtures/loto.csv).

## Déroulé d'une soirée

1. Avant la soirée : copier `loto.csv` dans `public/assets/`, les logos dans `public/assets/logo/` et les images de
   remerciement dans `public/assets/`, puis réinitialiser les tirages depuis l'accueil.
2. Ouvrir l'accueil : le mur des donateurs tourne pendant l'installation du public.
3. Lancer le spectacle depuis le menu, puis appuyer sur lecture pour la musique et le feu d'artifice.
4. Pour chaque partie, choisir la manche et cliquer sur « Tirer un nombre » à chaque boule. Passer à la manche
   suivante sans réinitialiser : les numéros déjà sortis restent.
5. Aux entractes, afficher l'écran d'entracte : le compte à rebours démarre tout seul.
6. Pour une surprise, laisser les lots cachés puis cliquer sur « Voir les lots ? » au moment voulu.
7. Terminer par l'outro.

En cas d'erreur de tirage, le dernier numéro est la dernière ligne du fichier `csv/<loto>/<session>_<partie>.csv` ;
on peut la supprimer à la main.

## Développement

```bash
composer test       # PHPUnit
composer analyse    # PHPStan
composer lint       # PHP_CodeSniffer (PSR-12)
docker compose run --rm tests   # les tests sans PHP local
```

Les tests utilisent `tests/fixtures/loto.csv` et un dossier de tirages temporaire : ils ne touchent jamais au vrai
fichier des lots ni aux tirages d'une soirée en cours.

### Organisation du code

| Chemin | Contenu |
| --- | --- |
| `app/` | Démarrage Slim : routes, réglages, dépendances, middlewares |
| `public/` | Contrôleur frontal, CSS, JS, polices, logos, musique, `loto.csv` |
| `src/Application/Library/LotoQuest.php` | Affiche tous les écrans et gère les tirages |
| `src/Application/Library/SortedDonations.php` | Lit `loto.csv` : lotos, parties, lots par manche |
| `src/Application/Library/Partie.php` | Une ligne de partie : identifiant, libellé, réglages d'entracte, spectacle, outro |
| `src/Application/Library/Paths.php` | Emplacement du fichier des lots et des tirages |
| `csv/<loto>/<session>_<partie>.csv` | Numéros tirés, un par ligne |
| `tests/` | Tests PHPUnit et `loto.csv` d'exemple |

Construit avec [Slim 4](https://www.slimframework.com/), PHP-DI, Monolog, Bootstrap 5, Font Awesome et Masonry.

### Limites connues

- Les couleurs de surprise et les parties spéciales sont listées dans le code (`LotoQuest::_showRoundsMenu()` et
  `SortedDonations`) ; une nouvelle couleur demande une modification du code.
- Les tirages sont liés à la session du navigateur : changer d'ordinateur en cours de soirée repart d'une grille vide.
- D'autres pistes sont listées dans [TODO.md](TODO.md).

## Contribuer

Voir [CONTRIBUTING.md](CONTRIBUTING.md).

## Licence

[MIT](LICENSE)
