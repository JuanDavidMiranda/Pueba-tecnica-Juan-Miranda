<?php

declare(strict_types=1);

use App\Bootstrap;
use App\Database\Connection;
use App\Database\Migrator;

require dirname(__DIR__) . '/vendor/autoload.php';

Bootstrap::init(dirname(__DIR__));
Bootstrap::prepareDatabase();
Migrator::runFile(Connection::get(), dirname(__DIR__) . '/database/sql/productos.sql');

$pdo = Connection::get();
$counts = [
    'departamentos' => (int) $pdo->query('SELECT COUNT(*) FROM departamentos')->fetchColumn(),
    'municipios' => (int) $pdo->query('SELECT COUNT(*) FROM municipios')->fetchColumn(),
    'tipos_documento' => (int) $pdo->query('SELECT COUNT(*) FROM tipos_documento')->fetchColumn(),
    'genero' => (int) $pdo->query('SELECT COUNT(*) FROM genero')->fetchColumn(),
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'paciente' => (int) $pdo->query('SELECT COUNT(*) FROM paciente')->fetchColumn(),
    'productos' => (int) $pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn(),
];

echo "Base lista.\n";
foreach ($counts as $table => $count) {
    echo sprintf("  %-18s %d\n", $table, $count);
}
echo "Acceso: admin@sihos.local / 1234567890\n";
