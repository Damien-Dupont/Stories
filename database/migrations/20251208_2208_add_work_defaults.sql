-- database/migrations/20251208_2208_add_work_defaults.sql
-- Description: force des valeurs par défaut dans la table Works

ALTER TABLE works 
ALTER COLUMN episode_label SET DEFAULT 'Épisode';

ALTER TABLE works 
ALTER COLUMN chapter_label SET DEFAULT 'Chapitre';
