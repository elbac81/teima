<?php
// Endpoint público e só de leitura para o calendário de concertos da
// homepage. Lê diretamente o mesmo ficheiro de armazenamento das Notas
// de Ensaio (ensaios/dados/musicas.json) mas devolve só os eventos —
// nunca músicas ou repertórios. As escritas só acontecem através de
// /ensaios/api.php, que fica protegido por password.
header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/ensaios/dados/musicas.json';

if (!file_exists($file)) {
    echo json_encode(['eventos' => []]);
    exit;
}

$fp = fopen($file, 'r');
flock($fp, LOCK_SH);
$contents = stream_get_contents($fp);
flock($fp, LOCK_UN);
fclose($fp);

$data = json_decode($contents, true);
$eventos = is_array($data) && isset($data['eventos']) && is_array($data['eventos']) ? $data['eventos'] : [];

echo json_encode(['eventos' => $eventos], JSON_UNESCAPED_UNICODE);
