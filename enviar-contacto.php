<?php
/**
 * SMB Services - processa o formulário de contacto e envia por e-mail
 * para contato@smbservices.com.br.
 *
 * Usa SMTP autenticado (via PHPMailer, incluído em vendor/phpmailer) em
 * vez da função mail() do PHP: o envio autenticado é assinado/validado
 * corretamente pelo servidor da Hostinger, o que evita que as mensagens
 * caiam na pasta de spam (problema observado com mail() simples quando
 * o campo "Responder a" tinha um endereço de outro domínio, ex. Hotmail).
 *
 * As credenciais SMTP vivem apenas no servidor, em smtp-secret.php
 * (nunca commitado no Git — já está no .gitignore).
 */

require __DIR__ . '/vendor/phpmailer/Exception.php';
require __DIR__ . '/vendor/phpmailer/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

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

// Verificação reCAPTCHA v3: confirma com o Google que quem submeteu tem
// comportamento humano (score >= 0.5). A chave secreta vive apenas no
// servidor, em recaptcha-secret.php (nunca commitado no Git).
$recaptchaConfigPath = __DIR__ . '/recaptcha-secret.php';
if (file_exists($recaptchaConfigPath)) {
    $recaptchaConfig = include $recaptchaConfigPath;
    $recaptchaSecret = is_array($recaptchaConfig) && isset($recaptchaConfig['secret_key'])
        ? $recaptchaConfig['secret_key']
        : '';
    $token = isset($_POST['recaptcha_token']) ? $_POST['recaptcha_token'] : '';

    if ($recaptchaSecret !== '' && $recaptchaSecret !== 'COLOQUE_AQUI_A_SUA_CHAVE_SECRETA') {
        if ($token === '') {
            voltar('erro');
        }
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $contexto = stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query(array(
                    'secret'   => $recaptchaSecret,
                    'response' => $token,
                    'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
                )),
                'timeout' => 5,
            ),
        ));
        $resposta = @file_get_contents($verifyUrl, false, $contexto);
        $resultado = $resposta ? json_decode($resposta, true) : null;
        $pontuacao = is_array($resultado) && isset($resultado['score']) ? $resultado['score'] : 0;
        $sucesso = is_array($resultado) && !empty($resultado['success']);

        // Se não conseguirmos contactar o Google, deixamos passar (falha aberta)
        // em vez de bloquear pedidos legítimos por uma falha de rede.
        if ($resultado !== null && (!$sucesso || $pontuacao < 0.5)) {
            voltar('enviado'); // trata como spam sem dar pistas ao remetente
        }
    }
}

// Verifica se o pedido veio de uma página do próprio site (bloqueia envios
// feitos diretamente a este script, fora do formulário).
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
if ($referer !== '' && strpos($referer, 'smbservices.com.br') === false) {
    voltar('enviado');
}

// Verifica o tempo entre o carregamento da página e o envio: bots costumam
// submeter o formulário em menos de 2 segundos.
$carregadoEm = isset($_POST['loaded_at']) ? (int) $_POST['loaded_at'] : 0;
if ($carregadoEm > 0 && (time() - $carregadoEm) < 2) {
    voltar('enviado');
}

// Limite simples: no máximo 5 envios por IP a cada 60 minutos, guardado
// num ficheiro local (sem necessidade de base de dados).
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'desconhecido';
$limiteFicheiro = __DIR__ . '/.contacto-limite.json';
$janela = 3600; // 1 hora
$maxEnvios = 5;
$agora = time();
$registo = array();
if (is_readable($limiteFicheiro)) {
    $conteudo = json_decode(file_get_contents($limiteFicheiro), true);
    if (is_array($conteudo)) {
        $registo = $conteudo;
    }
}
// limpa entradas antigas e conta envios recentes deste IP
foreach ($registo as $chaveIp => $tempos) {
    $registo[$chaveIp] = array_values(array_filter($tempos, function ($t) use ($agora, $janela) {
        return ($agora - $t) < $janela;
    }));
    if (empty($registo[$chaveIp])) {
        unset($registo[$chaveIp]);
    }
}
$envioseRecentes = isset($registo[$ip]) ? count($registo[$ip]) : 0;
if ($envioseRecentes >= $maxEnvios) {
    voltar('erro');
}
$registo[$ip][] = $agora;
@file_put_contents($limiteFicheiro, json_encode($registo));

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

// Credenciais SMTP (smtp-secret.php, criado manualmente no servidor):
// return ['host' => 'smtp.hostinger.com', 'port' => 465, 'username' => 'contato@smbservices.com.br', 'password' => '...'];
$smtpConfigPath = __DIR__ . '/smtp-secret.php';
$enviado = false;

if (file_exists($smtpConfigPath)) {
    $smtpConfig = include $smtpConfigPath;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtpConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpConfig['username'];
        $mail->Password   = $smtpConfig['password'];
        $mail->SMTPSecure = ((int) $smtpConfig['port'] === 587) ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int) $smtpConfig['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtpConfig['username'], 'Site SMB Services');
        $mail->addAddress($destino);
        $mail->addReplyTo($email, $nome);
        $mail->Subject = $assunto;
        $mail->Body    = $corpo;

        $mail->send();
        $enviado = true;
    } catch (PHPMailerException $e) {
        $enviado = false;
    }
} else {
    // Modo de reserva (sem credenciais SMTP ainda configuradas): usa a
    // função mail() nativa do PHP. Menos confiável para entrega, mas
    // mantém o formulário a funcionar enquanto o SMTP não é configurado.
    $headers  = "From: Site SMB Services <{$destino}>\r\n";
    $headers .= "Reply-To: {$nome} <{$email}>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $enviado = mail($destino, $assunto, $corpo, $headers);
}

voltar($enviado ? 'enviado' : 'erro');
