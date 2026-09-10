<?php
// HTTP Basic Auth não tem "logout" verdadeiro — o truque é responder
// sempre com 401 nesta página, o que faz o browser esquecer as
// credenciais guardadas para este realm. Depois o utilizador só
// precisa de voltar a /privado/ para lhe ser pedido login de novo.
header('WWW-Authenticate: Basic realm="Teima — notas de ensaio"');
header('HTTP/1.0 401 Unauthorized');
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sessão terminada — Teima</title>
  <style>
    body {
      background: #14171b;
      color: #f2ede0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      text-align: center;
      padding: 24px;
    }
    a {
      color: #e8c34a;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <p>Sessão terminada.<br><a href="/privado/">Entrar novamente</a></p>
</body>
</html>
