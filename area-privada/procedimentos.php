<?php
/**
 * Funções de leitura/escrita dos procedimentos internos (área privada).
 * Guardados em procedimentos.json, no mesmo diretório — sem base de dados.
 */

define('PROCEDIMENTOS_FICHEIRO', __DIR__ . '/procedimentos.json');

define('CATEGORIAS', [
    'sistema-operativo' => 'Sistema Operativo',
    'armazenamento'     => 'Armazenamento',
    'cloud'             => 'Cloud',
    'backup'            => 'Backup',
    'redes'             => 'Redes',
    'devops'            => 'DevOps',
    'automacao'         => 'Automação',
    'ia'                => 'IA',
    'hardware'          => 'Hardware',
    'outros'            => 'Outros',
]);

function listar_procedimentos() {
    if (!is_readable(PROCEDIMENTOS_FICHEIRO)) {
        return [];
    }
    $conteudo = file_get_contents(PROCEDIMENTOS_FICHEIRO);
    $dados = json_decode($conteudo, true);
    if (!is_array($dados)) {
        return [];
    }
    // Compatibilidade: procedimentos antigos sem categoria caem em "outros".
    foreach ($dados as &$p) {
        if (empty($p['categoria']) || !isset(CATEGORIAS[$p['categoria']])) {
            $p['categoria'] = 'outros';
        }
    }
    unset($p);
    return $dados;
}

function guardar_procedimentos($procedimentos) {
    $json = json_encode($procedimentos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    // LOCK_EX evita corrupção do ficheiro se duas gravações acontecerem ao
    // mesmo tempo (ex.: dois separadores abertos a guardar ao mesmo tempo).
    return file_put_contents(PROCEDIMENTOS_FICHEIRO, $json, LOCK_EX) !== false;
}

function novo_id($procedimentos) {
    $maior = 0;
    foreach ($procedimentos as $p) {
        $maior = max($maior, (int) $p['id']);
    }
    return (string) ($maior + 1);
}

function agrupar_por_categoria($procedimentos) {
    $grupos = [];
    foreach (CATEGORIAS as $chave => $nome) {
        $grupos[$chave] = [];
    }
    foreach ($procedimentos as $p) {
        $grupos[$p['categoria']][] = $p;
    }
    return $grupos;
}
