<?php

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
        if ($response->getStatusCode() !== 201) {
            echo "\n=== DEBUG ERROR 500 ===\n";
            echo "Status Code: " . $response->getStatusCode() . "\n";
            echo "Response Body: " . $response->getBody()->getContents() . "\n";
            echo "========================\n";
        }
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertCount(4, $data['data']);
        $this->assertEquals('First Testing Book', $data['data'][2]['title']); // index sortec by created_at DESC
        $this->assertEquals('Second Testing Book', $data['data'][1]['title']);
        $this->assertEquals('Third Testing Book', $data['data'][0]['title']);
    }

    // TODO: tests à ajouter

    // READ__it_should_get_a_work_by_id

    // UPDATE__it_should_update_work_title
// DELETE__it_should_delete_work
// CREATE__it_should_set_published_to_false_by_default
// CREATE__it_should_reject_work_without_title

}