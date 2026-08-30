# FamiDo - Frontend

Frontend responsive de FamiDo, réalisé avec HTML, SCSS et JavaScript vanilla.

## Prérequis

- PHP pour servir les fichiers localement ;
- Node.js et npm ;
- le backend Symfony lancé sur `http://127.0.0.1:8000`.

## Installation

Depuis le dossier `FrontEnd` :

```bash
npm install
```

Les dépendances utilisées sont :

- Bootstrap 5 pour les styles et les composants ;
- Bootstrap Icons pour les icônes ;
- Sass pour compiler le SCSS.

## Lancer le frontend

```bash
php -S localhost:5501
```

Puis ouvrir :

```text
http://localhost:5501
```

Le frontend doit être servi avec un serveur HTTP. Il ne faut pas ouvrir
`index.html` directement avec `file://`, car les modules JavaScript et les
requêtes vers l'API peuvent être bloqués par le navigateur.

## Organisation

```text
FrontEnd/
├── index.html                 # Point d'entrée de l'application
├── pages/                     # Fragments HTML des différentes pages
├── assets/
│   ├── scripts/
│   │   ├── Route.js           # Modèle d'une route
│   │   ├── allRoutes.js       # Liste des routes
│   │   ├── Router.js          # Chargement dynamique des pages
│   │   ├── api.js             # Client HTTP et token Bearer
│   │   └── *.js               # Scripts propres aux pages
│   └── styles/
│       ├── main.scss          # Fichier SCSS principal
│       ├── _custom.scss       # Variables et styles réutilisables
│       └── main.css           # CSS compilé
└── maquette/                  # Captures des maquettes
```

## Routage

L'application utilise un routeur JavaScript simple. Chaque route contient une
URL, un titre, un fichier HTML et éventuellement un script JavaScript.

Exemple :

```js
new Route(
  "/taches",
  "Tâches familiales",
  "/pages/tasks.html",
  "/assets/scripts/tasks.js",
  true
)
```

Le dernier paramètre indique qu'un token est nécessaire. Sans token, l'utilisateur
est redirigé vers `/connexion`.

## Pages

- `/` : accueil public et présentation de FamiDo ;
- `/connexion` : connexion ;
- `/inscription` : création d'un compte ;
- `/dashboard` : tâches assignées et statistiques personnelles ;
- `/famille` : famille et membres ;
- `/taches` : tâches familiales ;
- `/taches/nouvelle` : création d'une tâche familiale par le chef ;
- `/mes-taches` : tâches privées ;
- `/mes-taches/nouvelle` : création d'une tâche privée ;
- `/profil` : informations de l'utilisateur connecté et déconnexion.

## Connexion à l'API

Le client est centralisé dans `assets/scripts/api.js` :

```js
apiFetch("/taches");
```

Le token retourné par l'inscription ou la connexion est sauvegardé dans
`localStorage`. Il est ajouté automatiquement aux requêtes :

```http
Authorization: Bearer votre_token
```

Le backend doit autoriser l'origine du frontend avec sa configuration CORS.

## SCSS et Bootstrap

Les modifications de styles doivent être réalisées dans `assets/styles/main.scss`
ou dans le fichier `_custom.scss` importé par celui-ci.

Le fichier principal suit cette organisation :

```scss
@import url('/node_modules/bootstrap-icons/font/bootstrap-icons.css');

// Variables Bootstrap
$primary: #6c1de1;

@import '../../node_modules/bootstrap/scss/bootstrap';
@import 'custom';

// Styles personnalisés de FamiDo
```

L'extension Watch Sass peut compiler automatiquement `main.scss` vers
`main.css`. Une compilation manuelle est aussi possible :

```bash
npm run build:css
```

Pour surveiller les changements :

```bash
npm run watch:css
```

## Responsive design

- sur mobile, la navigation est affichée en bas de l'écran ;
- sur desktop, la navigation devient latérale ;
- les formulaires et les cartes s'adaptent à la largeur disponible ;
- les grilles passent sur une colonne lorsque l'écran est étroit.

## Vérification

Pour vérifier la syntaxe JavaScript :

```powershell
Get-ChildItem -Recurse -Filter *.js | ForEach-Object { node --check $_.FullName }
```
