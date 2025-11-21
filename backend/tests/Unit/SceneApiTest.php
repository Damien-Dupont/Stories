<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class SceneApiTest extends TestCase
{
    private Client $client;
    private PDO $pdo;
    private array $persistentData = [];

    protected function setUp(): void
    {
        // Client HTTP pour tester l'API
        $this->client = new Client([
            'base_uri' => 'http://nginx',
            'http_errors' => false // Ne pas throw sur 4xx/5xx
        ]);

        // Connexion BDD pour setup et cleanup
        $this->pdo = new PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $_ENV['DB_HOST'],
                $_ENV['DB_PORT'],
                $_ENV['DB_NAME']
            ),
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Nettoyer la BDD avant chaque test
        $this->cleanDatabase();

        // Créer les données de base (œuvre + chapitre)
        $this->seedTestData();
    }

    private function cleanDatabase(): void
    {
        $this->pdo->exec('TRUNCATE scenes, chapters, works RESTART IDENTITY CASCADE');
    }

    private function seedTestData(): void
    {
        // Créer une œuvre
        $stmt = $this->pdo->query("
            INSERT INTO works (title, published)
            VALUES ('Test Work', true)
            RETURNING id
        ");
        $this->persistentData['workId'] = $stmt->fetchColumn();

        // Créer un chapitre
        $stmt = $this->pdo->prepare("
            INSERT INTO chapters (work_id, title, number, order_hint)
            VALUES (:work_id, 'Test Chapter', 1, 1)
            RETURNING id
        ");
        $stmt->execute(['work_id' => $this->persistentData['workId']]);
        $this->persistentData['chapterId'] = $stmt->fetchColumn();

        // Créer uns scène
        $stmt = $this->pdo->prepare("
            INSERT INTO scenes (chapter_id, title, content_markdown, order_hint, scene_type, custom_type_label, sort_order, emoji, image_url )
            VALUES (:chapter_id, 'Test title', 'test content', 101, 'standard', null, 101, null, null)
            RETURNING id
        ");
        $stmt->execute(['chapter_id' => $this->persistentData['chapterId']]);
        $this->persistentData['sceneId'] = $stmt->fetchColumn();
    }

    /**
     * @test
     * Teste la création d'une scène standard via l'API
     */
    public function it_should_create_a_standard_scene()
    {
        // Données à envoyer
        $sceneToCreate = [
            'chapter_id' => $this->persistentData['chapterId'],
            'title' => 'Ma première scène',
            'content_markdown' => '# Titre\n\nContenu de la scène',
            'order_hint' => 1,
            'sort_order' => 200
        ];

        // Faire la requête POST
        $response = $this->client->post('/scenes', [
            'json' => $sceneToCreate
        ]);

        // Vérifications
        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertArrayHasKey('id', $data['data']);

        // Stocker l'ID pour les tests suivants si besoin
        $this->persistentData['sceneId'] = $data['data']['id'];

        // Vérifier en BDD que la scène est bien créée
        $stmt = $this->pdo->prepare('SELECT * FROM scenes WHERE id = :id');
        $stmt->execute(['id' => $this->persistentData['sceneId']]);
        $scene = $stmt->fetch();

        $this->assertEquals('Ma première scène', $scene['title']);
        $this->assertEquals('standard', $scene['scene_type']);
    }

    /**
     * @test
     * Teste la création d'une scène standard via l'API
     */
    public function it_should_create_a_special_scene_prologue()
    {
        // Variables du test
        $prologueTitle = 'Avant le texte, le prologue';
        $prologueSceneType = 'special';

        // Données à envoyer
        $prologueToCreate = [
            'custom_type_label' => 'Prologue',
            'title' => $prologueTitle,
            'content_markdown' => '# Titre\n\nContenu du prologue',
            'scene_type' => $prologueSceneType,
            'order_hint' => 1,
            'sort_order' => 100
        ];

        // Faire la requête POST
        $response = $this->client->post('/scenes', [
            'json' => $prologueToCreate
        ]);

        // Vérifications
        $this->assertNotEmpty($_ENV['DB_HOST']);
        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertArrayHasKey('id', $data['data']);

        // Stocker l'ID pour les tests suivants si besoin
        $this->persistentData['prologueId'] = $data['data']['id'];

        // Vérifier en BDD que la scène est bien créée
        $stmt = $this->pdo->prepare('SELECT * FROM scenes WHERE id = :id');
        $stmt->execute(['id' => $this->persistentData['prologueId']]);
        $scene = $stmt->fetch();

        $this->assertEquals($prologueTitle, $scene['title']);
        $this->assertEquals($prologueSceneType, $scene['scene_type']);
        $this->assertNull($scene['chapter_id']);
    }

     /**
     * @test
     * Teste la récupération d'une scène unique via l'API
     */
    public function it_should_get_single_scene()
    {
    // 1. ARRANGE : Créer une scène d'abord (il faut quelque chose à récupérer)
    $sceneToGetTitle = 'Ma scène à récupérer';
    $sceneToGetContent = '# Contenu';

    $sceneToCreate = [
        'chapter_id' => $this->persistentData['chapterId'],
        'title' => $sceneToGetTitle,
        'content_markdown' => '# Contenu',
        'sort_order' => 200
    ];

    $createResponse = $this->client->post('/scenes', [
        'json' => $sceneToCreate
    ]);

    $createData = json_decode($createResponse->getBody(), true);
    $sceneId = $createData['data']['id'];

    // 2. ACT : Récupérer la scène via GET
    $response = $this->client->get('/scenes/' . $sceneId);

    // 3. ASSERT : Vérifier la réponse
    $this->assertEquals(200, $response->getStatusCode());

    $data = json_decode($response->getBody(), true);
    $this->assertEquals('ok', $data['status']);

    // Vérifier les données de la scène
    $this->assertEquals($sceneToGetTitle, $data['data']['title']);
    $this->assertEquals($sceneToGetContent, $data['data']['content_markdown']);
    $this->assertEquals($this->persistentData['chapterId'], $data['data']['chapter_id']);

    // Vérifier que le titre du chapitre est inclus (grâce au LEFT JOIN)
    $this->assertEquals('Test Chapter', $data['data']['chapter_title']);
    }

     /**
     * @test
     * Teste le retour d'erreur 404 d'un GET sur id inconnu
     */
    public function it_should_return_404_when_scene_not_found()
    {
    // ACT : Essayer de récupérer un UUID qui n'existe pas
    $response = $this->client->get('/scenes/00000000-0000-0000-0000-000000000000');

    // ASSERT
    $this->assertEquals(404, $response->getStatusCode());
    
    $data = json_decode($response->getBody(), true);
    $this->assertEquals('error', $data['status']);
    $this->assertStringContainsString('not found', strtolower($data['message']));
    }

}


