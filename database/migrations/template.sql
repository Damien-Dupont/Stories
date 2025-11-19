-- Migration: YYYYMMDD_HHmm_description_courte
-- Description: Détail de ce que fait cette migration
-- Auteur: Ton nom
-- Date: YYYY-MM-DD

-- ⚠️ IMPORTANT : Ne PAS inclure d'INSERT INTO schema_migrations
-- C'est géré automatiquement par migrate.php

BEGIN;

-- ==============================================
-- 1. Modifications de structure (si applicable)
-- ==============================================

-- Exemple : Ajouter une colonne
-- ALTER TABLE ma_table 
-- ADD COLUMN nouvelle_colonne VARCHAR(255) NULL;

-- Exemple : Créer une table
-- CREATE TABLE nouvelle_table (
--     id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
--     nom VARCHAR(255) NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- Exemple : Créer un index
-- CREATE INDEX idx_ma_table_colonne ON ma_table(colonne);

-- ==============================================
-- 2. Migration de données (si applicable)
-- ==============================================

-- Exemple : Remplir une nouvelle colonne
-- UPDATE ma_table 
-- SET nouvelle_colonne = 'valeur_par_defaut'
-- WHERE nouvelle_colonne IS NULL;

-- ==============================================
-- 3. Contraintes finales (si applicable)
-- ==============================================

-- Exemple : Rendre une colonne NOT NULL après remplissage
-- ALTER TABLE ma_table 
-- ALTER COLUMN nouvelle_colonne SET NOT NULL;

COMMIT;

-- ==============================================
-- ROLLBACK (optionnel, commenté)
-- ==============================================
-- Si tu veux documenter comment annuler cette migration :

-- BEGIN;
-- ALTER TABLE ma_table DROP COLUMN nouvelle_colonne;
-- COMMIT;