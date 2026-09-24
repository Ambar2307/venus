<?php
require_once __DIR__ . '/../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['erro' => 'Método não permitido.'], 405);
}

$uf = strtoupper(trim((string)($_GET['uf'] ?? '')));

json_response(['cidades' => cities_for_state($uf)]);
