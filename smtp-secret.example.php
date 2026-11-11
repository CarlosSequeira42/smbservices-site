<?php
// Copie este arquivo para "smtp-secret.php" diretamente no servidor
// (via upload no Gerenciador de Arquivos do hPanel) e preencha com os
// dados reais da caixa de e-mail contato@smbservices.com.br.
//
// O arquivo "smtp-secret.php" NUNCA deve ser commitado no Git
// (já está listado no .gitignore).
//
// Host/porta padrão da Hostinger para e-mail (Titan):
//   - Porta 465 (SSL), ou
//   - Porta 587 (STARTTLS)
// Confirme os dados exatos em hPanel > E-mails > Contas de e-mail > Configurar o cliente de e-mail.
return [
    'host'     => 'smtp.hostinger.com',
    'port'     => 465,
    'username' => 'contato@smbservices.com.br',
    'password' => 'COLOQUE_AQUI_A_SENHA_DA_CAIXA_DE_EMAIL',
];
