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

        // Vérifier que les scènes existent via GET
        $check1 = $this->client->get('/scenes/' . $this->persistentData['sceneOneId']);

        $check2 = $this->client->get('/scenes/' . $this->persistentData['sceneTwoId']);

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
        $this->assertArrayHasKey('id', $data['data']);  // ✅ Vérifie que la clé 'id' existe
        $this->assertIsString($data['data']['id']);     // ✅ Vérifie que c'est une chaîne (UUID)

        $this->persistentData['transitionId'] = $data['data']['id'];

        $stmt = $this->pdo->prepare('SELECT * FROM scene_transitions WHERE id = :id');
        $stmt->execute(['id' => $this->persistentData['transitionId']]);
        $transition = $stmt->fetch();

        $this->assertEquals($this->persistentData['sceneOneId'], $transition['scene_before_id']);
        $this->assertEquals($this->persistentData['sceneTwoId'], $transition['scene_after_id']);
        $this->assertEquals('Par là', $transition['transition_label']);
        $this->assertEquals(1, $transition['transition_order']);
    }

    /**
     * @test Summary of READ__it_should_get_all_transitions
     * @return void
     */
    public function READ__it_should_get_all_transitions(): void
    {
        // ARRANGE - Créer plusieurs transitions
        $scene4Id = $this->createTestScene(['title' => 'Scène 4']);

        $this->client->post('/transitions', [
            'json' => [
                'scene_before_id' => $this->persistentData['sceneOneId'],
                'scene_after_id' => $this->persistentData['sceneTwoId'],
                'transition_label' => 'Continuer',
                'transition_order' => 1
            ]
        ]);

        $this->client->post('/transitions', [
            'json' => [
                'scene_before_id' => $this->persistentData['sceneTwoId'],
                'scene_after_id' => $this->persistentData['sceneThreeId'],
                'transition_label' => 'Suivant',
                'transition_order' => 1
            ]
        ]);

        $this->client->post('/transitions', [
            'json' => [
                'scene_before_id' => $this->persistentData['sceneThreeId'],
                'scene_after_id' => $scene4Id,
                'transition_label' => 'Fin',
                'transition_order' => 1
            ]
        ]);

        // ACT - Récupérer toutes les transitions
        $response = $this->client->get('/transitions');

        // ASSERT - Structure
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertIsArray($data['data']);
        $this->assertCount(3, $data['data'], 'Should return 3 transitions');

        // ASSERT - Ordre par scene_before_id puis transition_order
        $firstTransition = $data['data'][0];
        $this->assertEquals($this->persistentData['sceneOneId'], $firstTransition['from_scene']);
        $this->assertEquals($this->persistentData['sceneTwoId'], $firstTransition['to_scene']);
        $this->assertEquals('Continuer', $firstTransition['transition_label']);
    }


    /**
     * @test Summary of READ__it_should_get_transitions_to_next_scenes
     * @return void
     */
    public function READ__it_should_get_transitions_to_next_scenes(): void
    {
        // ARRANGE : create transitions
        $transition1to2 = $this->client->post('/transitions', [
            'json' => [
                'scene_before_id' => $this->persistentData['sceneOneId'],
                'scene_after_id' => $this->persistentData['sceneTwoId'],
                'transition_label' => 'Aller à droite',
                'transition_order' => 1
            ]
        ]);

        $transition1to3 = $this->client->post('/transitions', [
            'json' => [
                'scene_before_id' => $this->persistentData['sceneOneId'],
                'scene_after_id' => $this->persistentData['sceneThreeId'],
                'transition_label' => 'Aller à gauche',
                'transition_order' => 2
            ]
        ]);
        // Verify transitions have been created
        $this->assertEquals(201, $transition1to2->getStatusCode());
        $this->assertEquals(201, $transition1to3->getStatusCode());

        // ACT - retrieve 'next' transitions of SceneOne
        $response = $this->client->get('/scenes/' . $this->persistentData['sceneOneId'] . '/transitions/next');

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertIsArray($data['data']);
        $this->assertCount(2, $data['data'], 'Should return 2 next transitions');

        // ASSERT - Vérifier le contenu des transitions (ordre correct)
        $firstTransition = $data['data'][0];
        $this->assertEquals('Aller à droite', $firstTransition['transition_label']);
        $this->assertEquals(1, $firstTransition['transition_order']);
        $this->assertEquals($this->persistentData['sceneTwoId'], $firstTransition['scene_id']);
        $this->assertEquals('Par là', $firstTransition['scene_title']);

        $secondTransition = $data['data'][1];
        $this->assertEquals('Aller à gauche', $secondTransition['transition_label']);
        $this->assertEquals(2, $secondTransition['transition_order']);
        $this->assertEquals($this->persistentData['sceneThreeId'], $secondTransition['scene_id']);
        $this->assertEquals('Par ici', $secondTransition['scene_title']);
    }

    /**
     * @test Summary of READ__it_should_get_transitions_to_previous_scenes
     * @return void
     */
    public function READ__it_should_get_transitions_to_previous_scenes(): void
    {
        // ARRANGE : create transitions
        $transition1to2 = $this->client->post('/transitions', [
            'json' => [
                'scene_before_id' => $this->persistentData['sceneOneId'],
                'scene_after_id' => $this->persistentData['sceneTwoId']
            ]
        ]);

        $transition3to2 = $this->client->post('/transitions', [
            'json' => [
                'scene_before_id' => $this->persistentData['sceneThreeId'],
                'scene_after_id' => $this->persistentData['sceneTwoId']
            ]
        ]);
        // Verify transitions have been created
        $this->assertEquals(201, $transition1to2->getStatusCode());
        $this->assertEquals(201, $transition3to2->getStatusCode());

        // ACT - retrieve 'previous' transitions of SceneOne
        $response = $this->client->get('/scenes/' . $this->persistentData['sceneTwoId'] . '/transitions/prev');

        // ASSERT
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertCount(2, $data['data'], 'Should return 2 previous transitions');

        // Vérifier que les scènes précédentes sont bien 1 et 3
        $sceneIds = array_column($data['data'], 'scene_id');
        $this->assertContains($this->persistentData['sceneOneId'], $sceneIds);
        $this->assertContains($this->persistentData['sceneThreeId'], $sceneIds);
    }

    /**
     * @test Summary of READ__it_should_return_empty_array_when_no_next_transitions
     * @return void
     */
    public function READ__it_should_return_empty_array_when_no_next_transitions(): void
    {
        // ARRANGE - Créer une scène isolée sans transitions
        $isolatedSceneId = $this->createTestScene(['title' => 'Scène Isolée (Fin)']);

        // ACT - Scène sans transitions sortantes
        $response = $this->client->get('/scenes/' . $isolatedSceneId . '/transitions/next');

        // ASSERT - 200 OK avec tableau vide (pas 404)
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody(), true);
        $this->assertEquals('ok', $data['status']);
        $this->assertIsArray($data['data']);
        $this->assertCount(0, $data['data'], 'Should return empty array, not 404');
    }
}



// TODO: GET_CREATE__it_should_return_404_if_one_scene_does_not_exist
// TODO: READ__it_should_return_404_if_one_transition_does_not_exist
// GET /transitions
// GET /transitions/{id}
// GET /scenes/{id}/transitions
