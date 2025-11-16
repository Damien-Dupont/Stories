-- Création des tables de base pour Sprint 1 (Option A - MVP Lecture)

-- Table des œuvres
CREATE TABLE works (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    author_id UUID,
    published BOOLEAN DEFAULT FALSE,

    -- Nomenclature personnalisable
    episode_label VARCHAR(50) DEFAULT 'Épisode', -- 'Épisode', 'Partie', 'Livre', 'Saison', etc.
    chapter_label VARCHAR(50) DEFAULT 'Chapitre', -- 'Chapitre', 'Acte', etc.

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des épisodes
CREATE TABLE episodes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    work_id UUID NOT NULL REFERENCES works(id) ON DELETE CASCADE,
    title VARCHAR(255) NULL,
    number INTEGER,
    order_hint INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des chapitres
CREATE TABLE chapters (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    work_id UUID NOT NULL REFERENCES works(id) ON DELETE CASCADE,
    episode_id UUID NULL REFERENCES episodes(id) ON DELETE CASCADE,
    title VARCHAR(255) NULL,
    number INTEGER,
    order_hint INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Un chapitre peut être rattaché directement à l'œuvre OU à un épisode
    CHECK (episode_id IS NOT NULL OR work_id IS NOT NULL)
);

-- Table des scènes
CREATE TABLE scenes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    chapter_id UUID NOT NULL REFERENCES chapters(id) ON DELETE CASCADE,
    title VARCHAR(255) NULL,
    content_markdown TEXT NOT NULL,
    order_hint INTEGER DEFAULT 0,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des transitions entre scènes
CREATE TABLE scene_transitions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    scene_before_id UUID NOT NULL REFERENCES scenes(id) ON DELETE CASCADE,
    scene_after_id UUID NOT NULL REFERENCES scenes(id) ON DELETE CASCADE,
    is_sequential BOOLEAN NOT NULL DEFAULT TRUE,
    custom_label TEXT NULL,
    display_order INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(scene_before_id, scene_after_id),
    CHECK (scene_before_id != scene_after_id)
);

-- Index pour les performances
CREATE INDEX idx_scenes_chapter ON scenes(chapter_id);
CREATE INDEX idx_chapters_work ON chapters(work_id);
CREATE INDEX idx_chapters_episode ON chapters(episode_id);
CREATE INDEX idx_episodes_work ON episodes(work_id);
CREATE INDEX idx_transitions_before ON scene_transitions(scene_before_id);
CREATE INDEX idx_transitions_after ON scene_transitions(scene_after_id);

-- Insertion d'une œuvre de test
INSERT INTO works (id, title, description, published, episode_label, chapter_label)
VALUES ('00000000-0000-0000-0000-000000000001', 'Mon Roman', 'Description de test', true, 'Épisode', 'Chapitre');