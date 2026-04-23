<?php

class ChapterController
{
    /**
     * GET /chapters - Liste tous les chapitres
     */
    public static function index(PDO $pdo): void
    {
        try {
            $stmt = $pdo->query('
                SELECT
                ch.id as chapter_id,
                ch.work_id,
                ch.episode_id,
                ch.number as chapter_number,
                ch.title as chapter_title,
                ch.order_hint,
                ch.created_at
            FROM chapters ch
            LEFT JOIN episodes ep ON ep.id = ch.episode_id
            ORDER BY ch.order_hint ASC
        ');

            $chapters = $stmt->fetchAll();

            JsonResponse::success($chapters);
        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * GET /chapters/{id} - Détails d'un chapitre
     */
    public static function show(PDO $pdo, string $id): void
    {
        try {
            $stmt = $pdo->prepare('
                SELECT
                    ch.*,
                    ep.title as episode_title,
                    ep.number as episode_number
                FROM chapters ch
                LEFT JOIN episodes ep ON ch.episode_id = ep.id
                WHERE ch.id = :id
            ');

            $stmt->execute(['id' => $id]);
            $chapter = $stmt->fetch();

            if (!$chapter) {
                JsonResponse::error('Chapter not found', 404);
                return;
            }

            JsonResponse::success($chapter);
        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);

        }
    }

    /**
     * POST /chapters - Créer un nouveau chapitre
     * Body JSON: {"id": "uuid", "title": "...", "content_markdown": "...", "order_hint": 0}
     */
    public static function create(PDO $pdo): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validation minimale
            if (!isset($input['work_id'])) {
                JsonResponse::error('Missing required fields: work_id', 400);
                return;
            }

            // Génération automatique du titre si absent
            if (empty($input['title'])) {
                $number = $input['number'] ?? $input['order_hint'] ?? 0;
                $input['title'] = 'Chapitre ' . $number;
            }

            $stmt = $pdo->prepare('
            INSERT INTO chapters (
                work_id, episode_id, title, number, order_hint
            )
            VALUES (
                :work_id, :episode_id, :title, :number, :order_hint
            )
            RETURNING id, created_at
        ');

            $stmt->execute([
                'work_id' => $input['work_id'],
                'episode_id' => $input['episode_id'] ?? null,
                'title' => $input['title'],
                'number' => $input['number'] ?? null,
                'order_hint' => $input['order_hint'] ?? 0
            ]);

            $result = $stmt->fetch();

            JsonResponse::created('Chapter created', [
                'id' => $result['id'],
                'created_at' => $result['created_at']
            ]);
        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * PUT /chapters/{id} - Modifier un chapitre
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
                'number',
                'order_hint',
                'episode_id'
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

            //  $fields[] = 'updated_at = CURRENT_TIMESTAMP';
            // TODO: modifier BDD pour ajouter updated_at dans la table Chapitre
            $sql = 'UPDATE chapters SET ' . implode(', ', $fields) . ' WHERE id = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                JsonResponse::error('Chapter not found', 404);
                return;
            }

            JsonResponse::success(null, 'Chapter updated');

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /chapters/{id} - Supprimer un chapitre
     */
    public static function destroy(PDO $pdo, string $id): void
    {
        try {
            $stmt = $pdo->prepare('DELETE FROM chapters WHERE id = :id');
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() === 0) {
                JsonResponse::error('Chapter not found', 404);
                return;
            }

            JsonResponse::success(null, 'Chapter deleted');
        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    // /**
    //  * GET /chapters/{id}/scenes - Scènes d'un chapitre
    //  */
    // public static function byChapter(PDO $pdo, string $chapterId): void
    // {
    //     try {
    //         $stmt = $pdo->prepare('
    //             SELECT * FROM scenes
    //             WHERE chapter_id = :chapter_id
    //             ORDER BY order_hint
    //         ');

    //         $stmt->execute(['chapter_id' => $chapterId]);
    //         $scenes = $stmt->fetchAll();

    // JsonResponse::success($scenes);
    //     } catch (PDOException $e) {
    //          JsonResponse::error($e->getMessage(), 500);
    //     }
    // }

    // /**
    //  * Valide qu'une scène spéciale n'a pas de chapter_id
    //  * @throws InvalidArgumentException si validation échoue
    //  */
    // private static function validateSpecialScene(array $input): void
    // {
    //     if (isset($input['scene_type']) && $input['scene_type'] === 'special') {
    //         if (isset($input['chapter_id']) && $input['chapter_id'] !== null) {
    //   JsonResponse::error('Special scenes cannot have a chapter_id', 400);
    //             exit; // ou throw new Exception()
    //         }
    //     }
    // }
}