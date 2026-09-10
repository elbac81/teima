<?php
// API mínima para as Notas de Ensaio da Teima: guarda tudo num único
// ficheiro JSON (dados/musicas.json), como nos outros sites da banda.
//
// Notas privadas (a nível da app e a nível de cada secção de cada
// música) ficam guardadas num mapa notasUtilizador (chave = login do
// Basic Auth) mas esse mapa NUNCA é devolvido inteiro ao cliente — só
// a entrada do próprio utilizador (campo notaPropria), para que cada
// um só veja o que escreveu.
header('Content-Type: application/json; charset=utf-8');

$dataDir = __DIR__ . '/dados';
$file = $dataDir . '/musicas.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

function currentUser() {
    if (!empty($_SERVER['PHP_AUTH_USER'])) {
        return $_SERVER['PHP_AUTH_USER'];
    }
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    if ($header && stripos($header, 'Basic ') === 0) {
        $decoded = base64_decode(substr($header, 6));
        if ($decoded !== false && strpos($decoded, ':') !== false) {
            return explode(':', $decoded, 2)[0];
        }
    }
    return null;
}

// Substitui, em cada secção de cada música, o mapa privado
// notasUtilizador pela nota do próprio utilizador (notaPropria).
function stripSectionNotes($songs, $user) {
    return array_map(function ($song) use ($user) {
        $sections = is_array($song['sections'] ?? null) ? $song['sections'] : [];
        $song['sections'] = array_map(function ($sec) use ($user) {
            $nu = is_array($sec['notasUtilizador'] ?? null) ? $sec['notasUtilizador'] : [];
            $sec['notaPropria'] = ($user && isset($nu[$user]) && is_string($nu[$user])) ? $nu[$user] : '';
            unset($sec['notasUtilizador']);
            return $sec;
        }, $sections);
        return $song;
    }, $songs);
}

// Junta as músicas recebidas do cliente com os mapas notasUtilizador já
// guardados (que o cliente nunca viu inteiros), atualizando só a
// entrada do utilizador atual em cada secção.
function mergeSectionNotes($incomingSongs, $existingSongs, $user) {
    $existingSongsById = [];
    foreach ($existingSongs as $s) {
        if (isset($s['id'])) {
            $existingSongsById[$s['id']] = $s;
        }
    }
    return array_map(function ($song) use ($existingSongsById, $user) {
        $songId = $song['id'] ?? null;
        $existingSections = [];
        if ($songId && isset($existingSongsById[$songId]['sections']) && is_array($existingSongsById[$songId]['sections'])) {
            foreach ($existingSongsById[$songId]['sections'] as $es) {
                if (isset($es['id'])) {
                    $existingSections[$es['id']] = $es;
                }
            }
        }
        $sections = is_array($song['sections'] ?? null) ? $song['sections'] : [];
        $song['sections'] = array_map(function ($sec) use ($existingSections, $user) {
            $secId = $sec['id'] ?? null;
            $prevNu = ($secId && isset($existingSections[$secId]['notasUtilizador']) && is_array($existingSections[$secId]['notasUtilizador']))
                ? $existingSections[$secId]['notasUtilizador']
                : [];
            if ($user && array_key_exists('notaPropria', $sec) && is_string($sec['notaPropria'])) {
                $prevNu[$user] = $sec['notaPropria'];
            }
            unset($sec['notaPropria']);
            $sec['notasUtilizador'] = $prevNu;
            return $sec;
        }, $sections);
        return $song;
    }, $incomingSongs);
}

$method = $_SERVER['REQUEST_METHOD'];
$user = currentUser();

if ($method === 'GET') {
    $data = ['songs' => []];
    if (file_exists($file)) {
        $fp = fopen($file, 'r');
        flock($fp, LOCK_SH);
        $contents = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        $decoded = json_decode($contents, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    $notasUtilizador = is_array($data['notasUtilizador'] ?? null) ? $data['notasUtilizador'] : [];
    $songs = is_array($data['songs'] ?? null) ? $data['songs'] : [];
    echo json_encode([
        'songs' => stripSectionNotes($songs, $user),
        'repertorios' => $data['repertorios'] ?? [],
        'eventos' => $data['eventos'] ?? [],
        'notasGerais' => is_string($data['notasGerais'] ?? null) ? $data['notasGerais'] : '',
        'notaPropria' => ($user && isset($notasUtilizador[$user]) && is_string($notasUtilizador[$user])) ? $notasUtilizador[$user] : '',
        'user' => $user,
    ], JSON_UNESCAPED_UNICODE);
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
    if (array_key_exists('eventos', $decoded) && !is_array($decoded['eventos'])) {
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
    $existingRaw = stream_get_contents($fp);
    $existing = json_decode($existingRaw, true);
    if (!is_array($existing)) {
        $existing = [];
    }
    $notasUtilizador = is_array($existing['notasUtilizador'] ?? null) ? $existing['notasUtilizador'] : [];
    if ($user && array_key_exists('notaPropria', $decoded) && is_string($decoded['notaPropria'])) {
        $notasUtilizador[$user] = $decoded['notaPropria'];
    }
    $existingSongs = is_array($existing['songs'] ?? null) ? $existing['songs'] : [];

    $toWrite = [
        'songs' => mergeSectionNotes($decoded['songs'], $existingSongs, $user),
        'repertorios' => $decoded['repertorios'] ?? ($existing['repertorios'] ?? []),
        'eventos' => $decoded['eventos'] ?? ($existing['eventos'] ?? []),
        'notasGerais' => is_string($decoded['notasGerais'] ?? null) ? $decoded['notasGerais'] : ($existing['notasGerais'] ?? ''),
        'notasUtilizador' => $notasUtilizador,
    ];

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($toWrite, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'method not allowed']);
