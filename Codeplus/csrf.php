<?php
declare(strict_types=1);

require __DIR__ . '/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
echo json_encode(['token' => csrf_token()], JSON_THROW_ON_ERROR);