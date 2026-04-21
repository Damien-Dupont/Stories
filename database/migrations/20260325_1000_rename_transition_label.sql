-- Migration: 20260325_1000_rename_transition_label
-- Description: Renomme transition_label en label_forward pour clarifier le sens de navigation
-- Author: Story App Team
-- Date: 2026-03-25

-- ======================================
-- RENAME COLUMN
-- ======================================

ALTER TABLE scene_transitions 
RENAME COLUMN transition_label TO label_forward;
