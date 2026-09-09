<?php
// API mínima para as Notas de Ensaio da Teima: guarda tudo num único
// ficheiro JSON (dados/musicas.json), como nos outros sites da banda.
header('Content-Type: application/json; charset=utf-8');

$dataDir = __DIR__ . '/dados';
$file = $dataDir . '/musicas.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!file_exists($file)) {
        echo json_encode(['songs' => []]);
        exit;
    }
    $fp = fopen($file, 'r');
    flock($fp, LOCK_SH);
    $contents = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    echo $contents !== false && $contents !== '' ? $contents : json_encode(['songs' => []]);
    exit;
}

if ($method === 'POST') {
    $body = file_get_contents('php://input');
    $decoded = json_decode($body, true);

    if (!is_array($decoded) || !array_key_exists('songs', $decoded) || !is_array($decoded['songs'])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid payload']);
        exit;
    }
    if (array_key_exists('repertorios', $decoded) && !is_array($decoded['repertorios'])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid payload']);
        exit;
    }

    $fp = fopen($file, 'c+');
    if (!$fp) {
        http_response_code(500);
        echo json_encode(['error' => 'cannot open store']);
        exit;
    }
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method not allowed']);
