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
     * Summary of getTransitionById
     * @param PDO $pdo
     * @param string $id
     */
    private static function getTransitionById(PDO $pdo, string $id): ?array
    {
        try {
            $query = $pdo->prepare('SELECT scene_before_id, scene_after_id, label_forward, transition_order FROM scene_transitions WHERE id = :id');
            $query->execute(['id' => $id]);
            $transition = $query->fetch();

            return $transition ?: null;

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Summary of recalculateOrdersAfter
     * @param PDO $pdo
     * @param string $sceneBeforeId
     * @param int $deletedOrder
     * @return void
     */
    private static function recalculateOrdersAfter(PDO $pdo, string $sceneBeforeId, int $deletedOrder): void
    {
        $stmt = $pdo->prepare('UPDATE scene_transitions SET transition_order = transition_order -1 WHERE scene_before_id = :scene_before_id AND transition_order > :deleted_order');
        $stmt->execute([
            'scene_before_id' => $sceneBeforeId,
            'deleted_order' => $deletedOrder
        ]);
    }

    /**
     * Supprime une transition par ID (méthode utilitaire interne)
     * @param PDO $pdo
     * @param string $id
     * @return void
     */
    private static function deleteTransition(PDO $pdo, string $id): void
    {
        $stmt = $pdo->prepare('DELETE FROM scene_transitions WHERE id = :id');
        $stmt->execute(['id' => $id]);
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

        // Test 1 : Chercher Scene Before individuellement
        $test1 = $pdo->prepare('SELECT id, title FROM scenes WHERE id = :id');
        $test1->execute(['id' => $sceneBeforeId]);
        $found1 = $test1->fetch();
        error_log("Scene Before exists: " . ($found1 ? 'YES - ' . $found1['title'] : 'NO'));

        // Test 2 : Chercher Scene After individuellement
        $test2 = $pdo->prepare('SELECT id, title FROM scenes WHERE id = :id');
        $test2->execute(['id' => $sceneAfterId]);
        $found2 = $test2->fetch();
        error_log("Scene After exists: " . ($found2 ? 'YES - ' . $found2['title'] : 'NO'));

        $stmt = $pdo->prepare('
        SELECT COUNT(*) as count
        FROM scenes
        WHERE id::text = :before OR id::text = :after
    ');
        $stmt->execute([
            'before' => $sceneBeforeId,
            'after' => $sceneAfterId
        ]);
        $result = $stmt->fetch();

        if ($result['count'] != 2) {
            JsonResponse::error('One or both scenes not found', 404);
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
        try {
            $stmt = $pdo->query('
            SELECT
            st.id as transition_id,
            st.scene_before_id as from_scene,
            st.scene_after_id as to_scene,
            st.label_forward,
            st.transition_order,
            st.created_at
            FROM scene_transitions st
            ORDER BY st.transition_order ASC
            ');

            $transitions = $stmt->fetchAll();

            JsonResponse::success($transitions);

        } catch (PDOException $e) {

            JsonResponse::error($e->getMessage(), 500);

        }
    }

    /**
     * GET /transitions/{id}
     * @param PDO $pdo
     * @param string $id
     * @return void
     */
    public static function show(PDO $pdo, string $id): void
    {
        try {
            $stmt = $pdo->prepare('
            SELECT
            st.id as transition_id,
            st.scene_before_id as from_scene,
            st.scene_after_id as to_scene,
            st.label_forward,
            st.transition_order,
            st.created_at
            FROM scene_transitions st
            WHERE st.id = :id
            ');

            $stmt->execute(['id' => $id]);
            $transition = $stmt->fetch();

            if (!$transition) {
                JsonResponse::error('Transition not found', 404);
                return;
            }

            JsonResponse::success($transition);

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
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
                JsonResponse::error('Missing required fields: scene_id', 400);

                return;
            }

            // Forbid a transition between a scene and itself
            if ($input['scene_before_id'] === $input['scene_after_id']) {
                JsonResponse::error('A scene cannot follow itself: timeloop forbidden', 400);
                return;
            }

            // Validation : vérifier que les deux scènes existent
            if (!self::validateScenesExist($pdo, $input['scene_before_id'], $input['scene_after_id'])) {
                return;
            }

            if (empty($input['label_forward'])) {
                $sceneAfter = self::getSceneById($pdo, $input['scene_after_id']);

                $input['label_forward'] = $sceneAfter ? $sceneAfter['title'] : 'Suivant';
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
            // TODO: penser à créer un lien 'to be continued' réutilisable menant vers une page "salle d'attente" pour les liens vers des pages non publiées ou inexistantes

            $stmt = $pdo->prepare('INSERT INTO scene_transitions (scene_before_id, scene_after_id, label_forward, transition_order) VALUES (:scene_before_id, :scene_after_id, :label_forward, :transition_order) RETURNING id, created_at');
            $stmt->execute([
                'scene_before_id' => $input['scene_before_id'],
                'scene_after_id' => $input['scene_after_id'],
                'label_forward' => $input['label_forward'],
                'transition_order' => $input['transition_order']
            ]);
            $result = $stmt->fetch();

            JsonResponse::created('Transition created', [
                'id' => $result['id'],
                'created_at' => $result['created_at']
            ]);

        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate key') !== false) {
                JsonResponse::error('This transition already exists', 409);
            } else {
                JsonResponse::error('Database error' . $e->getMessage(), 500);
            }
        } catch (Exception $e) {
            JsonResponse::error('Unexpected error' . $e->getMessage(), 500);
        }
    }

    /**
     * Summary of nextTransitions
     * @param PDO $pdo
     * @param string $sceneId
     * @return void
     */
    public static function nextTransitions(PDO $pdo, string $sceneId): void
    {
        try {
            // Validation : UUID format
            if (!preg_match('/^[0-9a-f-]{36}$/i', $sceneId)) {
                JsonResponse::error('Invalid scene ID format', 400);
                return;
            }

            // Validation : La scène existe-t-elle ?
            $checkScene = $pdo->prepare('SELECT id FROM scenes WHERE id = :id');
            $checkScene->execute(['id' => $sceneId]);
            if (!$checkScene->fetch()) {
                JsonResponse::error('Scene not found', 404);
                return;
            }

            $stmt = $pdo->prepare('
            SELECT
                st.id as transition_id,
                st.label_forward,
                st.transition_order,
                sc.id as scene_id,
                sc.title as scene_title,
                sc.emoji,
                sc.scene_type
            FROM scene_transitions st
            JOIN scenes sc ON sc.id = st.scene_after_id
            WHERE st.scene_before_id = :scene_id
            ORDER BY st.transition_order ASC');

            $stmt->execute(['scene_id' => $sceneId]);
            $nextTransitions = $stmt->fetchAll();

            JsonResponse::success($nextTransitions);

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * Summary of previousTransitions
     * @param PDO $pdo
     * @param string $sceneId
     * @return void
     */
    public static function previousTransitions(PDO $pdo, string $sceneId): void
    {
        try {
            // Validation : UUID format
            if (!preg_match('/^[0-9a-f-]{36}$/i', $sceneId)) {
                JsonResponse::error('Invalid scene ID format', 400);
                return;
            }

            // Validation : La scène existe-t-elle ?
            $checkScene = $pdo->prepare('SELECT id FROM scenes WHERE id = :id');
            $checkScene->execute(['id' => $sceneId]);
            if (!$checkScene->fetch()) {
                JsonResponse::error('Scene not found', 404);
                return;
            }

            $stmt = $pdo->prepare('
            SELECT
                st.id as transition_id,
                st.label_forward,
                st.transition_order,
                sc.id as scene_id,
                sc.title as scene_title,
                sc.emoji,
                sc.scene_type
            FROM scene_transitions st
            JOIN scenes sc ON sc.id = st.scene_before_id
            WHERE st.scene_after_id = :scene_id
            ORDER BY st.transition_order, st.created_at ASC
            ');
            $stmt->execute(['scene_id' => $sceneId]);
            $prevTransitions = $stmt->fetchAll();

            JsonResponse::success($prevTransitions);

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /transitions/{id} - Supprimer une transition
     */
    public static function destroy(PDO $pdo, string $id): void
    {
        try {
            $transition = self::getTransitionById($pdo, $id);

            if (!$transition) {
                JsonResponse::error('Transition not found', 404);
                return;
            }

            self::deleteTransition($pdo, $id);

            self::recalculateOrdersAfter($pdo, $transition['scene_before_id'], $transition['transition_order']);

            JsonResponse::success(null, 'Transition deleted');

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

    /**
     * PUT /transitions/{id}
     */
    public static function update(PDO $pdo, string $id): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $fields = [];
            $params = ['id' => $id];
            $allowedFields = ['label_forward', 'transition_order'];

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
            $sql = 'UPDATE scene_transitions SET ' . implode(',', $fields) . ' WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                JsonResponse::error('Transition not found', 404);
                return;
            }

            JsonResponse::success(null, 'Transition updated');

        } catch (PDOException $e) {
            JsonResponse::error($e->getMessage(), 500);
        }
    }

}
