<?php

require_once __DIR__ . '/../ApiTestCase.php';

class TransitionApiTest extends ApiTestCase
{
    protected function seedTestData(): void
    {
        parent::seedTestData();
        $this->persistentData['sceneOneId'] = $this->createTestScene(['title' => 'Choix']);
        $this->persistentData['sceneTwoId'] = $this->createTestScene(['title' => 'Par là']);
        $this->persistentData['sceneThreeId'] = $this->createTestScene(['title' => 'Par ici']);
    }

    // CRUD TESTS :: CREATION

    /**
     * @test Summary of CREATE__it_should_create_a_transition_between_two_scenes
     * @return void
     */
    public function CREATE__it_should_create_a_transition_between_two_scenes(): void
    {
        //ARRANGE
        $transitionToCreate = [
            'scene_before_id' => $this->persistentData['sceneOneId'],
            'scene_after_id' => $this->persistentData['sceneTwoId']
        ];

        //ACT
        $response = $this->client->post('/transitions', ['json' => $transitionToCreate]);

        //ASSERT
        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertEquals('id', $data['data']);

        $this->persistentData['transitionId'] = $data['data']['id'];

        $stmt = $this->pdo->prepare('SELECT * FROM scene_transitions WHERE id = :id');
        $stmt->execute(['id' => $this->persistentData['transitionId']]);
        $transition = $stmt->fetch();

        $this->assertEquals($this->persistentData['sceneOneId'], $transition['scene_before_id']);
        $this->assertEquals($this->persistentData['sceneTwoId'], $transition['scene_after_id']);
        $this->assertNull($transition['transition_label']);
        $this->assertEquals(1, $transition['transition_order']);
    }

}

