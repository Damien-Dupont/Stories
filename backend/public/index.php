<?php

/**
 * Point d'entrée de l'API REST
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Charger la connexion BDD
$pdo = require_once __DIR__ . '/../config/database.php';

// Router simple
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');

// Route : GET /
if ($method === 'GET' && $path === '') {
    echo json_encode([
        'status' => 'ok',
        'message' => 'Story App API',
        'version' => '1.0.0'
    ]);
    exit;
}

// Route : GET /health
if ($method === 'GET' && $path === '/health') {
    try {
        // Tester la connexion BDD
        $stmt = $pdo->query('SELECT 1');
        echo json_encode([
            'status' => 'ok',
            'database' => 'connected'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'database' => 'disconnected',
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Route : GET /works
if ($method === 'GET' && $path === '/works') {
    try {
        $stmt = $pdo->query('SELECT * FROM works ORDER BY created_at DESC');
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
    exit;
}

// Route non trouvée
http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Route not found'
]);