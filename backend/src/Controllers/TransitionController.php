<?php

class TransitionController
{
    /**
     * Récupère une scène par ID (méthode utilitaire interne)
     * @param PDO $pdo
     * @param string $sceneId
     * @return array|null Retourne les données de la scène ou null si non trouvée
     */
    private static function getSceneById(PDO $pdo, string $sceneId): ?array
    {
        try {
            $query = $pdo->prepare("SELECT title, emoji FROM scenes WHERE id = :id");
            $query->execute(["id" => $sceneId]);
            $scene = $query->fetch();

            return $scene ?: null;

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Vérifie que les deux scènes existent en base
     * @param PDO $pdo
     * @param string $sceneBeforeId
     * @param string $sceneAfterId
     * @return bool
     * @throws Exception avec message approprié si validation échoue
     */
    private static function validateScenesExist(PDO $pdo, string $sceneBeforeId, string $sceneAfterId): bool
    {
        $stmt = $pdo->prepare('
        SELECT COUNT(*) as count
        FROM scenes
        WHERE id IN (:before, :after)
    ');
        $stmt->execute([
            'before' => $sceneBeforeId,
            'after' => $sceneAfterId
        ]);
        $result = $stmt->fetch();

        if ($result['count'] != 2) {
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'One or both scenes not found'
            ]);
            // Retourner false pour que le contrôleur puisse faire un return
            return false;
        }

        return true;
    }

    /**
     * GET /transitions
     * @param PDO $pdo
     * @return void
     */
    public static function index(PDO $pdo): void
    {
    }

    /**
     * GET /transitions/{id}
     * @param PDO $pdo
     * @param string $id
     * @return void
     */
    public static function show(PDO $pdo, string $id): void
    {
    }

    /**
     * POST /transitions
     * @param PDO $pdo
     * @return void
     */
    public static function create(PDO $pdo): void
    {
        try {
            $input = json_decode(file_get_contents("php://input"), true);

            // Scene_id's must be set
            if (!isset($input['scene_before_id'], $input['scene_after_id'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing required fields: scene_id'
                ]);
                return;
            }

            // Forbid a transition between a scene and itself
            if ($input['scene_before_id'] === $input['scene_after_id']) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'A scene cannot follow itself: timeloop forbidden'
                ]);
                return;
            }

            // Validation : vérifier que les deux scènes existent
            if (!self::validateScenesExist($pdo, $input['scene_before_id'], $input['scene_after_id'])) {
                return;
            }

            if (empty($input['transition_label'])) {
                $sceneAfter = self::getSceneById($pdo, $input['scene_after_id']);

                $input['transition_label'] = $sceneAfter ? $sceneAfter['title'] : 'Suivant';
            }


            if (empty($input['transition_order'])) {
                $stmt = $pdo->prepare('
                    SELECT COALESCE(MAX(transition_order), 0) + 1 as next_order
                    FROM scene_transitions
                    WHERE scene_before_id = :scene_before_id
                ');
                $stmt->execute(['scene_before_id' => $input['scene_before_id']]);
                $orderResult = $stmt->fetch();
                $input['transition_order'] = $orderResult['next_order'];
            }
            // penser à créer un lien 'to be continued' réutilisable menant vers une page "salle d'attente" pour les liens vers des pages non publiées ou inexistantes

            $stmt = $pdo->prepare('INSERT INTO scene_transitions (scene_before_id, scene_after_id, transition_label, transition_order) VALUES (:scene_before_id, :scene_after_id, :transition_label, :transition_order) RETURNING id, created_at');
            $stmt->execute([
                'scene_before_id' => $input['scene_before_id'],
                'scene_after_id' => $input['scene_after_id'],
                'transition_label' => $input['transition_label'],
                'transition_order' => $input['transition_order']
            ]);
            $result = $stmt->fetch();

            http_response_code(201);
            echo json_encode([
                'status' => 'ok',
                'message' => 'Transition created',
                'data' => [
                    'id' => $result['id'],
                    'created_at' => $result['created_at']
                ]
            ]);

        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                http_response_code(409);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'This transition already exists'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Database error:' . $e->getMessage()
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Unexpected error' . $e->getMessage()
            ]);
        }
    }
}