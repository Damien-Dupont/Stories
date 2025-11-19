# Guide des migrations - Story App

**Date** : 19 novembre 2025  
**Objectif** : Documentation complète du système de migrations avec gestion automatique

---

## 🎯 Philosophie du système

### Principes clés

1. **Fichiers SQL purs** : Pas de logique PHP dans les migrations
2. **Idempotence** : Une migration déjà appliquée ne passe jamais deux fois
3. **Atomicité** : Transaction BEGIN/COMMIT pour tout ou rien
4. **Traçabilité** : Checksum MD5 pour détecter les modifications
5. **Automatisation** : INSERT dans `schema_migrations` géré par `migrate.php`

---

## 📁 Structure des dossiers

```SCHEMA
database/
├── init.sql                    # Schéma initial (CREATE TABLE)
└── migrations/
    ├── 20251117_1145_add_special_scenes.sql
    ├── 20251119_1000_nouvelle_migration.sql
    └── ...
```

**Convention de nommage :**

- Format : `YYYYMMDD_HHmm_description_courte.sql`
- Exemple : `20251119_1430_add_user_authentication.sql`

**Pourquoi cette convention ?**

- Tri chronologique automatique
- Identification rapide de la date
- Description explicite du contenu

---

## 🛠️ Configuration Docker

### Option A : Chemin absolu (recommandé)

**docker-compose.yml :**

```yaml
php:
  volumes:
    - ./backend:/var/www
    - ./database:/database # Racine accessible en /database
```

**migrate.php :**

```php
$this->migrationsDir = '/database/migrations/';
```

**Avantages :**

- Séparation claire backend/database
- Cohérent avec PostgreSQL
- Facilite les backups

---

### Option B : Chemin relatif

**docker-compose.yml :**

```yaml
php:
  volumes:
    - ./backend:/var/www
    - ./database:/var/www/database # Sous-dossier de /var/www
```

**migrate.php :**

```php
$this->migrationsDir = __DIR__ . '/../../database/migrations/';
```

**Avantages :**

- Tout sous `/var/www`
- Portable (chemins relatifs)

---

## 📝 Créer une nouvelle migration

### Étape 1 : Créer le fichier

```bash
# Générer le nom avec la date actuelle
DATE=$(date +"%Y%m%d_%H%M")
touch database/migrations/${DATE}_ma_nouvelle_migration.sql
```

### Étape 2 : Utiliser le template

```sql
-- Migration: 20251119_1430_add_user_authentication
-- Description: Ajout de la table users et colonnes d'authentification
-- Auteur: Ton nom
-- Date: 2025-11-19

BEGIN;

-- Créer la table users
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index pour performances
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);

-- Ajouter la colonne author_id dans works
ALTER TABLE works
ADD COLUMN author_id UUID NULL REFERENCES users(id) ON DELETE SET NULL;

COMMIT;
```

⚠️ **Ne PAS inclure** :

```sql
-- ❌ À NE PAS FAIRE
INSERT INTO schema_migrations (version, description, script_name)
VALUES ('20251119_1430', 'Add user authentication', '20251119_1430_add_user_authentication.sql');
```

---

## 🚀 Appliquer les migrations

### Voir l'état actuel

```bash
docker exec -it story_php php /var/www/scripts/migrate.php status
```

**Résultat :**

```BASH
=== État des migrations ===

Appliquées: 1
Disponibles: 2
Dossier: /database/migrations/

✓ 20251117_1145 - add special scenes
⏳ 20251119_1430 - add user authentication
```

---

### Appliquer les migrations en attente

```bash
docker exec -it story_php php /var/www/scripts/migrate.php up
```

**Résultat :**

```BASH
Migrations en attente: 1

Applying 20251119_1430: add user authentication... ✓

✓ Toutes les migrations ont été appliquées.
```

---

### Vérifier l'intégrité

```bash
docker exec -it story_php php /var/www/scripts/migrate.php verify
```

**Résultat :**

```BASH
=== Vérification de l'intégrité ===

✓ 20251117_1145 - 20251117_1145_add_special_scenes.sql
✓ 20251119_1430 - 20251119_1430_add_user_authentication.sql

✓ Toutes les migrations sont intègres.
```

---

## ⚠️ Gestion des erreurs

### Erreur : Dossier de migrations introuvable

```BASH
⚠️  Le dossier de migrations n'existe pas : /database/migrations/
```

**Solution :**

1. Vérifier le volume dans `docker-compose.yml`
2. Vérifier le chemin dans `migrate.php`
3. Reconstruire le container : `docker compose up -d --build`

---

### Erreur : Migration SQL échoue

```BASH
Applying 20251119_1430: add user authentication... ✗
Erreur: SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "inexistante" does not exist
Migration: 20251119_1430_add_user_authentication.sql
```

**Solution :**

### 1. Corriger le fichier SQL

### 2. Supprimer l'entrée dans `schema_migrations` si partiellement appliquée

```bash
docker exec -it story_postgres psql -U story_user -d story_app -c \
"DELETE FROM schema_migrations WHERE version = '20251119_1430';"
```

### 3. Relancer `migrate.php up`

---

### Avertissement : Fichier modifié après application

```BASH
⚠️ 20251117_1145 - 20251117_1145_add_special_scenes.sql Fichier modifié après application
```

**Signification :**

- Le fichier SQL a été édité après son application
- Le checksum MD5 ne correspond plus

**Actions recommandées :**

- Si modif intentionnelle : Créer une nouvelle migration
- Si erreur : Restaurer le fichier original
- **Jamais** modifier une migration déjà appliquée en prod !

---

## 🔄 Workflow complet

### Développement local

#### 1. **Créer la migration**

```bash
touch database/migrations/20251119_1430_ma_migration.sql
```

#### 2. **Écrire le SQL**

```sql
BEGIN;
-- Modifications
COMMIT;
```

#### 3. **Tester localement**

```bash
docker exec -it story_php php /var/www/scripts/migrate.php up
```

#### 4. **Vérifier BDD**

```bash
docker exec -it story_postgres psql -U story_user -d story_app -c "\dt"
```

#### 5. **Commit**

```bash
git add database/migrations/20251119_1430_ma_migration.sql
git commit -m "feat(db): Ma nouvelle migration"
```

---

### Déploiement production (Synology)

#### 1. **Pull du code**

```bash
git pull origin main
```

#### 2. **Appliquer les migrations**

```bash
docker exec story_php php /var/www/scripts/migrate.php up
```

#### 3. **Vérifier**

```bash
docker exec story_php php /var/www/scripts/migrate.php verify
```

---

## 📊 Table `schema_migrations`

### Structure

```sql
CREATE TABLE schema_migrations (
    id SERIAL PRIMARY KEY,
    version VARCHAR(50) UNIQUE NOT NULL,      -- Ex: 20251119_1430
    description TEXT NOT NULL,                -- Ex: add user authentication
    script_name VARCHAR(255) NOT NULL,        -- Ex: 20251119_1430_add_user_authentication.sql
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    checksum VARCHAR(64) NULL                 -- MD5 du fichier
);
```

### Consultation

```bash
docker exec -it story_postgres psql -U story_user -d story_app -c \
"SELECT version, description, applied_at FROM schema_migrations ORDER BY version;"
```

---

## 🧪 Tests de migration

### Test 1 : Création simple

**Fichier : `20251119_test_simple.sql`**

```sql
BEGIN;
CREATE TABLE test_table (id SERIAL PRIMARY KEY, nom VARCHAR(50));
COMMIT;
```

**Test :**

```bash
docker exec -it story_php php /var/www/scripts/migrate.php up
docker exec -it story_postgres psql -U story_user -d story_app -c "\dt test_table"
```

**Nettoyage :**

```sql
DROP TABLE test_table;
DELETE FROM schema_migrations WHERE version = '20251119_test';
```

---

### Test 2 : Migration avec erreur (rollback automatique)

**Fichier : `20251119_test_erreur.sql`**

```sql
BEGIN;
CREATE TABLE test_table (id SERIAL PRIMARY KEY);
ALTER TABLE table_inexistante ADD COLUMN test VARCHAR(50); -- Échoue
COMMIT;
```

**Résultat attendu :**

- Migration échoue
- Aucune table créée (rollback automatique)
- Message d'erreur clair

---

## 📚 Bonnes pratiques

### ✅ À faire

1. **Nommer clairement** : `20251119_1430_add_user_roles` (pas `migration_001`)
2. **Une responsabilité** : Une migration = un changement logique
3. **BEGIN/COMMIT** : Toujours entourer les commandes
4. **Index** : Créer les index dans la même migration
5. **Documentation** : Commenter les parties complexes
6. **Tester** : Toujours tester en local avant commit

### ❌ À éviter

1. **Modifier une migration appliquée** : Créer une nouvelle à la place
2. **Dépendances externes** : Ne pas référencer des fichiers hors BDD
3. **Données hardcodées** : Éviter les UUID fixes (sauf tests)
4. **Suppressions destructives** : Toujours backuper avant `DROP TABLE`
5. **INSERT massif** : Préférer un script séparé pour les grosses données

---

## 🔐 Sécurité

### Migrations sensibles

Si la migration contient des données sensibles (seeds utilisateurs, etc.) :

#### 1. Créer un fichier `.sql.example`

```sql
-- 20251119_seed_admin.sql.example
BEGIN;
INSERT INTO users (email, password_hash, username)
VALUES ('admin@example.com', '$2y$...', 'admin');
COMMIT;
```

#### 2. Ajouter `.sql` au `.gitignore` (si besoin)

```BASH
database/migrations/*_seed_*.sql
```

#### 3. Documenter dans `SETUP.md`

```markdown
## Créer l'utilisateur admin

Copier `20251119_seed_admin.sql.example` en `20251119_seed_admin.sql`
et modifier les valeurs avant d'appliquer.
```

---

## 📈 Monitoring

### Logs de migration

**Stdout :**

```bash
docker compose logs -f php | grep "Applying"
```

**PostgreSQL :**

```bash
docker compose logs -f postgres | grep "ERROR"
```

---

## 🚀 Prochaines améliorations

1. **Rollback** : Implémenter `migrate.php down` avec fichiers `*_down.sql`
2. **Dry-run** : `migrate.php up --dry-run` pour simuler
3. **Backup auto** : Backup avant chaque migration en prod
4. **Notifications** : Slack/Discord après migration prod
5. **CI/CD** : Exécuter `verify` dans GitHub Actions

---

## 📝 Checklist avant commit

- [ ] Nom du fichier respecte `YYYYMMDD_HHmm_description.sql`
- [ ] BEGIN/COMMIT présents
- [ ] Pas d'INSERT INTO schema_migrations
- [ ] Migration testée localement (`up` + vérification BDD)
- [ ] `migrate.php verify` OK
- [ ] Commentaires clairs dans le SQL
- [ ] Rollback documenté (optionnel)

---

**Prêt à migrer ?** 🎯
