<?php

// Charger les variables d'environnement depuis .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Forcer l'environnement de test (optionnel, si tu veux un flag)
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';

require_once __DIR__ . '/../vendor/autoload.php';