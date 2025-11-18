# Guide d'installation - Story App

## Prérequis

- macOS 13+ (Ventura)
- Homebrew installé
- Docker Desktop 4.48+
- PHP 8.4+
- Node 20+
- Composer

---

## 1. Installation des dépendances

### Vérifier les versions installées

```bash
php -v          # Doit afficher 8.4.x
node -v         # Doit afficher v20.x
npm -v          # Doit afficher 10.x
composer -v     # Doit afficher 2.9.x
docker -v       # Doit afficher 28.x
```

### Si manquantes, installer via Homebrew

```bash
brew install php node composer
```

### Installer Docker Desktop

- Télécharger depuis <https://www.docker.com/products/docker-desktop/>
- Version pour **Mac Intel** (macOS 13)
- Lancer l'application et attendre "Engine running"

---

## 2. Configuration du projet

### Structure des dossiers

```TEXT
story-app/
├── backend/          # Code PHP
├── frontend/         # Code React
├── database/         # Scripts SQL
│   └── init.sql     # Schéma de base de données
├── nginx/           # Config serveur web
│   └── default.conf
├── db_data/         # Données PostgreSQL (généré)
├── .env             # Variables d'environnement
├── docker-compose.yml
└── SETUP.md
```

### Créer le fichier `.env`

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

## 4. Vérifier la base de données

### Se connecter à PostgreSQL

```bash
docker exec -it story_postgres psql -U story_user -d story_app
```

### Lister les tables

```sql
\dt
```

**Tables créées :**

- `works` (œuvres)
- `episodes` (épisodes/parties/livres)
- `chapters` (chapitres/actes)
- `scenes` (scènes)
- `scene_transitions` (liens entre scènes)

### Voir la structure d'une table

```sql
\d works
```

### Quitter PostgreSQL

```sql
\q
```

---

## 5. Commandes utiles

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

# Relancer (réexécute init.sql)
docker compose up -d
```

---

## 6. Troubleshooting

### Erreur "port 5432 already in use"

PostgreSQL local tourne déjà. Solutions :

**Option 1 :** Arrêter PostgreSQL local

```bash
brew services stop postgresql
```

**Option 2 :** Changer le port dans `.env`

```env
DB_PORT=5433
```

### Docker ne démarre pas

1. Quitter Docker Desktop (Cmd+Q)
2. Attendre 10 secondes
3. Relancer `/Applications/Docker.app`
4. Attendre "Engine running"

### Docker command not found dans le terminal

```bash
# Ajouter Docker au PATH
export PATH="/Applications/Docker.app/Contents/Resources/bin:$PATH"

# Ou de manière permanente dans ~/.zshrc
echo 'export PATH="/Applications/Docker.app/Contents/Resources/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

### Les tables ne sont pas créées

Vérifier que `database/init.sql` existe et relancer :

```bash
docker compose down -v
docker compose up -d
```

---

## 7. Architecture de la base de données

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

**Emoji et image :**

- `emoji` : Emoji illustrant la scène (🌙, ⚔️, 🏰...)
- `image_url` : URL de l'image header

**Exemple de prologue :**

```json
{
  "chapter_id": null,
  "scene_type": "special",
  "custom_type_label": "Prologue",
  "title": "Les origines",
  "emoji": "🌅",
  "sort_order": 100,
  "content_markdown": "# Prologue\n\nIl était une fois..."
}
```

---

## 8. Backend PHP - API REST

### Structure backend

```TEXT
backend/
├── Dockerfile                    # Image PHP avec extension PostgreSQL
├── config/
│   └── database.php             # Connexion PDO
├── src/
│   ├── Router.php               # Gestionnaire de routes
│   └── Controllers/
│       └── SceneController.php  # CRUD Scènes
└── public/
    └── index.php                # Point d'entrée API
```

### Rebuild du container PHP (si modifié)

```bash
docker compose build --no-cache php
docker compose up -d
```

### Routes disponibles

#### Base

- `GET /` - Statut de l'API
- `GET /health` - Test connexion PostgreSQL
- `GET /works` - Liste toutes les œuvres

#### Scènes (CRUD complet)

- `GET /scenes` - Liste toutes les scènes
- `GET /scenes/{id}` - Détails d'une scène
- `POST /scenes` - Créer une nouvelle scène
- `PUT /scenes/{id}` - Modifier une scène
- `DELETE /scenes/{id}` - Supprimer une scène
- `GET /chapters/{id}/scenes` - Scènes d'un chapitre

### Exemples d'utilisation

#### Créer un chapitre (prérequis)

```bash
docker exec -it story_postgres psql -U story_user -d story_app -c "
INSERT INTO chapters (work_id, title, number, order_hint)
VALUES ('00000000-0000-0000-0000-000000000001', 'Chapitre 1', 1, 1)
RETURNING id;
"
```

#### Créer une scène

```bash
curl -X POST http://localhost:8080/scenes \
  -H "Content-Type: application/json" \
  -d '{
    "chapter_id": "UUID_DU_CHAPITRE",
    "title": "Ma première scène",
    "content_markdown": "# Titre\n\nContenu en **Markdown**.",
    "order_hint": 1
  }'
```

#### Lister les scènes

```bash
curl http://localhost:8080/scenes
```

#### Récupérer une scène

```bash
curl http://localhost:8080/scenes/UUID_DE_LA_SCENE
```

#### Modifier une scène

```bash
curl -X PUT http://localhost:8080/scenes/UUID_DE_LA_SCENE \
  -H "Content-Type: application/json" \
  -d '{"title": "Nouveau titre"}'
```

#### Supprimer une scène

```bash
curl -X DELETE http://localhost:8080/scenes/UUID_DE_LA_SCENE
```

---

## 9. Migrations de base de données

### Gérer les évolutions du schéma

Le projet utilise un système de migrations versionné pour suivre les modifications de la BDD.

### Voir l'état des migrations

```bash
docker exec -it story_php php /var/www/scripts/migrate.php status
```

### Appliquer les migrations en attente

```bash
docker exec -it story_php php /var/www/scripts/migrate.php up
```

### Structure d'une migration

Les fichiers sont dans `database/migrations/` avec la nomenclature :

- `YYYYMMDD_HHmm_description.sql`
- Exemple : `20251117_1145_add_special_scenes.sql`

### Créer une nouvelle migration

1. Créer le fichier dans `database/migrations/`
2. Utiliser le template avec `BEGIN/COMMIT` et `INSERT INTO schema_migrations`
3. Appliquer avec `migrate.php up`

## Support

Pour toute question, consulter :

- Docker logs : `docker compose logs -f`
- PostgreSQL directement : `docker exec -it story_postgres psql -U story_user -d story_app`
