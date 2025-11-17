<?php

class SceneController
{
    /**
     * GET /scenes - Liste toutes les scènes
     */
    public static function index(PDO $pdo): void
    {
        try {
            $stmt = $pdo->query('
                SELECT
                    sc.id as scene_id, sc.title as scene_title,
                    sc.content_markdown, sc.order_hint,
                    sc.published_at, sc.created_at,
                    ch.title as chapter_title, ch.id as chapter_id
                FROM scenes sc
                JOIN chapters ch ON ch.id = sc.chapter_id
                ORDER BY ch.order_hint, sc.order_hint
            ');

            $scenes = $stmt->fetchAll();

            echo json_encode([
                'status' => 'ok',
                'data' => $scenes
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /scenes/{id} - Détails d'une scène
     */
    public static function show(PDO $pdo, string $id): void
    {
        try {
            $stmt = $pdo->prepare('
                SELECT
                    sc.*,
                    ch.title as chapter_title, ch.id as chapter_id,
                    ch.number as chapter_number
                FROM scenes sc
                JOIN chapters ch ON ch.id = sc.chapter_id
                WHERE sc.id = :id
            ');

            $stmt->execute(['id' => $id]);
            $scene = $stmt->fetch();

            if (!$scene) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Scene not found'
                ]);
                return;
            }

            echo json_encode([
                'status' => 'ok',
                'data' => $scene
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * POST /scenes - Créer une nouvelle scène
     * Body JSON: {"chapter_id": "uuid", "title": "...", "content_markdown": "...", "order_hint": 0}
     */
    public static function create(PDO $pdo): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validation
            if (!isset($input['chapter_id'], $input['title'], $input['content_markdown'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields: chapter_id, title, content_markdown'
                ]);
                return;
            }

            $stmt = $pdo->prepare('
                INSERT INTO scenes (chapter_id, title, content_markdown, order_hint)
                VALUES (:chapter_id, :title, :content_markdown, :order_hint)
                RETURNING id, created_at
            ');

            $stmt->execute([
                'chapter_id' => $input['chapter_id'],
                'title' => $input['title'],
                'content_markdown' => $input['content_markdown'],
                'order_hint' => $input['order_hint'] ?? 0
            ]);

            $result = $stmt->fetch();

            http_response_code(201);
            echo json_encode([
                'status' => 'ok',
                'message' => 'Scene created',
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

    /**
     * PUT /scenes/{id} - Modifier une scène
     */
    public static function update(PDO $pdo, string $id): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $fields = [];
            $params = ['id' => $id];

            if (isset($input['title'])) {
                $fields[] = 'title = :title';
                $params['title'] = $input['title'];
            }

            if (isset($input['content_markdown'])) {
                $fields[] = 'content_markdown = :content_markdown';
                $params['content_markdown'] = $input['content_markdown'];
            }

            if (isset($input['order_hint'])) {
                $fields[] = 'order_hint = :order_hint';
                $params['order_hint'] = $input['order_hint'];
            }

            if (empty($fields)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No fields to update'
                ]);
                return;
            }

            $fields[] = 'updated_at = CURRENT_TIMESTAMP';
            $sql = 'UPDATE scenes SET ' . implode(', ', $fields) . ' WHERE id = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Scene not found'
                ]);
                return;
            }

            echo json_encode([
                'status' => 'ok',
                'message' => 'Scene updated'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * DELETE /scenes/{id} - Supprimer une scène
     */
    public static function destroy(PDO $pdo, string $id): void
    {
        try {
            $stmt = $pdo->prepare('DELETE FROM scenes WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Scene not found'
                ]);
                return;
            }

            echo json_encode([
                'status' => 'ok',
                'message' => 'Scene deleted'
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /chapters/{id}/scenes - Scènes d'un chapitre
     */
    public static function byChapter(PDO $pdo, string $chapterId): void
    {
        try {
            $stmt = $pdo->prepare('
                SELECT * FROM scenes
                WHERE chapter_id = :chapter_id
                ORDER BY order_hint
            ');

            $stmt->execute(['chapter_id' => $chapterId]);
            $scenes = $stmt->fetchAll();

            echo json_encode([
                'status' => 'ok',
                'data' => $scenes
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