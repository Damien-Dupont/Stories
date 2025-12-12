-- Migration: 20251211_1800_refactor_ordering_and_labels
-- Description: Refactor ordering and labeling for clarity
--              - scenes: sort_order → global_order, remove order_hint
--              - scene_transitions: remove is_sequential, rename custom_label → transition_label, display_order → transition_order
-- Author: Story App Team
-- Date: 2024-12-11

BEGIN;

-- ======================================
-- 1. REFACTOR TABLE scenes
-- ======================================

-- Remove redundant order_hint column
ALTER TABLE scenes 
DROP COLUMN IF EXISTS order_hint;

-- Rename sort_order to global_order for clarity
ALTER TABLE scenes 
RENAME COLUMN sort_order TO global_order;

-- Update index name for consistency
DROP INDEX IF EXISTS idx_scenes_sort_order;
CREATE INDEX idx_scenes_global_order ON scenes(global_order);

-- ======================================
-- 2. REFACTOR TABLE scene_transitions
-- ======================================

-- Remove redundant is_sequential column (can be computed from transition count)
ALTER TABLE scene_transitions 
DROP COLUMN IF EXISTS is_sequential;

-- Rename custom_label to transition_label (avoid confusion with episode_label/chapter_label)
ALTER TABLE scene_transitions 
RENAME COLUMN custom_label TO transition_label;

-- Rename display_order to transition_order (avoid confusion with scenes.global_order)
ALTER TABLE scene_transitions 
RENAME COLUMN display_order TO transition_order;

-- ======================================
-- 3. REGISTER MIGRATION
-- ======================================

INSERT INTO schema_migrations (version, description, script_name)
VALUES (
    '20251211_1800',
    'Refactor ordering and labeling: scenes.global_order, transitions.transition_label/transition_order',
    '20251211_1800_refactor_ordering_and_labels.sql'
);

COMMIT;

-- ======================================
-- ROLLBACK (optionnel, commenté)
-- ======================================
-- BEGIN;
-- 
-- -- Revert scene_transitions
-- ALTER TABLE scene_transitions RENAME COLUMN transition_order TO display_order;
-- ALTER TABLE scene_transitions RENAME COLUMN transition_label TO custom_label;
-- ALTER TABLE scene_transitions ADD COLUMN is_sequential BOOLEAN NOT NULL DEFAULT TRUE;
-- 
-- -- Revert scenes
-- DROP INDEX IF EXISTS idx_scenes_global_order;
-- ALTER TABLE scenes RENAME COLUMN global_order TO sort_order;
-- ALTER TABLE scenes ADD COLUMN order_hint INTEGER DEFAULT 0;
-- CREATE INDEX idx_scenes_sort_order ON scenes(sort_order);
-- 
-- -- Remove migration record
-- DELETE FROM schema_migrations WHERE version = '20251211_1800';
-- 
-- COMMIT;