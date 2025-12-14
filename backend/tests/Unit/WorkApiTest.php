<?php

use function PHPUnit\Framework\assertEquals;

require_once __DIR__ . '/../ApiTestCase.php';

class WorkApiTest extends ApiTestCase
{
    // CRUD TESTS :: CREATION

    /**
     * @test
     * Teste la création d'un chapitre
     */
    public function CREATE__it_should_create_a_work(): void
    {
        // ARRANGE
        $workToCreate = ['title' => 'Test Work', 'description' => 'Test Work Description'];

        // ACT
        $response = $this->client->post(
            '/works',
            ['json' => $workToCreate]
        );

        // ASSERT
        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertArrayHasKey('id', $data['data']);

        // Stocker l'ID pour les tests suivants si besoin
        $this->persistentData['workId'] = $data['data']['id'];

        // Vérifier en BDD que l'oeuvre est bien créée
        $stmt = $this->pdo->prepare('SELECT * FROM works WHERE id = :id');
        $stmt->execute(['id' => $this->persistentData['workId']]);
        $scene = $stmt->fetch();

        $this->assertEquals('Test Work', $scene['title']);

    }

    // CRUD TESTS :: READ

    /**
     * @test
     * Teste la récupération de toutes les oeuvres
     */
    public function READ__it_should_list_all_works(): void
    {
        // ARRANGE : create 3 different works
        $WorkOneId = $this->createTestWork(
            ['title' => 'First Testing Book']
        );
        $WorkTwoId = $this->createTestWork(
            ['title' => 'Second Testing Book']
        );
        $WorkThreeId = $this->createTestWork(
            ['title' => 'Third Testing Book']
        );

        // ACT
        $response = $this->client->get('/works');

        // ASSERT

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertCount(4, $data['data']);
        $this->assertEquals('First Testing Book', $data['data'][2]['title']); // index sortec by created_at DESC
        $this->assertEquals('Second Testing Book', $data['data'][1]['title']);
        $this->assertEquals('Third Testing Book', $data['data'][0]['title']);
    }

    /**
     * @test
     * Summary of READ__it_should_get_a_single_work
     * @return void
     */
    public function READ__it_should_get_a_single_work(): void
    {
        // ARRANGE
        $WorkId = $this->createTestWork(
            [
                'title' => 'Testing Book',
                'episode_label' => 'Livre',
                'chapter_label' => 'Chapitre',
                'published_date' => null
            ]
        );

        // ACT
        $response = $this->client->get('/works/' . $WorkId);

        // ASSERT

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);

        $this->assertEquals('Testing Book', $data['data']['title']);
        $this->assertEquals('Livre', $data['data']['episode_label']);
        $this->assertEquals('Chapitre', $data['data']['chapter_label']);
        $this->assertEquals(null, $data['data']['published_date']);
    }

    // CRUD TESTS :: UPDATE

    /**
     * @test
     * Summary of UPDATE__it_should_update_work_title
     * @return void
     */
    public function UPDATE__it_should_update_work_title(): void
    {
        // ARRANGE
        $workId = $this->createTestWork(['title' => 'To Be Or Not To Be']);

        sleep(1);
        $beforeUpdate = new DateTime();
        // ACT
        $response = $this->client->put('/works/' . $workId, ['json' => ['title' => 'To Be Updated']]);

        // ASSERT
        $stmt = $this->pdo->prepare('SELECT title, updated_at FROM works WHERE id = :id');
        $stmt->execute(['id' => $workId]);
        $work = $stmt->fetch();

        $afterUpdate = new DateTime();
        $updatedAt = new DateTime($work['updated_at']);

        $this->assertEquals('To Be Updated', $work['title']);

        $this->assertGreaterThanOrEqual(
            $beforeUpdate->getTimestamp() - 1,
            $updatedAt->getTimestamp(),
            'updated_at should be after or equal to the update time'
        );
        $this->assertLessThanOrEqual(
            $afterUpdate->getTimestamp() + 1,
            $updatedAt->getTimestamp(),
            'updated_at should be before or equal to the current time'
        );
    }

    /**
     * @test
     * Summary of DELETE__it_should_delete_a_work
     * @return void
     */
    public function DELETE__it_should_delete_a_work(): void
    {
        // ARRANGE
        $workToDelete = $this->createTestWork(['title' => 'Work to be deleted']);
        $getResponse = $this->client->get('/works/' . $workToDelete);

        // ACT
        $delResponse = $this->client->delete('/works/' . $workToDelete);

        // ASSERT
        $this->assertEquals(200, $getResponse->getStatusCode());

        $getResponse = $this->client->get('/works/' . $workToDelete);
        $this->assertEquals(404, $getResponse->getStatusCode());

        $data = json_decode($getResponse->getBody()->getContents(), true);
        $this->assertEquals('error', $data['status']);
        $this->assertStringContainsString('not found', strtolower($data['message']));
    }
    /**
     * @test
     * Teste le retour d'erreur à la suppression d'une oeuvre inexistante
     */
    public function DELETE__it_should_return_404_when_deleting_non_existent_work()
    {
        // ACT : supprimer une scène via son ID
        $delResponse = $this->client->delete('/works/00000000-0000-0000-0000-000000000000');

        // ASSERT
        $this->assertEquals(404, $delResponse->getStatusCode());
    }

    // TODO: tests à ajouter





    // CREATE__it_should_set_published_to_false_by_default
// CREATE__it_should_reject_work_without_title

}