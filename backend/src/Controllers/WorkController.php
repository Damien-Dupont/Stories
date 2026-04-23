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

            JsonResponse::success($works);
        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /works/{id} - voir une œuvre
     * @param PDO $pdo
     * @param string $id
     * @return void
     */
    public static function show(PDO $pdo, string $id): void
    {
        try {
            $stmt = $pdo->prepare('
        SELECT *
        FROM works
        WHERE works.id = :id
        ');

            $stmt->execute(['id' => $id]);
            $work = $stmt->fetch();

            if (!$work) {
                JsonResponse::error('Work not found', 404);
                return;
            }

            JsonResponse::success($work);

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * POST /works - créer une nouvelle œuvre
     * @param PDO $pdo
     * @return void
     * Body JSON: {"id": "uuid", "title": "...", "description": "...", "author_id": "uuid", "episode_label": "...", "chapter_label": "...", "created_at": "date", "updated_at": "date", "published_date": "date", "deleted_date": "date"}
     */
    public static function create(PDO $pdo): void
    {
        try {

            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['title']) || trim($input['title']) === '') {
                JsonResponse::error('Missing required field: title', 400);
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

            JsonResponse::created('work created', [
                'id' => $result['id'],
                'created_at' => $result['created_at']
            ]);

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * UPDATE /works{id} - mettre à jour une œuvre
     * @param PDO $pdo
     * @param string $id
     * @return void
     */
    public static function update(PDO $pdo, string $id): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $fields = [];
            $params = ['id' => $id];

            // Champs modifiables
            $allowedFields = [
                'title',
                'description',
                'episode_label',
                'chapter_label',
                'number',
                'order_hint',
                'updated_at',
                'published_date',
                'deleted_date'
            ];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $fields[] = "$field = :$field";
                    $params[$field] = $input[$field];
                }
            }

            if (empty($fields)) {
                JsonResponse::error('No fields to update', 400);
                return;
            }

            $fields[] = 'updated_at = CURRENT_TIMESTAMP';
            $sql = 'UPDATE works SET ' . implode(', ', $fields) . ' WHERE id = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                JsonResponse::error('Work not found', 404);
                return;
            }

            JsonResponse::success(null, 'Work updated');

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /works/{id} - supprimer une œuvre
     * @param PDO $pdo
     * @param mixed $id
     * @return void
     */
    public static function destroy(PDO $pdo, $id)
    {
        try {
            $stmt = $pdo->prepare('
            DELETE FROM works WHERE id = :id
            ');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                JsonResponse::error('Work not found', 404);
                return;
            }
            JsonResponse::success(null, 'Work deleted');

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }
}