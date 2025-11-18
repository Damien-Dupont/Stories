-- Migration: 20251117_1145_add_special_scenes
-- Description: Ajoute les colonnes pour scènes spéciales (prologue, intermède, épilogue)

BEGIN;

-- Rendre chapter_id optionnel
ALTER TABLE scenes 
ALTER COLUMN chapter_id DROP NOT NULL;

-- Ajouter les nouvelles colonnes
ALTER TABLE scenes 
ADD COLUMN scene_type VARCHAR(50) DEFAULT 'standard';

ALTER TABLE scenes 
ADD COLUMN custom_type_label VARCHAR(100) NULL;

ALTER TABLE scenes 
ADD COLUMN sort_order INTEGER DEFAULT 0;

ALTER TABLE scenes 
ADD COLUMN emoji VARCHAR(10) NULL;

ALTER TABLE scenes 
ADD COLUMN image_url TEXT NULL;

-- Index pour performances
CREATE INDEX idx_scenes_sort_order ON scenes(sort_order);
CREATE INDEX idx_scenes_type ON scenes(scene_type);

-- Enregistrer la migration
INSERT INTO schema_migrations (version, description, script_name)
VALUES (
    '20251117_1145',
    'Ajout des colonnes pour scènes spéciales (prologue, emoji, image)',
    '20251117_1145_add_special_scenes.sql'
);

COMMIT;