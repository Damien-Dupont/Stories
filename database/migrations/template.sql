-- Migration: YYYYMMDD_HHmm_description
-- Description: [Détails]
-- Author: Story App Team
-- Date: YYYY-MM-DD

-- ======================================
-- [SECTION 1]
-- ======================================

ALTER TABLE ...;

-- ======================================
-- [SECTION 2]
-- ======================================

CREATE TABLE ...;

-- ======================================
-- REGISTER MIGRATION
-- ======================================

INSERT INTO schema_migrations (version, description, script_name)
VALUES (
    'YYYYMMDD_HHmm',
    'Description courte',
    'YYYYMMDD_HHmm_description.sql'
);