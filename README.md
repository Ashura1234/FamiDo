# FamiDo

API REST de gestion des tâches familiales développée avec **Symfony 8.1**.

Le chef de famille crée les tâches communes, les assigne à plusieurs membres
et valide leur réalisation. Chaque membre peut aussi gérer ses tâches privées,
visibles uniquement par lui.

## Prérequis

Avant de commencer, installer :

- PHP 8.4 ou supérieur ;
- Composer ;
- Symfony CLI ;
- MariaDB 10.4 ou MySQL compatible ;
- phpMyAdmin, facultatif pour administrer la base.

## Installation

### 1. Cloner le dépôt

```bash
git clone <url-de-ton-depot>
cd FamiDo
```

### 2. Installer les dépendances

```bash
cd BackEnd/FamiDo_BackEnd
composer install
```

### 3. Configurer les variables d'environnement

Créer `.env.local` à partir de `.env` et adapter la connexion locale :

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/famido?serverVersion=mariadb-10.4.32&charset=utf8mb4"
```

Pour les tests, le fichier `.env.test` utilise une base séparée :

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/famido_test?serverVersion=mariadb-10.4.32&charset=utf8mb4"
```

Ne versionnez jamais `.env.local` : il est ignoré par Git.

### 4. Créer la base de données

Démarrer MariaDB, puis exécuter :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

La base peut être consultée dans phpMyAdmin sur
`http://localhost/phpmyadmin`.

### 5. Lancer le serveur

```bash
symfony server:start
```

L'API est accessible sur `https://127.0.0.1:8000`.

## Documentation de l'API

Swagger UI est disponible sur :

```text
https://127.0.0.1:8000/api/doc
```

Le document OpenAPI brut est disponible sur :

```text
https://127.0.0.1:8000/api/doc.json
```

Dans Swagger, cliquer sur **Try it out** pour renseigner les corps JSON.

## Authentification

L'API utilise un token Bearer aléatoire stocké dans `User.apiToken`.

### Créer un compte

```http
POST /api/register
Content-Type: application/json
```

```json
{
  "email": "parent@test.fr",
  "prenom": "Parent",
  "password": "motdepasse123",
  "role": "ROLE_CHEF"
}
```

Rôles disponibles : `ROLE_CHEF` et `ROLE_MEMBRE`.

### Se connecter

```http
POST /api/login
Content-Type: application/json
```

```json
{
  "email": "parent@test.fr",
  "password": "motdepasse123"
}
```

### Utiliser le token

Ajouter le token reçu dans les routes protégées :

```http
Authorization: Bearer votre_token
```

Les routes publiques sont `/api/register`, `/api/login`, `/api/doc` et
`/api/doc.json`.

## Routes principales

| Méthode | Route | Accès | Description |
|---|---|---|---|
| `POST` | `/api/register` | Public | Créer un compte |
| `POST` | `/api/login` | Public | Se connecter et obtenir un token |
| `POST` | `/api/famille` | Chef | Créer une famille |
| `GET` | `/api/famille` | Authentifié | Consulter sa famille |
| `POST` | `/api/famille/rejoindre` | Authentifié | Rejoindre avec un code |
| `DELETE` | `/api/famille/membres/{id}` | Chef | Retirer un membre |
| `GET` | `/api/taches` | Authentifié | Lister les tâches familiales |
| `POST` | `/api/taches` | Chef | Créer une tâche familiale |
| `PUT` | `/api/taches/{id}` | Chef | Modifier une tâche familiale |
| `PATCH` | `/api/taches/{id}/statut` | Chef | Modifier son statut |
| `DELETE` | `/api/taches/{id}` | Chef | Supprimer une tâche familiale |
| `PUT` | `/api/taches/{id}/assignations` | Chef | Assigner plusieurs membres |
| `GET` | `/api/mes-taches` | Authentifié | Lister ses tâches privées |
| `POST` | `/api/mes-taches` | Authentifié | Créer une tâche privée |
| `PUT` | `/api/mes-taches/{id}` | Créateur | Modifier une tâche privée |
| `DELETE` | `/api/mes-taches/{id}` | Créateur | Supprimer une tâche privée |

## Règles métier

- Une tâche familiale est visible uniquement par les membres de sa famille.
- Une tâche privée est visible uniquement par son créateur.
- Seul le chef peut gérer les tâches familiales et leurs assignations.
- Une tâche peut être assignée à plusieurs membres.
- Une même tâche ne peut pas être assignée deux fois au même utilisateur.
- Les mots de passe sont toujours hachés avant stockage.

## Structure du projet

```text
├── BackEnd/FamiDo_BackEnd/
│   ├── config/              # Configuration Symfony, Security et Swagger
│   ├── migrations/          # Migrations Doctrine
│   ├── src/
│   │   ├── Controller/Api/  # Contrôleurs REST
│   │   ├── Entity/          # Entités Doctrine
│   │   ├── Repository/      # Repositories
│   │   └── Security/        # Gestion du token Bearer
│   ├── tests/               # Tests fonctionnels API
│   └── composer.json
├── FrontEnd/                # Interface HTML, SCSS et JavaScript à venir
├── JOURNAL_DE_BORD.md       # Documentation de développement locale
└── README.md
```

## Tests

Les tests utilisent la base `famido_test` et couvrent l'inscription, la
connexion, l'authentification Bearer, les familles et la confidentialité des
tâches privées.

```bash
vendor/bin/phpunit --testdox
```

Résultat actuel :

```text
OK (7 tests, 33 assertions)
```

## Commandes utiles

```bash
# Vérifier les entités et la base
php bin/console doctrine:schema:validate

# Créer une migration après une modification d'entité
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Vider le cache
php bin/console cache:clear

# Générer le document OpenAPI
php bin/console nelmio:apidoc:dump --format=json

# Lancer les tests
vendor/bin/phpunit --testdox
```
