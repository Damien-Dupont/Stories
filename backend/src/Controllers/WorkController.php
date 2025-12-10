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

            http_response_code(200);
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

            $stmt = $pdo->prepare('
        INSERT INTO works (
            title, description, published_date, episode_label, chapter_label
        )
        VALUES (
            :title, :description, :published_date, :episode_label, :chapter_label
        )
        RETURNING id, created_at');

            $stmt->execute([
                'title' => trim($input['title']),
                'description' => $input['description'] ?? null,
                'published_date' => $input['published_date'] ?? null,
                'episode_label' => $input['episode_label'] ?? 'Épisode',
                'chapter_label' => $input['chapter_label'] ?? 'Chapitre'
            ]);

            $result = $stmt->fetch();

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