-- Migration: 20251209_0700_improve_works_schema.sql (VERSION CORRIGEE)
-- Description: Remplacer published par published_date + ajouter deleted_date (idempotent)

BEGIN;

-- 1) S'assurer que published_date existe (si absent)
ALTER TABLE works
ADD COLUMN IF NOT EXISTS published_date TIMESTAMP NULL DEFAULT NULL;

-- 2) Si la colonne 'published' existe, migrer sa valeur vers 'published_date' puis la supprimer
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name = 'works'
          AND column_name = 'published'
    ) THEN
        -- Remplir published_date pour les lignes marquées published = true
        -- On choisit created_at comme valeur de référence si disponible, sinon NOW().
        UPDATE works
        SET published_date = COALESCE(created_at, now())
        WHERE published IS TRUE AND published_date IS NULL;

        -- Si vous voulez marquer les lignes non-publiées avec NULL, on ne fait rien (déjà NULL)

        -- Puis supprimer la colonne boolean
        ALTER TABLE works DROP COLUMN published;
    END IF;
END
$$;

-- 3) Ajouter deleted_date pour soft deletes si absent
ALTER TABLE works
ADD COLUMN IF NOT EXISTS deleted_date TIMESTAMP NULL;

-- 4) Index sur published_date (si absent)
CREATE INDEX IF NOT EXISTS idx_works_published ON works(published_date);

-- 5) Enregistrer la migration dans schema_migrations seulement si elle n'y est pas déjà
INSERT INTO schema_migrations (version, description, script_name)
SELECT '20251209_0700',
       'Remplacer published par published_at + soft deletes',
       '20251209_0700_improve_works_schema.sql'
WHERE NOT EXISTS (
    SELECT 1 FROM schema_migrations WHERE version = '20251209_0700'
);

COMMIT;
