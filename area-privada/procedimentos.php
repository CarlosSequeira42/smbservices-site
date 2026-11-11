<?php
/**
 * Camada de dados dos procedimentos da área privada.
 * Armazenamento em MySQL (ver db-secret.php). Se existir um
 * procedimentos.json de uma versão anterior, os seus dados são
 * migrados automaticamente para a base de dados na primeira execução
 * e o ficheiro é renomeado para procedimentos.json.bak.
 */

define('CATEGORIAS', [
    'armazenamento'     => 'Armazenamento',
    'automacao'         => 'Automação',
    'backup'            => 'Backup',
    'cloud'             => 'Cloud',
    'devops'            => 'DevOps',
    'hardware'          => 'Hardware',
    'ia'                => 'IA',
    'redes'             => 'Redes',
    'seguranca'         => 'Segurança',
    'sistema-operativo' => 'Sistema Operativo',
    'outros'            => 'Outros',
]);

/**
 * Liga à base de dados (ligação única por pedido), garante que a
 * tabela existe e migra dados antigos do JSON, se necessário.
 */
function conectar_bd() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $configPath = __DIR__ . '/db-secret.php';
    if (!file_exists($configPath)) {
        throw new RuntimeException('Configuração da base de dados ainda não foi definida (falta o ficheiro db-secret.php).');
    }

    $config = include $configPath;
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['dbname']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    garantir_schema($pdo);
    migrar_json_se_necessario($pdo);

    return $pdo;
}

function garantir_schema($pdo) {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS procedimentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categoria VARCHAR(40) NOT NULL DEFAULT 'outros',
            titulo VARCHAR(255) NOT NULL,
            conteudo MEDIUMTEXT,
            criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_categoria (categoria)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function migrar_json_se_necessario($pdo) {
    $jsonPath = __DIR__ . '/procedimentos.json';
    if (!file_exists($jsonPath)) {
        return;
    }

    $total = (int) $pdo->query('SELECT COUNT(*) FROM procedimentos')->fetchColumn();
    if ($total > 0) {
        // Já migrado anteriormente; só arquiva o ficheiro antigo.
        @rename($jsonPath, $jsonPath . '.bak');
        return;
    }

    $dados = json_decode(file_get_contents($jsonPath), true);
    if (!is_array($dados)) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO procedimentos (categoria, titulo, conteudo) VALUES (:categoria, :titulo, :conteudo)'
    );
    foreach ($dados as $p) {
        $categoria = (isset($p['categoria']) && isset(CATEGORIAS[$p['categoria']])) ? $p['categoria'] : 'outros';
        $stmt->execute([
            'categoria' => $categoria,
            'titulo'    => $p['titulo'] ?? '',
            'conteudo'  => $p['conteudo'] ?? '',
        ]);
    }

    @rename($jsonPath, $jsonPath . '.bak');
}

/**
 * Lista procedimentos, opcionalmente filtrando por título ou conteúdo.
 */
function listar_procedimentos($busca = null) {
    $pdo = conectar_bd();

    if ($busca !== null && trim($busca) !== '') {
        $stmt = $pdo->prepare(
            'SELECT * FROM procedimentos WHERE titulo LIKE :q OR conteudo LIKE :q ORDER BY categoria, titulo'
        );
        $stmt->execute(['q' => '%' . $busca . '%']);
    } else {
        $stmt = $pdo->query('SELECT * FROM procedimentos ORDER BY categoria, titulo');
    }

    $linhas = $stmt->fetchAll();
    foreach ($linhas as &$p) {
        $p['id'] = (string) $p['id'];
        if (!isset(CATEGORIAS[$p['categoria']])) {
            $p['categoria'] = 'outros';
        }
    }
    unset($p);

    return $linhas;
}

function obter_procedimento($id) {
    $pdo = conectar_bd();
    $stmt = $pdo->prepare('SELECT * FROM procedimentos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $p = $stmt->fetch();
    if ($p) {
        $p['id'] = (string) $p['id'];
        if (!isset(CATEGORIAS[$p['categoria']])) {
            $p['categoria'] = 'outros';
        }
    }
    return $p ?: null;
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

function inserir_procedimento($titulo, $categoria, $conteudo) {
    if (!isset(CATEGORIAS[$categoria])) {
        $categoria = 'outros';
    }
    $pdo = conectar_bd();
    $stmt = $pdo->prepare(
        'INSERT INTO procedimentos (categoria, titulo, conteudo) VALUES (:categoria, :titulo, :conteudo)'
    );
    $stmt->execute(['categoria' => $categoria, 'titulo' => $titulo, 'conteudo' => $conteudo]);
}

function atualizar_procedimento($id, $titulo, $categoria, $conteudo) {
    if (!isset(CATEGORIAS[$categoria])) {
        $categoria = 'outros';
    }
    $pdo = conectar_bd();
    $stmt = $pdo->prepare(
        'UPDATE procedimentos SET titulo = :titulo, categoria = :categoria, conteudo = :conteudo WHERE id = :id'
    );
    $stmt->execute(['titulo' => $titulo, 'categoria' => $categoria, 'conteudo' => $conteudo, 'id' => $id]);
}

function remover_procedimento($id) {
    $pdo = conectar_bd();
    $stmt = $pdo->prepare('DELETE FROM procedimentos WHERE id = :id');
    $stmt->execute(['id' => $id]);
}
