# Contexte du Projet Story App

Date de création : 2025-11-16 à 2025-11-18
Type de projet : Application web de narration interactive avec gamification
Stack technique : PHP 8.4 + React TypeScript + PostgreSQL 15 + Docker

## 1. Vision du projet

Objectif principal
Créer une plateforme pour publier et lire des histoires interactives (romans, nouvelles) avec :

Navigation non-linéaire entre scènes (embranchements, scènes simultanées)
Gamification : références pop culture cachées dans le texte, système de points
Double interface : Auteur (création/édition) et Lecteur (lecture/jeu)

Particularités narratives

Structure hiérarchique : Œuvre → Épisodes (opt.) → Chapitres → Scènes
Scènes spéciales hors-chapitre (prologue, intermède, épilogue)
Transitions entre scènes : séquentielles (suite logique) ou simultanées (points de vue parallèles)
Références pop culture : lecteur sélectionne du texte, valide via mots-clés → gagne des points

## 2. Décisions architecturales majeures

Stack technique

Backend : PHP 8.4-FPM (Alpine) avec PDO PostgreSQL
Frontend : React 18 + TypeScript (Vite)
BDD : PostgreSQL 15
Infrastructure : Docker Compose (3 containers : postgres, php, nginx)
Déploiement cible : NAS Synology (macOS 13 en dev)

Approche de développement

TDD : Tests unitaires avec PHPUnit (prévu)
Agile : Sprints courts, itératif
CI/CD : Planifié pour déploiement Synology
Versioning BDD : Système de migrations avec table schema_migrations

Architecture backend

Pattern : MVC léger (pas de framework lourd)
Router custom : Router.php avec matching de routes dynamiques
Controllers : Séparation par ressource (SceneController, WorkController...)
API REST : JSON, CORS activé pour React

## 3. Structure de la base de données

Hiérarchie du contenu
Work (Œuvre)
├─ episode_label (personnalisable : "Épisode", "Partie", "Livre", "Saison"...)
├─ chapter_label (personnalisable : "Chapitre", "Acte"...)
├─ Episodes (optionnel, peut être NULL)
│ └─ Chapters
│ └─ Scenes
└─ Chapters (directement rattachés si pas d'épisode)
└─ Scenes
Tables principales
works

id (UUID)
title, description
author_id (UUID, FK vers users - pas encore implémenté)
published (boolean)
episode_label, chapter_label (nomenclature personnalisable)
created_at, updated_at

episodes (optionnel)

id (UUID)
work_id (FK → works)
title, number, order_hint
created_at

chapters

id (UUID)
work_id (FK → works, toujours présent)
episode_id (FK → episodes, nullable)
title, number, order_hint
created_at

scenes (TABLE CENTRALE)

id (UUID)
chapter_id (FK → chapters, nullable pour scènes spéciales)
title, content_markdown
scene_type : 'standard' | 'special'
custom_type_label : "Prologue", "Intermède", "Épilogue"... (nullable)
sort_order : Ordre global d'affichage (0-99 : préface, 100-199 : prologue, 200+ : chapitres, 9000+ : épilogue)
emoji : Emoji illustrant la scène (ex: 🌙, ⚔️)
image_url : URL de l'image header de la scène
order_hint : Ordre au sein du chapitre (si applicable)
published_at (nullable)
created_at, updated_at

Index : idx_scenes_sort_order, idx_scenes_type, idx_scenes_chapter
scene_transitions (Navigation)

id (UUID)
scene_before_id (FK → scenes)
scene_after_id (FK → scenes)
is_sequential (boolean) : true = suite logique, false = scènes simultanées
custom_label : Label personnalisé pour le lien (nullable, sinon titre de la scène)
display_order : Ordre d'affichage si plusieurs liens
created_at

Contraintes :

UNIQUE(scene_before_id, scene_after_id)
CHECK(scene_before_id != scene_after_id)

Index : idx_transitions_before, idx_transitions_after
schema_migrations (Gestion des migrations)

id (SERIAL)
version (VARCHAR 50, UNIQUE) : Format YYYYMMDD_HHmm
description : Description de la migration
script_name : Nom du fichier SQL
applied_at : Timestamp d'application
checksum : Hash MD5 du script (nullable)

## 4. Décisions de conception importantes

Scènes spéciales (prologue, intermède, épilogue)

chapter_id = NULL pour scènes hors-chapitre
scene_type = 'special' (vs 'standard')
custom_type_label = label affiché au lecteur ("Prologue", "Intermède"...)
sort_order = position globale dans la narration

Stratégie de sort_order :

0-99 : Préface, avant-propos
100-199 : Prologue
200-299 : Chapitre 1
300-399 : Chapitre 2
350 : Intermède inséré entre chapitres
9000+ : Épilogue, postface

Navigation non-linéaire

is_sequential = true : Scènes se suivent logiquement (flèche ➡️ ou ⬅️)
is_sequential = false : Scènes simultanées, différents points de vue (icône 🔄)
Une scène peut avoir plusieurs transitions sortantes (branches narratives)
Une scène peut être destination de plusieurs transitions (convergence)

Styles Markdown personnalisés (Option A retenue)

Syntaxe : :::narrateur-direct ... ::: pour styles spéciaux
Parse côté backend → génère HTML avec classes CSS
Classes prédéfinies : narrateur-direct, narrateur-indirect, citation, pensee
CSS appliqué côté frontend React

Références pop culture (Sprint 2+, pas encore implémenté)

Table references : titre, catégorie (film/série/livre...), mots-clés (JSONB)
Table pivot scene_references : many-to-many avec scènes
Stockage du highlighted_text pour affichage après découverte
Pas de position exacte dans le texte (annotation manuelle en Phase 3 avec Tiptap)

Gamification (Sprint 2+, pas encore implémenté)

1 point par référence trouvée
1 point pour débloquer une scène en avance
1 point pour révéler une référence
Déblocage quotidien automatique : opt-in (désactivé par défaut)
Pas de système d'indices progressifs (tout ou rien)

Images des scènes

Stockage local : backend/public/uploads/scenes/
image_url stocke le chemin relatif : /uploads/scenes/scene-uuid.jpg
Migration future possible vers CDN (Cloudinary) si besoin

## 5. État actuel du projet (au 2025-11-18)

✅ Infrastructure opérationnelle
Docker Compose configuré :
yamlservices:
postgres: PostgreSQL 15-alpine (port 5433, car 5432 occupé localement)
php: PHP 8.4-fpm-alpine avec extension pdo_pgsql
nginx: Reverse proxy (port 8080)
Variables d'environnement (.env) :
envAPP_ENV=development
DB_HOST=postgres
DB_PORT=5433
DB_NAME=story_app
DB_USER=story_user
DB_PASSWORD=story_password_dev
API_PORT=8080
FRONTEND_PORT=3000

**Volumes montés :**

- `./backend:/var/www` (code PHP)
- `./database:/database` (migrations SQL)
- `./db_data:/var/lib/postgresql/data` (données PostgreSQL persistantes)

### ✅ Base de données

**Schéma initial (`database/init.sql`) :**

- Tables : `works`, `episodes`, `chapters`, `scenes`, `scene_transitions`
- Œuvre de test insérée : ID `00000000-0000-0000-0000-000000000001`, titre "Mon Roman"

**Migration appliquée (`20251117_1145_add_special_scenes.sql`) :**

- `chapter_id` nullable
- Colonnes ajoutées : `scene_type`, `custom_type_label`, `sort_order`, `emoji`, `image_url`
- Index créés pour performances

**Système de migrations fonctionnel :**

- Script `backend/scripts/migrate.php`
- Commandes : `status` (liste migrations) et `up` (applique en attente)

### ✅ Backend PHP

**Structure des fichiers :**

backend/
├── Dockerfile (PHP + pdo_pgsql)
├── config/
│ └── database.php (connexion PDO)
├── src/
│ ├── Router.php (gestionnaire de routes)
│ └── Controllers/
│ └── SceneController.php (CRUD scènes)
├── scripts/
│ └── migrate.php (gestion migrations)
└── public/
└── index.php (point d'entrée API)
Routes API disponibles :

GET / → Statut API
GET /health → Test connexion BDD
GET /works → Liste œuvres
GET /scenes → Liste scènes (triées par order_hint actuellement)
GET /scenes/{id} → Détails d'une scène
POST /scenes → Créer une scène
PUT /scenes/{id} → Modifier une scène
DELETE /scenes/{id} → Supprimer une scène
GET /chapters/{id}/scenes → Scènes d'un chapitre

SceneController :

Méthodes statiques : index(), show(), create(), update(), destroy(), byChapter()
Validation basique des champs requis
Retours JSON avec structure {"status": "ok"|"error", "data": ..., "message": ...}

⏳ À faire immédiatement (Sprint 1 en cours)
SceneController à mettre à jour :

index() : Trier par sort_order ASC (pas juste order_hint)
create() : Déjà accepte scene_type, custom_type_label, sort_order, emoji, image_url ✅
update() : Déjà gère ces champs ✅
Tester création d'un prologue via API

Prochaines étapes : 5. Upload d'images pour scènes 6. Import Markdown simple (drag & drop fichier .md) 7. Interface React basique (affichage scène + navigation)
❌ Pas encore implémenté

Authentification (users, JWT)
Système de références (tables references, scene_references, user_found_references)
Gamification (points, progression, déblocages)
Frontend React (aucun code côté client pour l'instant)
Tiptap (éditeur WYSIWYG pour annotation références)
Import Notion (prévu Sprint 3+)

## 6. Conventions de code établies

PHP

PSR-4 : Autoloading par namespace (pas encore configuré Composer autoload)
Types stricts : declare(strict_types=1); dans chaque fichier
Nommage :

Classes : PascalCase (SceneController)
Méthodes : camelCase (createScene())
Variables : camelCase ($sceneId)
Constantes : SCREAMING_SNAKE_CASE

Retours JSON : Toujours structure {"status": "ok"|"error", "data": ..., "message": ...}
Codes HTTP : 200 (OK), 201 (Created), 400 (Bad Request), 404 (Not Found), 500 (Server Error)

SQL

UUIDs : gen_random_uuid() pour tous les IDs
Timestamps : CURRENT_TIMESTAMP par défaut
Nommage :

Tables : snake*case pluriel (scenes, scene_transitions)
Colonnes : snake_case (created_at, scene_type)
Index : idx*{table}\_{colonne} (idx_scenes_sort_order)

ON DELETE CASCADE : Propagation des suppressions
NOT NULL : Par défaut, sauf si logique métier impose nullable

Migrations

Format fichier : YYYYMMDD_HHmm_description.sql
Structure : BEGIN ... COMMIT avec INSERT INTO schema_migrations
Documentation : Commentaires en en-tête (version, description, auteur)
Rollback : Section DOWN commentée (optionnelle mais recommandée)

## 7. Problèmes résolus et décisions techniques

Port PostgreSQL 5432 occupé
Solution : Mapper sur 5433 (DB_PORT=5433 dans .env)
Extension pdo_pgsql manquante
Solution : Dockerfile custom avec apk add postgresql-dev && docker-php-ext-install pdo pdo_pgsql
Docker Desktop 28.5.1 sur macOS 13
Solution : Version 4.48 compatible, mais nécessite ajout PATH manuel dans .zshrc
Node v16 → v20
Solution : brew install node@20 && brew link --overwrite node@20
Homebrew warnings (CLT 15.2, python@3.11)
Solution : Ignorés (non bloquants), .zprofile nettoyé
Chemin migrations dans migrate.php
Problème : **DIR** . '/../../database/migrations/' incorrect depuis container
Solution : Chemin absolu /database/migrations/ + volume monté dans docker-compose.yml
Message "What's next" Docker
Solution : export DOCKER_CLI_HINTS=false dans .zshrc

## 8. Données de test actuelles

Œuvre

ID : 00000000-0000-0000-0000-000000000001
Titre : "Mon Roman"
Published : true

Chapitre (créé manuellement)

work_id : 00000000-0000-0000-0000-000000000001
Titre : "Chapitre 1"
Number : 1

Scène (créée via API)

Titre : "Scène modifiée"
Content : "# Première scène\n\nContenu en **Markdown**."
chapter_id : UUID du chapitre ci-dessus
order_hint : 1

## 9. Roadmap et priorisation

Sprint 1 : MVP Lecture (1-2 semaines) - EN COURS

✅ Setup infrastructure Docker
✅ Tables SQL + système de migrations
✅ CRUD Scènes (API REST)
⏳ Mise à jour SceneController (tri par sort_order)
⏳ Upload images pour scènes
⏳ Import Markdown simple
⏳ Frontend React basique (affichage scène + navigation)

Objectif : Voir l'histoire en ligne, navigable, avec les 70-100 scènes de l'auteur.
Sprint 2 : Auth + Progression (1 semaine)

Système login (JWT ou sessions)
Table users
Sauvegarde progression lecteur (table user_progress)

Sprint 3 : Gamification (1-2 semaines)

Tables references, scene_references, user_found_references
Validation références (popup)
Système de points
Déblocage scènes avec points

Sprint 4 : Polish (1 semaine)

Tiptap pour annoter références visuellement
UX/UI propre
Déploiement Synology

Sprint 5+ : Features avancées

Import Notion automatisé
Import batch (TXT, JSON, CSV)
Commentaires lecteurs
Système de popularité
Partage externe (Open Graph)

## 10. Fichiers clés à consulter

Configuration

.env : Variables d'environnement
docker-compose.yml : Orchestration containers
backend/Dockerfile : Image PHP custom

Base de données

database/init.sql : Schéma initial
database/migrations/20251117_1145_add_special_scenes.sql : Migration scènes spéciales

Backend

backend/config/database.php : Connexion PDO
backend/src/Router.php : Gestionnaire de routes
backend/src/Controllers/SceneController.php : CRUD scènes
backend/public/index.php : Point d'entrée API
backend/scripts/migrate.php : Gestion migrations

Documentation

SETUP.md : Guide d'installation et utilisation complet
README.md : Vue d'ensemble du projet (à enrichir)

## 11. Points d'attention pour la suite

Sécurité

Pas de validation stricte des inputs pour l'instant (à ajouter avant prod)
CORS ouvert (Access-Control-Allow-Origin: \*) → À restreindre en prod
Mots de passe en clair dans .env (OK pour dev, changer en prod)

Performance

Index créés sur colonnes fréquemment requêtées
Prévoir pagination si nombre de scènes > 1000

Évolutivité

Structure permet ajout facile de nouvelles ressources (Chapters, Episodes, Users...)
Migrations versionnées → Safe pour déploiements multiples environnements

Testabilité

Architecture MVC favorise tests unitaires
PHPUnit à configurer (prévu Sprint 2)

## 12. Commandes utiles mémorisées

bash# Docker
docker compose up -d
docker compose down
docker compose ps
docker compose logs -f php
docker compose restart php

### PostgreSQL

docker exec -it story_postgres psql -U story_user -d story_app
docker exec -it story_postgres psql -U story_user -d story_app -c "\dt"

### Migrations

docker exec -it story_php php /var/www/scripts/migrate.php status
docker exec -it story_php php /var/www/scripts/migrate.php up

### API (curl)

curl <http://localhost:8080/scenes>
curl <http://localhost:8080/health>
curl -X POST <http://localhost:8080/scenes> -H "Content-Type: application/json" -d '{...}'

Ce fichier couvre tout le contexte nécessaire pour reprendre le projet sans perte d'information. Utilise-le comme base dans Projects ! 🚀
