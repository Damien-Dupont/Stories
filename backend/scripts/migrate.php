<?php

/**
 * Script de gestion des migrations de BDD
 * Usage: php backend/scripts/migrate.php [up|down|status]
 */

require_once __DIR__ . '/../config/database.php';

class MigrationManager
{
    private PDO $pdo;
    private string $migrationsDir;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->migrationsDir = '/database/migrations/';
        $this->ensureMigrationTableExists();
    }

    /**
     * Crée la table schema_migrations si elle n'existe pas
     */
    private function ensureMigrationTableExists(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id SERIAL PRIMARY KEY,
                version VARCHAR(50) UNIQUE NOT NULL,
                description TEXT NOT NULL,
                script_name VARCHAR(255) NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                checksum VARCHAR(64) NULL
            );

            CREATE INDEX IF NOT EXISTS idx_migrations_version
            ON schema_migrations(version);
        ");
    }

    /**
     * Liste les migrations appliquées
     */
    public function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query('SELECT version FROM schema_migrations ORDER BY version');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Liste les fichiers de migration disponibles
     */
    public function getAvailableMigrations(): array
    {
        $files = glob($this->migrationsDir . '*.sql');
        $migrations = [];

        foreach ($files as $file) {
            $filename = basename($file);
            // Extraire version (ex: 20251117_1145 depuis 20251117_1145_description.sql)
            if (preg_match('/^(\d{8}_\d{4})_(.+)\.sql$/', $filename, $matches)) {
                $migrations[] = [
                    'version' => $matches[1],
                    'description' => str_replace('_', ' ', $matches[2]),
                    'filename' => $filename,
                    'path' => $file
                ];
            }
        }

        usort($migrations, fn($a, $b) => strcmp($a['version'], $b['version']));
        return $migrations;
    }

    /**
     * Applique les migrations en attente
     */
    public function migrate(): void
    {
        $applied = $this->getAppliedMigrations();
        $available = $this->getAvailableMigrations();

        $pending = array_filter($available, fn($m) => !in_array($m['version'], $applied));

        if (empty($pending)) {
            echo "✓ Aucune migration à appliquer.\n";
            return;
        }

        echo "Migrations en attente: " . count($pending) . "\n\n";

        foreach ($pending as $migration) {
            echo "Applying {$migration['version']}: {$migration['description']}... ";

            try {
                $sql = file_get_contents($migration['path']);
                $this->pdo->exec($sql);
                echo "✓\n";
            } catch (PDOException $e) {
                echo "✗\n";
                echo "Erreur: " . $e->getMessage() . "\n";
                exit(1);
            }
        }

        echo "\n✓ Toutes les migrations ont été appliquées.\n";
    }

    /**
     * Affiche le statut des migrations
     */
    public function status(): void
    {
        $applied = $this->getAppliedMigrations();
        $available = $this->getAvailableMigrations();

        echo "=== État des migrations ===\n\n";
        echo "Appliquées: " . count($applied) . "\n";
        echo "Disponibles: " . count($available) . "\n\n";

        foreach ($available as $migration) {
            $status = in_array($migration['version'], $applied) ? '✓' : '⏳';
            echo "{$status} {$migration['version']} - {$migration['description']}\n";
        }
    }
}

// Exécution du script
$command = $argv[1] ?? 'status';

$manager = new MigrationManager($pdo);

switch ($command) {
    case 'up':
    case 'migrate':
        $manager->migrate();
        break;
    case 'status':
        $manager->status();
        break;
    default:
        echo "Usage: php migrate.php [up|status]\n";
        exit(1);
}