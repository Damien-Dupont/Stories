<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class WorkApiTest extends TestCase
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

        $this->seedTEstData();
    }

    private function cleanDatabase(): void
    {

    }

}