<?php
/* ==========================================================================
   Estruturas / guias — dados (API em JSON), para estruturas.php.

   O login é o Basic Auth de /privado/ (o Apache só deixa chegar aqui quem
   entrou). Os POST exigem o cabeçalho X-Teima (um formulário de outro site
   não o consegue pôr). GET devolve todas as músicas (?exportar=1 descarrega
   o ficheiro); POST grava ou apaga uma música, ou importa várias (só junta
   as que ainda não existem).

   Dados em dados/estruturas.json — só no servidor (fora do git e do deploy).
   Antes de cada gravação fica a versão anterior em dados/estruturas.bak.json.
   ========================================================================== */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$pasta = __DIR__ . '/dados';
$ficheiro = $pasta . '/estruturas.json';
if (!is_dir($pasta)) mkdir($pasta, 0755, true);

function ler($f) {
    $j = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    return is_array($j) && isset($j['songs']) && is_array($j['songs']) ? $j : array('songs' => array());
}
function responder($dados, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $d = ler($ficheiro);
    if (isset($_GET['exportar'])) {
        header('Content-Disposition: attachment; filename="teima-estruturas-' . date('Y-m-d') . '.json"');
    }
    echo json_encode(array('songs' => (object) $d['songs']), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(array('ok' => false, 'erro' => 'Método não suportado.'), 405);
if (empty($_SERVER['HTTP_X_TEIMA'])) responder(array('ok' => false, 'erro' => 'Pedido inválido.'), 400);

$corpo = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($corpo)) responder(array('ok' => false, 'erro' => 'Pedido inválido.'), 400);
$acao = isset($corpo['acao']) ? (string) $corpo['acao'] : '';
$limpa = function ($id) { return preg_replace('/[^a-z0-9]/i', '', (string) $id); };

/* Ler, mudar e gravar com o ficheiro trancado (duas pessoas ao mesmo tempo). */
$h = fopen($ficheiro . '.lock', 'c');
flock($h, LOCK_EX);
$d = ler($ficheiro);
$resposta = array('ok' => true);

if ($acao === 'gravar') {
    $id = $limpa(isset($corpo['id']) ? $corpo['id'] : '');
    if ($id === '' || !is_array(isset($corpo['dados']) ? $corpo['dados'] : null)) responder(array('ok' => false, 'erro' => 'Pedido inválido.'), 400);
    $d['songs'][$id] = $corpo['dados'];
} elseif ($acao === 'apagar') {
    $id = $limpa(isset($corpo['id']) ? $corpo['id'] : '');
    if ($id === '') responder(array('ok' => false, 'erro' => 'Pedido inválido.'), 400);
    unset($d['songs'][$id]);
} elseif ($acao === 'importar') {
    if (!is_array(isset($corpo['songs']) ? $corpo['songs'] : null)) responder(array('ok' => false, 'erro' => 'Ficheiro sem músicas.'), 400);
    $juntas = 0; $ja = 0;
    foreach ($corpo['songs'] as $id => $musica) {
        $id = $limpa($id);
        if ($id === '' || !is_array($musica)) continue;
        if (isset($d['songs'][$id])) { $ja++; continue; }
        $d['songs'][$id] = $musica; $juntas++;
    }
    $resposta = array('ok' => true, 'juntas' => $juntas, 'ja' => $ja);
} else {
    responder(array('ok' => false, 'erro' => 'Acção desconhecida.'), 400);
}

if (is_file($ficheiro)) @copy($ficheiro, $pasta . '/estruturas.bak.json');
$tmp = $ficheiro . '.tmp';
$ok = file_put_contents($tmp, json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false && rename($tmp, $ficheiro);
flock($h, LOCK_UN); fclose($h);
responder($ok ? $resposta : array('ok' => false, 'erro' => 'Não foi possível gravar.'), $ok ? 200 : 500);
