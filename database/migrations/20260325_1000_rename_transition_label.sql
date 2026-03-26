-- Migration: 20260325_1000_rename_transition_label
-- Description: Renomme transition_label en label_forward pour clarifier le sens de navigation
-- Author: Story App Team
-- Date: 2026-03-25

BEGIN;

-- ======================================
-- RENAME COLUMN
-- ======================================

ALTER TABLE scene_transitions 
RENAME COLUMN transition_label TO label_forward;

-- ======================================
-- REGISTER MIGRATION
-- ======================================

INSERT INTO schema_migrations (version, description, script_name)
VALUES (
    '20260325_1000',
    'Renomme transition_label en label_forward',
    '20260325_1000_rename_transition_label.sql'
);

COMMIT;