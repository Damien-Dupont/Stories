-- database/migrations/20251208_2208_add_work_defaults.sql
-- Description: force des valeurs par défaut dans la table Works

BEGIN;

ALTER TABLE works 
ALTER COLUMN episode_label SET DEFAULT 'Épisode';

ALTER TABLE works 
ALTER COLUMN chapter_label SET DEFAULT 'Chapitre';

INSERT INTO schema_migrations (version, description, script_name)
VALUES (
    '20251208_2208',
    'Ajouter valeurs par défaut pour episode_label et chapter_label',
    '20251208_2208_add_work_defaults.sql'
);

COMMIT;