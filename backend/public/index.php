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

// Chargement des classes
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Controllers/SceneController.php';

// Charger la connexion BDD
$pdo = require_once __DIR__ . '/../config/database.php';


// Initialisation du router
$router = new Router($pdo);

// Routes de base
$router->get('/', function() {
    echo json_encode([
        'status' => 'ok',
        'message' => 'Story App API',
        'version' => '1.0.0'
    ]);
});

$router->get('/health', function(PDO $pdo) {
    try {
        $pdo->query('SELECT 1');
        echo json_encode([
            'status' => 'ok',
            'database' => 'connected'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'database' => 'disconnected'
        ]);
    }
});

$router->get('/works', function(PDO $pdo) {
    $stmt = $pdo->query('SELECT * FROM works ORDER BY created_at DESC');
    echo json_encode([
        'status' => 'ok',
        'data' => $stmt->fetchAll()
    ]);
});

// Routes CRUD Scènes
$router->get('/scenes', [SceneController::class, 'index']);
$router->get('/scenes/{id}', [SceneController::class, 'show']);
$router->post('/scenes', [SceneController::class, 'create']);
$router->put('/scenes/{id}', [SceneController::class, 'update']);
$router->delete('/scenes/{id}', [SceneController::class, 'destroy']);
$router->get('/chapters/{id}/scenes', [SceneController::class, 'byChapter']);

// Dispatch
$router->dispatch();