<?php

declare(strict_types=1);

require __DIR__ . '/../app/Core/Autoloader.php';
$loader = new App\Core\Autoloader(__DIR__ . '/../app');
$loader->register();
$db = (new App\Core\Database(require __DIR__ . '/../config/database.php'))->pdo();
$compiler = new App\Graph\GraphCompiler();
$validator = new App\Graph\GraphValidator();

$stmt = $db->query('SELECT * FROM process_versions WHERE status IN ("draft","published") ORDER BY updated_at DESC LIMIT 100');
foreach ($stmt->fetchAll() as $version) {
    $graph = json_decode((string)$version['graph_json'], true) ?: [];
    $result = $validator->validate($graph);
    if ($result['status'] !== 'valid') {
        continue;
    }

    $compiled = $compiler->compile($graph);
    $db->prepare('UPDATE process_versions SET compiled_graph_json=:compiled, validation_status="valid", updated_at=NOW() WHERE id=:id')
        ->execute(['id' => $version['id'], 'compiled' => json_encode($compiled, JSON_UNESCAPED_UNICODE)]);
    file_put_contents(__DIR__ . '/../storage/compiled_graphs/' . $version['id'] . '.json', json_encode($compiled, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo "compiled version #{$version['id']}\n";
}
