<?php

require dirname(__DIR__).'/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Different local Compose databases must not share the same test file collection.
$testStorageRoot = $_SERVER['TEST_STORAGE_ROOT'] ?? $_ENV['TEST_STORAGE_ROOT'] ?? null;
if (is_string($testStorageRoot) && $testStorageRoot !== '') {
    $_SERVER['STORAGE_ROOT'] = $_ENV['STORAGE_ROOT'] = $testStorageRoot;
    putenv('STORAGE_ROOT='.$testStorageRoot);
}
