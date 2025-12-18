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
        if (!is_dir($this->migrationsDir)) {
            echo "⚠️  Le dossier de migrations n'existe pas : {$this->migrationsDir}\n";
            return [];
        }

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

        if (empty($available)) {
            echo "⚠️  Aucun fichier de migration trouvé dans {$this->migrationsDir}\n";
            return;
        }

        $pending = array_filter($available, fn($m) => !in_array($m['version'], $applied));

        if (empty($pending)) {
            echo "✓ Aucune migration à appliquer.\n";
            return;
        }

        echo "Migrations en attente: " . count($pending) . "\n\n";

        foreach ($pending as $migration) {
            echo "Applying {$migration['version']}: {$migration['description']}... ";

            try {
                // 1. Lire le fichier SQL
                $sql = file_get_contents($migration['path']);

                // 2. Nettoyer les anciennes commandes INSERT schema_migrations (si présentes)
                $sql = $this->cleanOldMigrationInserts($sql);

                // 3. Calculer le checksum
                $checksum = md5_file($migration['path']);

                try {
                    // Exécuter le SQL de la migration
                    $this->pdo->beginTransaction();
                    $this->pdo->exec($sql);

                    // Enregistrer dans schema_migrations (géré automatiquement)
                    $stmt = $this->pdo->prepare("
                        INSERT INTO schema_migrations (version, description, script_name, checksum)
                        VALUES (:version, :description, :script_name, :checksum)
                    ");

                    $stmt->execute([
                        'version' => $migration['version'],
                        'description' => $migration['description'],
                        'script_name' => $migration['filename'],
                        'checksum' => $checksum
                    ]);

                    $this->pdo->commit();
                    echo "✓\n";
                } catch (PDOException $e) {
                    $this->pdo->rollBack();
                    throw $e;
                }

            } catch (PDOException $e) {
                echo "✗\n";
                echo "Erreur: " . $e->getMessage() . "\n";
                echo "Migration: {$migration['filename']}\n";
                exit(1);
            }
        }

        echo "\n✓ Toutes les migrations ont été appliquées.\n";
    }

    /**
     * Nettoie les anciens INSERT INTO schema_migrations du SQL
     * (pour rétrocompatibilité avec les anciennes migrations)
     */
    private function cleanOldMigrationInserts(string $sql): string
    {
        // Supprimer les lignes INSERT INTO schema_migrations
        $sql = preg_replace(
            '/INSERT\s+INTO\s+schema_migrations\s*\([^)]+\)\s*VALUES\s*\([^)]+\)\s*;/is',
            '-- [Nettoyé] INSERT INTO schema_migrations (géré par migrate.php)',
            $sql
        );

        return $sql;
    }

    /**
     * Affiche le statut des migrations
     */
    public function status(): void
    {
        $applied = $this->getAppliedMigrations();
        $available = $this->getAvailableMigrations();
        $pending = array_filter($available, fn($m) => !in_array($m['version'], $applied));

        echo "=== État des migrations ===\n\n";
        echo "Dossier: {$this->migrationsDir}\n\n";
        echo "Total fichiers: " . count($available) . "\n";
        echo "✓ Appliquées: " . count($applied) . "\n";
        echo "⏳ En attente: " . count($pending) . "\n";

        if (empty($available)) {
            echo "⚠️  Aucune migration trouvée.\n";
            return;
        }

        foreach ($pending as $migration) {
            echo "⏳ {$migration['version']} - {$migration['description']}\n";
        }
    }

    /**
     * Vérifie l'intégrité des migrations appliquées
     */
    public function verify(): void
    {
        echo "=== Vérification de l'intégrité ===\n\n";

        $stmt = $this->pdo->query('
            SELECT version, script_name, checksum, applied_at
            FROM schema_migrations
            ORDER BY version
        ');

        $appliedMigrations = $stmt->fetchAll();

        if (empty($appliedMigrations)) {
            echo "Aucune migration appliquée.\n";
            return;
        }

        $hasErrors = false;

        foreach ($appliedMigrations as $migration) {
            $filepath = $this->migrationsDir . $migration['script_name'];
            $status = '✓';
            $message = '';

            if (!file_exists($filepath)) {
                $status = '✗';
                $message = 'Fichier manquant';
                $hasErrors = true;
            } elseif ($migration['checksum']) {
                $currentChecksum = md5_file($filepath);
                if ($currentChecksum !== $migration['checksum']) {
                    $status = '⚠️';
                    $message = 'Fichier modifié après application';
                    $hasErrors = true;
                }
            }

            echo "{$status} {$migration['version']} - {$migration['script_name']} {$message}\n";
        }

        if ($hasErrors) {
            echo "\n⚠️  Des migrations ont été modifiées ou supprimées.\n";
        } else {
            echo "\n✓ Toutes les migrations sont intègres.\n";
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
    case 'verify':
        $manager->verify();
        break;
    default:
        echo "Usage: php migrate.php [up|status|verify]\n";
        echo "\n";
        echo "Commandes:\n";
        echo "  up, migrate  - Applique les migrations en attente\n";
        echo "  status       - Affiche l'état des migrations\n";
        echo "  verify       - Vérifie l'intégrité des migrations\n";
        exit(1);
}