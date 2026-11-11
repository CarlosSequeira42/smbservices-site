<?php
// Copie este arquivo para "db-secret.php" diretamente no servidor
// (via upload no Gerenciador de Arquivos do hPanel) dentro da pasta
// "area-privada". Os dados abaixo já correspondem à base de dados
// MySQL criada para os procedimentos da área privada.
//
// O arquivo "db-secret.php" NUNCA deve ser commitado no Git
// (já está listado no .gitignore).
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'u902022626_procedimentos',
    'username' => 'u902022626_proc_app',
    'password' => 'COLOQUE_AQUI_A_SENHA_DA_BASE_DE_DADOS',
];
