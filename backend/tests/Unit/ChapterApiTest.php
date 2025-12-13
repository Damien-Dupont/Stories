<?php

require_once __DIR__ . '/../ApiTestCase.php';

class ChapterApiTest extends ApiTestCase
{

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
    //  * Teste la génération auto d'un titre lors de la création d'un chapitre sans titre
    //  */
    // public function it_should_auto_generate_title_when_missing()


    /**
     * @test
     * Teste le rejet de création d'un chapitre sans work_id
     */
    public function CREATE__it_should_reject_chapter_without_work_id()
    {

        // ARRANGE
        $chapterWithoutWorkId = [
            'number' => 2,
            'order_hint' => 2,
            'title' => 'Titre sans oeuvre'
            // Manque : work_id
        ];

        // ACT
        $response = $this->client->post('/chapters', ['json' => $chapterWithoutWorkId]);

        // ASSERT
        $this->assertEquals(400, $response->getStatusCode());

    }

    /**
     * @test
     * Teste le la valeur par défaut de order_hint à la création d'un chapitre
     */
    public function CREATE__it_should_set_order_hint_to_zero_by_default()
    {
        // ARRANGE
        $chapterNoOrderHint = [
            'number' => 2,
            'work_id' => $this->persistentData['workId'],
            'title' => 'Titre sans order_hint'
            // Manque : work_id
        ];

        // ACT
        $response = $this->client->post('/chapters', ['json' => $chapterNoOrderHint]);

        // ASSERT
        $data = json_decode($response->getBody(), true);
        $chapterId = $data['data']['id'];

        $stmt = $this->pdo->prepare('SELECT order_hint FROM chapters WHERE id = :id');
        $stmt->execute(['id' => $chapterId]);
        $chapter = $stmt->fetch();
        $this->assertEquals(0, $chapter['order_hint']);

    }

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
    public function READ__it_should_list_all_chapters_ordered_by_global_order()
    {
        // ARRANGE : Créer 3 chapitres avec différents global_order
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
        $this->assertEquals('Test Chapter', $data['data'][0]['chapter_title']);
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
    //  * Teste la mise à jour de plusieurs propriétés d'un chapitre
    //  */
    // public function it_should_update_multiple_chapter_fields()
    // {
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

    // TODO: tests fonctionnels à ajouter
    // it_should_list_chapters_by_work
    // GET /works/{id}/chapters → Seulement les chapitres de cette œuvre
    // Nécessite la route byWork()

    // it_should_update_chapter_episode_id
    // Rattacher un chapitre à un épisode (mettre à jour episode_id)

    // it_should_cascade_delete_scenes_when_deleting_chapter
    // Supprimer un chapitre → Vérifie que ses scènes sont aussi supprimées
    // (Déjà géré par ON DELETE CASCADE en SQL, mais bon à tester)

}


