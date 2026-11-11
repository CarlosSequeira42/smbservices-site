<?php
/**
 * SMB Services - processa o formulário de contacto e envia por e-mail
 * para contato@smbservices.com.br, usando o envio local do servidor
 * (mail()), sem necessidade de senha de SMTP.
 */

$destino = 'contato@smbservices.com.br';
$siteUrl = 'https://smbservices.com.br';

function voltar($status) {
    global $siteUrl;
    header('Location: ' . $siteUrl . '/contacto.html?' . $status . '=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $siteUrl . '/contacto.html');
    exit;
}

// Honeypot anti-spam: se o campo "website" vier preenchido, é um bot.
if (!empty($_POST['website'])) {
    voltar('enviado'); // responde como sucesso para não dar pistas a bots
}

function campo($nome) {
    return isset($_POST[$nome]) ? trim(strip_tags($_POST[$nome])) : '';
}

$nome    = campo('name');
$empresa = campo('company');
$email   = campo('email');
$telefone = campo('phone');
$servico = campo('service');
$mensagem = campo('message');

if ($nome === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    voltar('erro');
}

// Remove quebras de linha de qualquer campo para evitar injeção de cabeçalhos.
$limpar = function ($valor) {
    return str_replace(array("\r", "\n"), '', $valor);
};
$nome = $limpar($nome);
$empresa = $limpar($empresa);
$email = $limpar($email);
$telefone = $limpar($telefone);
$servico = $limpar($servico);

$servicos = array(
    'health-check' => 'Backup Health Check',
    'resilience'   => 'Infrastructure Resilience Assessment',
    'ransomware'   => 'Ransomware Readiness Assessment',
    'migration'    => 'Backup Migration & Implementation',
    'advisory'     => 'Managed Backup Advisory',
);
$servicoLabel = isset($servicos[$servico]) ? $servicos[$servico] : 'Não especificado / orientação geral';

$assunto = 'Novo contacto pelo site - ' . $nome;

$corpo  = "Novo pedido de contacto recebido pelo site SMB Services:\n\n";
$corpo .= "Nome: {$nome}\n";
$corpo .= "Empresa: " . ($empresa !== '' ? $empresa : '-') . "\n";
$corpo .= "E-mail: {$email}\n";
$corpo .= "Telefone / WhatsApp: " . ($telefone !== '' ? $telefone : '-') . "\n";
$corpo .= "Serviço de interesse: {$servicoLabel}\n\n";
$corpo .= "Mensagem:\n" . $mensagem . "\n";

$headers  = "From: Site SMB Services <{$destino}>\r\n";
$headers .= "Reply-To: {$nome} <{$email}>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$enviado = mail($destino, $assunto, $corpo, $headers);

voltar($enviado ? 'enviado' : 'erro');
