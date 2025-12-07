<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class EpisodeApiTest extends TestCase
{
    private Client $client;
    private PDO $pdo;
    private array $persistentData = [];

    protected function setUp(): void
    {
        $this->client = new CLient([
            "base_uri" => "http://nginx",
            "http_errors" => "false",
        ]);

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
        $this->cleanDatabase();

        $this->seedTestData();
    }

    private function cleanDatabase(): void
    {

    }

    // TODO: tests à ajouter
// it_should_create_an_episode
// it_should_list_episodes_by_work
// it_should_update_episode
// it_should_delete_episode

}