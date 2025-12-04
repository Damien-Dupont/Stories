<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class ChapterApiTest extends TestCase
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

    /**
     * Helper : Créer une oeuvre de test
     * @param array $data Données personnalisées (écrase les valeurs par défaut)
     * @return string UUID de l'oeuvre créée
     */
    private function createTestWork(array $data = []): string
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
    private function createTestChapter(array $data = []): string
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

    private function seedTestData(): void
    {
        // Créer une œuvre
        $stmt = $this->pdo->query("
            INSERT INTO works (title, published)
            VALUES ('Test Work', true)
            RETURNING id
        ");
        $this->persistentData['workId'] = $stmt->fetchColumn();
        // $this->persistentData['workId'] = $this->createTestWork();

        // Créer un chapitre
        $stmt = $this->pdo->prepare("
            INSERT INTO chapters (work_id, title, number, order_hint)
            VALUES (:work_id, 'Chapitre premier', 1, 1)
            RETURNING id
        ");
        $stmt->execute(['work_id' => $this->persistentData['workId']]);
        $this->persistentData['chapterOneId'] = $stmt->fetchColumn();
    }

    // CRUD TESTS :: CREATION

    /**
     * @test
     * Teste la création d'un chapitre
     */
    public function CREATE__it_should_create_a_chapter()
    {
        // Données à envoyer
        $chapterToCreate = [
            'work_id' => $this->persistentData['workId'],
            'title' => 'Chapitre second',
            'number' => 2,
            'order_hint' => 2
        ];

        // Faire la requête POST
        $response = $this->client->post('/chapters', [
            'json' => $chapterToCreate
        ]);

        // Vérifications
        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertArrayHasKey('id', $data['data']);

        // Stocker l'ID pour les tests suivants si besoin
        $this->persistentData['chapterTwoId'] = $data['data']['id'];

        // Vérifier en BDD que le chapitre est bien créée
        $stmt = $this->pdo->prepare('SELECT * FROM chapters WHERE id = :id');
        $stmt->execute(['id' => $this->persistentData['chapterTwoId']]);
        $chapter = $stmt->fetch();

        $this->assertEquals('Chapitre second', $chapter['title']);
        $this->assertEquals(2, $chapter['number']);  // ← Bonus : vérifier aussi number
        $this->assertEquals($this->persistentData['workId'], $chapter['work_id']);
    }

    // /**
    //  * @test
    //  * Teste la génération auto d'un titre lors de la création d'une scène sans titre
    //  */
    // public function it_should_auto_generate_title_when_missing()
    // {
    //     // ARRANGE
    //     $sceneWithoutTitle = [
    //         'chapter_id' => $this->persistentData['chapterId'],
    //         'content_markdown' => '# Contenu',
    //         'order_hint' => 3
    //         // Pas de title
    //     ];

    //     // ACT
    //     $response = $this->client->post('/scenes', ['json' => $sceneWithoutTitle]);

    //     // ASSERT
    //     $this->assertEquals(201, $response->getStatusCode());

    //     $data = json_decode($response->getBody(), true);
    //     $sceneId = $data['data']['id'];

    //     // Vérifier en BDD que le titre a été auto-généré
    //     $stmt = $this->pdo->prepare('SELECT title FROM scenes WHERE id = :id');
    //     $stmt->execute(['id' => $sceneId]);
    //     $chapter = $stmt->fetch();

    //     $this->assertEquals('Scène 3', $chapter['title']);
    // }

    // /**
    //  * @test
    //  * Teste le rejet de création d'une scène sans contenu
    //  */
    // public function it_should_reject_scene_without_content_markdown()
    // {
    //     // ARRANGE
    //     $sceneWithoutContent = [
    //         'chapter_id' => $this->persistentData['chapterId'],
    //         'title' => 'Titre sans contenu'
    //         // Manque : content_markdown
    //     ];

    //     // ACT
    //     $response = $this->client->post('/scenes', ['json' => $sceneWithoutContent]);

    //     // ASSERT
    //     $this->assertEquals(400, $response->getStatusCode());
    // }

    // CRUD TESTS :: READ

    /**
     * @test
     * Teste la récupération d'un chapitre unique via l'API
     */
    public function READ__it_should_get_single_chapter()
    {
        // 1. ARRANGE : Créer un chapitre d'abord (il faut quelque chose à récupérer)
        $chapterToGetTitle = 'Mon chapitre à récupérer';

        $chapterToGetID = $this->createTestChapter([
            'title' => $chapterToGetTitle,
            'number' => 5,
            'order_hint' => 5
        ]);

        // 2. ACT : Récupérer le chapitre via GET
        $response = $this->client->get('/chapters/' . $chapterToGetID);

        // 3. ASSERT : Vérifier la réponse
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);

        // Vérifier les données du chapitre
        $this->assertEquals($chapterToGetTitle, $data['data']['title']);
        $this->assertEquals(5, $data['data']['number']);
        $this->assertEquals($this->persistentData['workId'], $data['data']['work_id']);

        // Vérifier que le titre de l'épisode est inclus (grâce au LEFT JOIN)
        $this->assertEquals(null, $data['data']['episode_title']);
    }

    /**
     * @test
     * Teste le retour d'erreur 404 d'un GET sur id inconnu
     */
    public function READ__it_should_return_404_when_chapter_not_found()
    {
        // ACT : Essayer de récupérer un UUID qui n'existe pas
        $response = $this->client->get('/chapters/00000000-0000-0000-0000-000000000000');

        // ASSERT
        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertStringContainsString('not found', strtolower($data['message']));
    }

    /**
     * @test
     * Teste le listing de tous les chapitres d'un épisode, dans l'ordre
     */
    public function READ__it_should_list_all_chapters_ordered_by_sort_order()
    {
        // ARRANGE : Créer 3 chapitres avec différents sort_order
        $WorkId = $this->createTestWork([
            'title' => 'Testing Book'
        ]);

        $this->createTestChapter([
            'title' => 'Chapitre second - Le début',
            'number' => 100,
            'work_id' => $WorkId
        ]);

        $this->createTestChapter([
            'title' => 'Chapitre ultime - La fin',
            'number' => 300,
            'order_hint' => 3,
            'work_id' => $WorkId
        ]);

        $this->createTestChapter([
            'title' => 'Chapitre pénultième - Le milieu',
            'number' => 200,
            'order_hint' => 2,
            'work_id' => $WorkId
        ]);

        // ACT
        $response = $this->client->get('/chapters');

        // ASSERT
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertCount(4, $data['data']);

        // Vérifier l'ordre
        $this->assertEquals('Chapitre premier', $data['data'][0]['chapter_title']);
        $this->assertEquals('Chapitre second - Le début', $data['data'][1]['chapter_title']);
        $this->assertEquals('Chapitre pénultième - Le milieu', $data['data'][2]['chapter_title']);
        $this->assertEquals('Chapitre ultime - La fin', $data['data'][3]['chapter_title']);
    }

    // CRUD TESTS :: UPDATE

    /**
     * @test
     * Teste la mise à jour du titre d'un chapitre
     */
    public function UPDATE__it_should_update_chapter_title()
    {
        // ARRANGE
        $chapterId = $this->createTestChapter(['title' => 'Titre original']);

        // ACT
        $response = $this->client->put('/chapters/' . $chapterId, [
            'json' => ['title' => 'Titre modifié']
        ]);

        // ASSERT
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifier en BDD
        $stmt = $this->pdo->prepare('SELECT title FROM chapters WHERE id = :id');
        $stmt->execute(['id' => $chapterId]);
        $chapter = $stmt->fetch();

        $this->assertEquals('Titre modifié', $chapter['title']);
    }

    // /**
    //  * @test
    //  * Teste la mise à jour de plusieurs propriétés d'une scène
    //  */
    // public function it_should_update_multiple_scene_fields()
    // {
    //     // ARRANGE
    //     $sceneId = $this->createTestChapter([
    //         'title' => 'Original',
    //         'sort_order' => 200
    //     ]);

    //     // ACT
    //     $response = $this->client->put('/scenes/' . $sceneId, [
    //         'json' => [
    //             'title' => 'Nouveau titre',
    //             'sort_order' => 150,
    //             'emoji' => '🔥'
    //         ]
    //     ]);

    //     // ASSERT
    //     $this->assertEquals(200, $response->getStatusCode());

    //     $stmt = $this->pdo->prepare('SELECT * FROM scenes WHERE id = :id');
    //     $stmt->execute(['id' => $sceneId]);
    //     $chapter = $stmt->fetch();

    //     $this->assertEquals('Nouveau titre', $chapter['title']);
    //     $this->assertEquals(150, $chapter['sort_order']);
    //     $this->assertEquals('🔥', $chapter['emoji']);
    // }

    /**
     * @test
     * Teste le retour d'erreur 404 d'un UPDATE sur ID inconnu
     */
    public function UPDATE__it_should_return_404_when_updating_non_existent_scene()
    {
        // ACT
        $response = $this->client->put('/chapters/00000000-0000-0000-0000-000000000000', [
            'json' => ['title' => 'Nouveau titre']
        ]);

        // ASSERT
        $this->assertEquals(404, $response->getStatusCode());
    }

    // CRUD TESTS :: DELETE

    /**
     * @test
     * Teste la suppression d'un chapitre
     */
    public function DELETE__it_should_delete_a_chapter()
    {
        // ARRANGE : créer un chapitre à supprimer, vérifier qu'il existe
        $chapterToDeleteID = $this->createTestChapter(['title' => 'Chapitre à supprimer']);
        $getResponse = $this->client->get('/chapters/' . $chapterToDeleteID);

        // ACT : supprimer un chapitre via son ID
        $delResponse = $this->client->delete('/chapters/' . $chapterToDeleteID);

        // ASSERT
        $this->assertEquals(200, $delResponse->getStatusCode());

        $getResponse = $this->client->get('/chapters/' . $chapterToDeleteID);
        $this->assertEquals(404, $getResponse->getStatusCode());

        $data = json_decode($getResponse->getBody(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertStringContainsString('not found', strtolower($data['message']));
    }

    /**
     * @test
     * Teste le retour d'erreur à la suppression d'un chapitre inexistant
     */
    public function DELETE__it_should_return_404_when_deleting_non_existent_chapter()
    {
        // ACT : supprimer un chapitre via son ID
        $delResponse = $this->client->delete('/chapters/00000000-0000-0000-0000-000000000000');

        // ASSERT
        $this->assertEquals(404, $delResponse->getStatusCode());
    }
}


