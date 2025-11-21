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
}
