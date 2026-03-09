<?php

require_once __DIR__ . '/../ApiTestCase.php';

class SceneApiTest extends ApiTestCase
{
    protected function seedTestData(): void
    {
        parent::seedTestData();
        $this->persistentData['chapterId'] = $this->createTestChapter();
    }

    // TESTS :: CORS
    /**
     * @test
     * Teste la requête OPTIONS sur /scenes
     */
    public function OPT__it_should_respond_with_CORS_headers_on_preflight(): void
    {
        $response = $this->client->options('/scenes');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertTrue($response->hasHeader('Access-Control-Allow-Methods'));
    }

    /**
     * @test
     * Teste que les headers CORS sont présents dans la réponse d'une requête sur /scenes
     */
    public function GET__it_should_include_CORS_headers_on_normal_request()
    {
        // ACT
        $response = $this->client->get('/scenes');

        // ASSERT
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
    }

    // CRUD TESTS :: CREATION

    /**
     * @test
     * Teste la création d'une scène standard via l'API
     */
    public function CREATE__it_should_create_a_standard_scene()
    {
        // Données à envoyer
        $sceneToCreate = [
            'chapter_id' => $this->persistentData['chapterId'],
            'title' => 'Ma première scène',
            'content_markdown' => '# Titre\n\nContenu de la scène',
            'global_order' => 200
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
     * Teste la création d'une scène spéciale via l'API
     */
    public function CREATE__it_should_create_a_special_scene_prologue()
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
            'global_order' => 100
        ];

        // Faire la requête POST
        $response = $this->client->post('/scenes', [
            'json' => $prologueToCreate
        ]);

        // Vérifications
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
     * Teste la génération auto d'un titre lors de la création d'une scène sans titre
     */
    public function CREATE__it_should_auto_generate_title_when_missing()
    {
        // ARRANGE
        $sceneWithoutTitle = [
            'chapter_id' => $this->persistentData['chapterId'],
            'content_markdown' => '# Contenu'
            // Pas de title
        ];

        // ACT
        $response = $this->client->post('/scenes', ['json' => $sceneWithoutTitle]);

        // ASSERT
        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $sceneId = $data['data']['id'];

        // Vérifier en BDD que le titre a été auto-généré
        $stmt = $this->pdo->prepare('SELECT title FROM scenes WHERE id = :id');
        $stmt->execute(['id' => $sceneId]);
        $scene = $stmt->fetch();

        $this->assertEquals('Sans titre', $scene['title']);
    }

    /**
     * @test
     * Teste le rejet de création d'une scène spéciale liée à un chapitre
     */
    public function CREATE__it_should_reject_special_scene_with_chapter_id()
    {
        // ARRANGE
        $invalidScene = [
            'scene_type' => 'special',
            'chapter_id' => $this->persistentData['chapterId'], // ← Interdit !
            'title' => 'Prologue invalide',
            'content_markdown' => '# Test'
        ];

        // ACT
        $response = $this->client->post('/scenes', ['json' => $invalidScene]);

        // ASSERT
        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertStringContainsString('special scenes cannot have a chapter_id', strtolower($data['message']));
    }

    /**
     * @test
     * Teste l'utilisation du custom_type_label comme titre à la création d'une scène spéciale sans titre
     */
    public function CREATE__it_should_use_custom_type_label_as_title_for_special_scene()
    {
        // ARRANGE
        $prologueWithoutTitle = [
            'scene_type' => 'special',
            'custom_type_label' => 'Prologue',
            'content_markdown' => '# Contenu du prologue',
            'global_order' => 100
            // Pas de title
        ];

        // ACT
        $response = $this->client->post('/scenes', ['json' => $prologueWithoutTitle]);

        // ASSERT
        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $sceneId = $data['data']['id'];

        // Vérifier que le titre est le custom_type_label
        $stmt = $this->pdo->prepare('SELECT title FROM scenes WHERE id = :id');
        $stmt->execute(['id' => $sceneId]);
        $scene = $stmt->fetch();

        $this->assertEquals('Prologue', $scene['title']);
    }

    /**
     * @test
     * Teste le rejet de création d'une scène sans contenu
     */
    public function CREATE__it_should_reject_scene_without_content_markdown()
    {
        // ARRANGE
        $sceneWithoutContent = [
            'chapter_id' => $this->persistentData['chapterId'],
            'title' => 'Titre sans contenu'
            // Manque : content_markdown
        ];

        // ACT
        $response = $this->client->post('/scenes', ['json' => $sceneWithoutContent]);

        // ASSERT
        $this->assertEquals(400, $response->getStatusCode());
    }

    // CRUD TESTS :: READ

    /**
     * @test
     * Teste la récupération d'une scène unique via l'API
     */
    public function READ__it_should_get_single_scene()
    {
        // 1. ARRANGE : Créer une scène d'abord (il faut quelque chose à récupérer)
        $sceneToGetTitle = 'Ma scène à récupérer';
        $sceneToGetContent = '# Contenu';
        $sceneToCreate = [
            'chapter_id' => $this->persistentData['chapterId'],
            'title' => $sceneToGetTitle,
            'content_markdown' => $sceneToGetContent,
            'global_order' => 200
        ];
        $sceneToGetID = $this->createTestScene($sceneToCreate);

        // 2. ACT : Récupérer la scène via GET
        $response = $this->client->get('/scenes/' . $sceneToGetID);

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
    public function READ__it_should_return_404_when_scene_not_found()
    {
        // ACT : Essayer de récupérer un UUID qui n'existe pas
        $response = $this->client->get('/scenes/00000000-0000-0000-0000-000000000000');

        // ASSERT
        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertStringContainsString('not found', strtolower($data['message']));
    }

    /**
     * @test
     * Teste le listing de toutes les scènes d'un chapitre, dans l'ordre
     */
    public function READ__it_should_list_all_scenes_ordered_by_global_order()
    {
        // ARRANGE : Créer 3 scènes avec différents global_order
        $this->createTestScene([
            'title' => 'Chapitre 1 - Scène 1',
            'global_order' => 200
        ]);

        $prologueId = $this->client->post('/scenes', [
            'json' => [
                'scene_type' => 'special',
                'custom_type_label' => 'Prologue',
                'title' => 'Prologue',
                'content_markdown' => '# Prologue',
                'global_order' => 100
            ]
        ]);

        $this->createTestScene([
            'title' => 'Chapitre 1 - Scène 2',
            'global_order' => 300
        ]);

        // ACT
        $response = $this->client->get('/scenes');

        // ASSERT
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertCount(3, $data['data']);

        // Vérifier l'ordre
        $this->assertEquals('Prologue', $data['data'][0]['scene_title']);
        $this->assertEquals('Chapitre 1 - Scène 1', $data['data'][1]['scene_title']);
        $this->assertEquals('Chapitre 1 - Scène 2', $data['data'][2]['scene_title']);
    }

    // CRUD TESTS :: UPDATE

    /**
     * @test
     * Teste la mise à jour du titre d'une scène
     */
    public function UPDATE__it_should_update_scene_title()
    {
        // ARRANGE
        $sceneId = $this->createTestScene(['title' => 'Titre original']);

        // ACT
        $response = $this->client->put('/scenes/' . $sceneId, [
            'json' => ['title' => 'Titre modifié']
        ]);

        // ASSERT
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifier en BDD
        $stmt = $this->pdo->prepare('SELECT title FROM scenes WHERE id = :id');
        $stmt->execute(['id' => $sceneId]);
        $scene = $stmt->fetch();

        $this->assertEquals('Titre modifié', $scene['title']);
    }

    /**
     * @test
     * Teste la mise à jour de plusieurs propriétés d'une scène
     */
    public function UPDATE__it_should_update_multiple_scene_fields()
    {
        // ARRANGE
        $sceneId = $this->createTestScene([
            'title' => 'Original',
            'global_order' => 200
        ]);

        // ACT
        $response = $this->client->put('/scenes/' . $sceneId, [
            'json' => [
                'title' => 'Nouveau titre',
                'global_order' => 150,
                'emoji' => '🔥'
            ]
        ]);

        // ASSERT
        $this->assertEquals(200, $response->getStatusCode());

        $stmt = $this->pdo->prepare('SELECT * FROM scenes WHERE id = :id');
        $stmt->execute(['id' => $sceneId]);
        $scene = $stmt->fetch();

        $this->assertEquals('Nouveau titre', $scene['title']);
        $this->assertEquals(150, $scene['global_order']);
        $this->assertEquals('🔥', $scene['emoji']);
    }

    /**
     * @test
     * Teste le retour d'erreur 404 d'un UPDATE sur ID inconnu
     */
    public function UPDATE__it_should_return_404_when_updating_non_existent_scene()
    {
        // ACT
        $response = $this->client->put('/scenes/00000000-0000-0000-0000-000000000000', [
            'json' => ['title' => 'Nouveau titre']
        ]);

        // ASSERT
        $this->assertEquals(404, $response->getStatusCode());
    }

    // CRUD TESTS :: DELETE

    /**
     * @test
     * Teste la suppression d'une scène
     */
    public function DELETE__it_should_delete_a_scene()
    {
        // ARRANGE : créer une scène à supprimer, vérifier qu'elle existe
        $sceneToDeleteID = $this->createTestScene(['title' => 'Scène à supprimer']);
        $getResponse = $this->client->get('/scenes/' . $sceneToDeleteID);

        // ACT : supprimer une scène via son ID
        $delResponse = $this->client->delete('/scenes/' . $sceneToDeleteID);

        // ASSERT
        $this->assertEquals(200, $delResponse->getStatusCode());

        $getResponse = $this->client->get('/scenes/' . $sceneToDeleteID);
        $this->assertEquals(404, $getResponse->getStatusCode());

        $data = json_decode($getResponse->getBody(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertStringContainsString('not found', strtolower($data['message']));
    }

    /**
     * @test
     * Teste le retour d'erreur à la suppression d'une scène inexistante
     */
    public function DELETE__it_should_return_404_when_deleting_non_existent_scene()
    {
        // ACT : supprimer une scène via son ID
        $delResponse = $this->client->delete('/scenes/00000000-0000-0000-0000-000000000000');

        // ASSERT
        $this->assertEquals(404, $delResponse->getStatusCode());
    }
}


