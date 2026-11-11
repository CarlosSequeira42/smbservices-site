<?php
/**
 * Funções de leitura/escrita dos procedimentos internos (área privada).
 * Guardados em procedimentos.json, no mesmo diretório — sem base de dados.
 */

define('PROCEDIMENTOS_FICHEIRO', __DIR__ . '/procedimentos.json');

function listar_procedimentos() {
    if (!is_readable(PROCEDIMENTOS_FICHEIRO)) {
        return [];
    }
    $conteudo = file_get_contents(PROCEDIMENTOS_FICHEIRO);
    $dados = json_decode($conteudo, true);
    return is_array($dados) ? $dados : [];
}

function guardar_procedimentos($procedimentos) {
    $json = json_encode($procedimentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents(PROCEDIMENTOS_FICHEIRO, $json) !== false;
}

function novo_id($procedimentos) {
    $maior = 0;
    foreach ($procedimentos as $p) {
        $maior = max($maior, (int) $p['id']);
    }
    return (string) ($maior + 1);
}
