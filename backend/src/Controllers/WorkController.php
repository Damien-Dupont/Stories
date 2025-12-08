<?php

class WorkController
{
    /**
     * Summary of index : list all works
     * @param PDO $pdo
     * @return void
     */
    public static function index(PDO $pdo): void
    {
        try {
            $stmt = $pdo->query('
                SELECT *
                FROM works
                ORDER BY created_at DESC
    ');

            $works = $stmt->fetchAll();

            echo json_encode([
                'status' => 'ok',
                'data' => $works
            ]);
        } catch (PDOException $e) {

            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }


    public static function create(PDO $pdo): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['title']) || trim($input['title']) === '') {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required field: title'
                ]);
                return;
            }

            // ✅ Nettoyer published (AVANT prepare())
            $published = false;
            if (array_key_exists('published', $input) && $input['published'] !== "" && $input['published'] !== null) {
                $published = (bool) $input['published'];
            }

            // ✅ Nettoyer description (AVANT prepare())
            $description = null;
            if (isset($input['description']) && trim($input['description']) !== '') {
                $description = trim($input['description']);
            }
            $episode_label = $input['episode_label'] ?? 'Épisode';
            $chapter_label = $input['chapter_label'] ?? 'Chapitre';

            $stmt = $pdo->prepare('
        INSERT INTO works
        (
        title,
        description,
        published,
        episode_label,
        chapter_label)
        VALUES
        (
        :title,
        :description,
        :published,
        :episode_label,
        :chapter_label)
        RETURNING id, created_at');


            // Lier explicitement les types
            $stmt->bindValue(':title', trim($input['title']), PDO::PARAM_STR);

            if ($description === null) {
                $stmt->bindValue(':description', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            }
            $stmt->bindValue(':published', $published, PDO::PARAM_BOOL);
            $stmt->bindValue(':episode_label', $episode_label, PDO::PARAM_STR);
            $stmt->bindValue(':chapter_label', $chapter_label, PDO::PARAM_STR);

            $stmt->execute();
            // $stmt->execute([
            //     'title' => trim($input['title']),
            //     'description' => $description,
            //     'published' => $published,
            //     'episode_label' => $input['episode_label'] ?? 'Épisode',
            //     'chapter_label' => $input['chapter_label'] ?? 'Chapitre'
            // ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            http_response_code(201);
            echo json_encode([
                'status' => 'ok',
                'message' => 'work created',
                'data' => [
                    'id' => $result['id'],
                    'created_at' => $result['created_at']
                ]
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}