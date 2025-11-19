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
                sc.id as scene_id,
                sc.title as scene_title,
                sc.content_markdown,
                sc.scene_type,
                sc.custom_type_label,
                sc.sort_order,
                sc.emoji,
                sc.image_url,
                sc.order_hint,
                sc.published_at,
                sc.created_at,
                ch.title as chapter_title,
                ch.id as chapter_id,
                ch.number as chapter_number
            FROM scenes sc
            LEFT JOIN chapters ch ON ch.id = sc.chapter_id
            ORDER BY sc.sort_order ASC, sc.order_hint ASC
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
                    ch.title as chapter_title,
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

            // Validation minimale
        if (!isset($input['title'], $input['content_markdown'])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing required fields: title, content_markdown'
            ]);
            return;
        }

        // Validation cohérence scène spéciale
        self::validateSpecialScene($input);

        $stmt = $pdo->prepare('
            INSERT INTO scenes (
                chapter_id, title, content_markdown, order_hint,
                scene_type, custom_type_label, sort_order, emoji, image_url
            )
            VALUES (
                :chapter_id, :title, :content_markdown, :order_hint,
                :scene_type, :custom_type_label, :sort_order, :emoji, :image_url
            )
            RETURNING id, created_at
        ');

           $stmt->execute([
            'chapter_id' => $input['chapter_id'] ?? null,
            'title' => $input['title'],
            'content_markdown' => $input['content_markdown'],
            'order_hint' => $input['order_hint'] ?? 0,
            'scene_type' => $input['scene_type'] ?? 'standard',
            'custom_type_label' => $input['custom_type_label'] ?? null,
            'sort_order' => $input['sort_order'] ?? 0,
            'emoji' => $input['emoji'] ?? null,
            'image_url' => $input['image_url'] ?? null
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

        // Champs modifiables
        $allowedFields = [
            'title', 'content_markdown', 'order_hint',
            'scene_type', 'custom_type_label', 'sort_order',
            'emoji', 'image_url', 'chapter_id'
        ];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $input[$field];
            }
        }

        // Validation cohérence scène spéciale
        self::validateSpecialScene($input);

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

    /**
    * Valide qu'une scène spéciale n'a pas de chapter_id
    * @throws InvalidArgumentException si validation échoue
    */
    private static function validateSpecialScene(array $input): void
    {
    if (isset($input['scene_type']) && $input['scene_type'] === 'special') {
        if (isset($input['chapter_id']) && $input['chapter_id'] !== null) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Special scenes cannot have a chapter_id'
            ]);
            exit; // ou throw new Exception()
        }
    }
}
}