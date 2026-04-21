# Guide d'installation - Story App

## Prérequis

### Commun (tous OS)

- Docker Desktop 4.48+
- Node 20+
- npm 10+

### macOS

- macOS 13+ (Ventura)
- Homebrew (pour installer Node si besoin : `brew install node`)

### Windows

- Windows 10/11
- Docker Desktop pour Windows (avec WSL2 activé)
- Node.js installé depuis <https://nodejs.org/> (LTS 20+)
- PowerShell ou Windows Terminal

> **Note :** PHP et Composer ne sont pas nécessaires sur la machine hôte. Ils tournent dans le container Docker.

---

## 1. Installation des dépendances

### Vérifier les versions installées

```bash
node -v         # Doit afficher v20.x
npm -v          # Doit afficher 10.x
docker -v       # Doit afficher 28.x
```

### macOS (via Homebrew)

```bash
brew install node
```

### Windows

- Télécharger et installer Node.js depuis <https://nodejs.org/> (LTS)
- Télécharger Docker Desktop depuis <https://www.docker.com/products/docker-desktop/>
- Redémarrer le terminal après installation

---

## 2. Configuration du projet

### Structure des dossiers

```TEXT
story-app/
├── backend/          # Code PHP (API REST)
├── front/            # Code React (TypeScript + Vite)
├── database/         # Scripts SQL + migrations
│   ├── init.sql     # Schéma initial
│   └── migrations/  # Fichiers de migration
├── nginx/           # Config serveur web
│   └── default.conf
├── db_data/         # Données PostgreSQL (généré, gitignored)
├── .env             # Variables d'environnement (gitignored)
├── .env.example     # Template pour .env (versionné)
├── docker-compose.yml
└── SETUP.md
```

### Créer le fichier `.env`

Copier le template et ajuster si besoin :

**macOS / Linux :**

```bash
cp .env.example .env
```

**Windows (PowerShell) :**

```powershell
copy .env.example .env
```

Contenu par défaut (`.env.example`) :

```env
# Environnement
APP_ENV=development

# Base de données
DB_HOST=postgres
DB_PORT=5433
DB_NAME=story_app
DB_USER=story_user
DB_PASSWORD=story_password_dev

# Backend API
API_PORT=8080

# Frontend
FRONTEND_PORT=3000
```

---

## 3. Lancer l'infrastructure Docker

### Démarrer les containers

```bash
# À la racine du projet
docker compose up -d
```

**Containers créés :**

- `story_postgres` : PostgreSQL 15 (port 5433)
- `story_php` : PHP 8.4-FPM
- `story_nginx` : Nginx (port 8080)

### Vérifier que tout tourne

```bash
docker compose ps
```

**Résultat attendu :** 3 containers avec status "Up" ou "healthy"

---

## 4. Appliquer les migrations

```bash
docker exec -it story_php php /var/www/scripts/migrate.php up
```

### Vérifier la base de données

```bash
docker exec -it story_postgres psql -U story_user -d story_app
```

```sql
\dt    -- Lister les tables
\d works  -- Voir la structure d'une table
\q     -- Quitter
```

**Tables créées :**

- `works` (œuvres)
- `episodes` (épisodes/parties/livres)
- `chapters` (chapitres/actes)
- `scenes` (scènes)
- `scene_transitions` (liens entre scènes)
- `schema_migrations` (suivi des migrations)

---

## 5. Lancer le frontend

```bash
cd front
npm install
npm run dev
```

Le frontend est accessible sur `http://localhost:5173` (port par défaut de Vite).

L'API backend est sur `http://localhost:8080`.

---

## 6. Lancer les tests

### Tests backend (PHPUnit)

```bash
# Créer la base de test (une seule fois)
docker exec -it story_postgres psql -U story_user -d postgres -c "CREATE DATABASE story_app_test OWNER story_user;"

# Lancer les tests
docker exec -it story_php vendor/bin/phpunit
```

---

## 7. Commandes utiles

### Arrêter les containers

```bash
docker compose down
```

### Redémarrer les containers

```bash
docker compose restart
```

### Voir les logs

```bash
# Tous les containers
docker compose logs -f

# Un container spécifique
docker compose logs -f postgres
docker compose logs -f php
docker compose logs -f nginx
```

### Réinitialiser la base de données

```bash
# Arrêter et supprimer les données
docker compose down -v

# Supprimer les données persistantes
# macOS/Linux :
rm -rf db_data
# Windows :
Remove-Item -Recurse -Force db_data

# Relancer (réexécute init.sql)
docker compose up -d

# Réappliquer les migrations
docker exec -it story_php php /var/www/scripts/migrate.php up
```

### Migrations

```bash
# Voir l'état
docker exec -it story_php php /var/www/scripts/migrate.php status

# Appliquer les migrations en attente
docker exec -it story_php php /var/www/scripts/migrate.php up

# Vérifier l'intégrité
docker exec -it story_php php /var/www/scripts/migrate.php verify
```

---

## 8. Troubleshooting

### Erreur "port 5432/5433 already in use"

Un autre PostgreSQL tourne déjà.

**macOS :**

```bash
brew services stop postgresql
```

**Windows :**

```powershell
# Vérifier quel processus utilise le port
netstat -ano | findstr 5433
# Arrêter le service PostgreSQL si installé localement
Stop-Service postgresql*
```

Ou changer le port dans `.env` (`DB_PORT=5434` par exemple).

### Container postgres "exited (1)" au démarrage

Le plus souvent causé par un dossier `db_data/` corrompu (init partielle). Supprimer et relancer :

```bash
docker compose down -v
# macOS/Linux :
rm -rf db_data
# Windows :
Remove-Item -Recurse -Force db_data

docker compose up -d
```

### Docker ne démarre pas (macOS)

1. Quitter Docker Desktop (Cmd+Q)
2. Attendre 10 secondes
3. Relancer `/Applications/Docker.app`
4. Attendre "Engine running"

### Docker ne démarre pas (Windows)

1. Vérifier que WSL2 est activé : `wsl --status`
2. Si nécessaire : `wsl --install` puis redémarrer
3. Relancer Docker Desktop

### Problèmes de fins de ligne (Windows)

Si les scripts PHP ou SQL ne fonctionnent pas dans les containers Linux, configurer git pour éviter la conversion CRLF :

```bash
git config core.autocrlf input
```

Puis re-cloner ou réinitialiser les fichiers :

```bash
git rm --cached -r .
git reset --hard
```

### Les tables ne sont pas créées

Vérifier que `database/init.sql` existe et relancer :

```bash
docker compose down -v
docker compose up -d
```

---

## 9. Architecture de la base de données

### Hiérarchie du contenu

```TEXT
Work (Œuvre)
  ├─ episode_label (personnalisable : "Épisode", "Partie", "Livre"...)
  ├─ chapter_label (personnalisable : "Chapitre", "Acte"...)
  └─ Episodes (optionnel)
      └─ Chapters
          └─ Scenes
              └─ scene_transitions (navigation non-linéaire)
```

### Navigation entre scènes

- **is_sequential = true** : Scènes qui se suivent logiquement
- **is_sequential = false** : Scènes simultanées (différents points de vue)

### Scènes spéciales

Le système supporte des scènes hors-chapitre :

**Types de scènes :**

- `scene_type = 'standard'` : Scène normale (dans un chapitre)
- `scene_type = 'special'` : Scène spéciale (prologue, intermède, épilogue)

**Label personnalisable :**

- `custom_type_label` : "Prologue", "Intermède", "Épilogue", "Note de l'auteur"...

**Ordre global :**

- `sort_order` : Position dans la narration globale
  - 0-99 : Préface, avant-propos
  - 100-199 : Prologue
  - 200+ : Chapitres (incréments de 100)
  - 9000+ : Épilogue, postface

---

## 10. Backend PHP - API REST

### Structure backend

```TEXT
backend/
├── Dockerfile
├── config/
│   └── database.php
├── src/
│   ├── Router.php
│   └── Controllers/
│       ├── SceneController.php
│       ├── ChapterController.php
│       ├── WorkController.php
│       └── TransitionController.php
├── scripts/
│   └── migrate.php
├── tests/
└── public/
    └── index.php
```

### Rebuild du container PHP (si Dockerfile modifié)

```bash
docker compose build --no-cache php
docker compose up -d
```

### Routes disponibles

**Base :**

- `GET /` - Statut de l'API
- `GET /health` - Test connexion PostgreSQL
- `GET /works` - Liste toutes les œuvres

**Scènes (CRUD) :**

- `GET /scenes` - Liste toutes les scènes
- `GET /scenes/{id}` - Détails d'une scène
- `POST /scenes` - Créer une scène
- `PUT /scenes/{id}` - Modifier une scène
- `DELETE /scenes/{id}` - Supprimer une scène
- `GET /chapters/{id}/scenes` - Scènes d'un chapitre

---

## 11. Migrations de base de données

### Principes

- `migrate.php` gère les transactions (BEGIN/COMMIT) et l'enregistrement dans `schema_migrations`
- Les fichiers SQL ne doivent **pas** contenir de `BEGIN;`, `COMMIT;` ni d'`INSERT INTO schema_migrations`
- Le script nettoie automatiquement ces éléments s'ils sont présents (rétrocompatibilité)

### Créer une nouvelle migration

1. Créer le fichier dans `database/migrations/` au format `YYYYMMDD_HHmm_description.sql`
2. Écrire uniquement les commandes SQL (ALTER, CREATE, etc.)
3. Appliquer avec `docker exec -it story_php php /var/www/scripts/migrate.php up`

---

## Support

Pour toute question, consulter :

- Docker logs : `docker compose logs -f`
- PostgreSQL directement : `docker exec -it story_postgres psql -U story_user -d story_app`