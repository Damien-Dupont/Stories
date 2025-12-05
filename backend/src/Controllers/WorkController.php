<?php

class WorkController
{
    /**
     * GET /works - liste toutes les oeuvres
     */
    public static function index(PDO $pdo): void
    {
        try {
            $stmt = $pdo->query('
                SELECT *
                FROM works
                ORDER BY updated_at DESC
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

}