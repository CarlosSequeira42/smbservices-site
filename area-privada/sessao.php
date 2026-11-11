<?php
/**
 * Controlo de sessão da área privada: login próprio (não mais Basic
 * Auth), com encerramento automático por inatividade.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tempo de inatividade (em segundos) até a sessão expirar sozinha.
define('SESSAO_TIMEOUT_SEGUNDOS', 20 * 60);

/**
 * Chame no topo de qualquer página protegida. Redireciona para o
 * login se não houver sessão válida ou se ela já tiver expirado por
 * inatividade.
 */
function exigir_autenticacao() {
    $autenticado = !empty($_SESSION['autenticado']);
    $expirado = $autenticado
        && isset($_SESSION['ultima_atividade'])
        && (time() - $_SESSION['ultima_atividade'] > SESSAO_TIMEOUT_SEGUNDOS);

    if (!$autenticado || $expirado) {
        $next = $_SERVER['REQUEST_URI'] ?? 'index.php';
        session_unset();
        session_destroy();
        header('Location: login.php?next=' . urlencode($next) . ($expirado ? '&expirado=1' : ''));
        exit;
    }

    $_SESSION['ultima_atividade'] = time();
}
