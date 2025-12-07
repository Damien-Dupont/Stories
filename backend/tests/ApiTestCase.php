<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

abstract class ApiTestCase extends TestCase
{
    protected Client $client;
    protected PDO $pdo;
    protected array $persistentData = [];

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

        // Créer les données de base
        $this->seedTestData();
    }
    protected function cleanDatabase(): void
    {
        $this->pdo->exec('TRUNCATE scenes, chapters, works RESTART IDENTITY CASCADE');
    }

    /**
     * Helper : Créer une oeuvre de test
     * @param array $data Données personnalisées (écrase les valeurs par défaut)
     * @return string UUID de l'oeuvre créée
     */
    protected function createTestWork(array $data = []): string
    // id, title, description, author_id, published, episode_label, chapter_label, created_at, updated_at
    {
        $defaults = [
            'title' => 'Test Work',
            'description' => 'any description',
            // 'author_id' => '',
            'published' => true,
            'episode_label' => 'Épisode',
            'chapter_label' => 'Chapitre'
        ];

        $workData = array_merge($defaults, $data);

        // $stmt = $this->pdo->prepare("
        //     INSERT INTO works (title, description, author_id, published, episode_label, chapter_label)
        //     VALUES (:title, :description, :author_id, :published, :episode_label, :chapter_label)
        //     RETURNING id
        // ");

        $stmt = $this->pdo->prepare("
            INSERT INTO works (title, description, published, episode_label, chapter_label)
            VALUES (:title, :description, :published, :episode_label, :chapter_label)
            RETURNING id
        ");

        $stmt->execute($workData);
        return $stmt->fetchColumn();
    }

    /**
     * Helper : Créer un chapitre de test
     */
    protected function createTestChapter(array $data = []): string
    {
        $defaults = [
            'work_id' => $this->persistentData['workId'],
            'episode_id' => null,
            'title' => 'Test Chapter',
            'number' => 1,
            'order_hint' => 1
        ];

        $response = $this->client->post('/chapters', [
            'json' => array_merge($defaults, $data)
        ]);

        return json_decode($response->getBody(), true)['data']['id'];
    }

    /**
     * Helper : Créer une scène de test
     * @param array $data Données personnalisées (écrase les valeurs par défaut)
     * @return string UUID de la scène créée
     */
    protected function createTestScene(array $data = []): string
    {
        $defaults = [
            'chapter_id' => $this->persistentData['chapterId'],
            'title' => 'Test Scene',
            'content_markdown' => '# Content',
            'sort_order' => 200
        ];

        $response = $this->client->post('/scenes', [
            'json' => array_merge($defaults, $data)
        ]);

        return json_decode($response->getBody(), true)['data']['id'];
    }

    protected function seedTestData(): void
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

}


