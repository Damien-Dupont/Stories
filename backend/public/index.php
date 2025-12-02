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
require_once __DIR__ . '/../src/Controllers/ChapterController.php';
//require_once __DIR__ . '/../src/Controllers/EpisodeController.php';
//require_once __DIR__ . '/../src/Controllers/WorkController.php';

// Charger la connexion BDD
$pdo = require_once __DIR__ . '/../config/database.php';


// Initialisation du router
$router = new Router($pdo);

// Routes de base
$router->get('/', function () {
    echo json_encode([
        'status' => 'ok',
        'message' => 'Story App API',
        'version' => '1.0.0'
    ]);
});

$router->get('/health', function (PDO $pdo) {
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

$router->get('/works', function (PDO $pdo) {
    $stmt = $pdo->query('SELECT * FROM works ORDER BY created_at DESC');
    echo json_encode([
        'status' => 'ok',
        'data' => $stmt->fetchAll()
    ]);
});

// Routes relationnelles
//$router->get('/chapters/{id}/scenes', [SceneController::class, 'byChapter']);
//$router->get('/episodes/{id}/chapters', [ChapterController::class, 'byEpisode']);
// $router->get('/works/{id}/episodes', [EpisodeController::class,'byWork']);

// Routes CRUD Chapters
$router->get('/chapters', [ChapterController::class, 'index']);
$router->get('/chapters/{id}', [ChapterController::class, 'show']);
$router->post('/chapters', [ChapterController::class, 'create']);
// $router->put('/chapters/{id}', [ChapterController::class, 'update']);
// $router->delete('/chapters/{id}', [ChapterController::class, 'destroy']);

// Routes CRUD Scènes
$router->get('/scenes', [SceneController::class, 'index']);
$router->get('/scenes/{id}', [SceneController::class, 'show']);
$router->post('/scenes', [SceneController::class, 'create']);
$router->put('/scenes/{id}', [SceneController::class, 'update']);
$router->delete('/scenes/{id}', [SceneController::class, 'destroy']);

// Routes CRUD Episodes
// $router->get('/episodes', [EpisodeController::class,'index']);
// $router->get('/episodes/{id}', [EpisodeController::class, 'show']);
// $router->post('/episodess', [EpisodeController::class, 'create']);
// $router->put('/episodes/{id}', [EpisodeController::class, 'update']);
// $router->delete('/episodes/{id}', [EpisodeController::class, 'destroy']);

// Dispatch
$router->dispatch();
