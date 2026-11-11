<?php
// Copie este arquivo para "auth-secret.php" diretamente no servidor
// (via upload no Gerenciador de Arquivos do hPanel) dentro da pasta
// "area-privada". Define o utilizador e a senha (em hash) do login
// da área privada.
//
// Para gerar o hash de uma nova senha, execute localmente:
//   php -r "echo password_hash('SUA_SENHA_AQUI', PASSWORD_BCRYPT), PHP_EOL;"
// e cole o resultado em 'senha_hash' abaixo.
//
// O arquivo "auth-secret.php" NUNCA deve ser commitado no Git
// (já está listado no .gitignore).
return [
    'utilizador'  => 'adm.15001BR',
    'senha_hash'  => 'COLOQUE_AQUI_O_HASH_GERADO_COM_password_hash',
];
