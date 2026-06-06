<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\SystemCheck;

Auth::require();
$checks = (new SystemCheck(Database::pdo()))->run();

$allOk = true;
$issues = [];
foreach ($checks as $check) {
    if (!$check['ok']) {
        $allOk = false;
        $issues[] = $check;
    }
}
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>System Doctor</title><link rel="stylesheet" href="assets/style.css"></head>
<body><main class="container">
<div style="display:flex;justify-content:space-between"><h1>System Doctor</h1><a href="index.php" style="color:#93c5fd">← в админку</a></div>
<section class="card" style="border-color:<?= $allOk ? '#14532d' : '#7f1d1d' ?>">
<h2><?= $allOk ? 'Система готова' : 'Найдены проблемы и неудобства' ?></h2>
<?php if (!$allOk): ?>
<ul>
<?php foreach ($issues as $issue): ?>
<li><b><?= htmlspecialchars($issue['name'], ENT_QUOTES, 'UTF-8') ?></b>: <?= htmlspecialchars($issue['info'], ENT_QUOTES, 'UTF-8') ?> (severity: <?= htmlspecialchars((string) ($issue['severity'] ?? 'n/a'), ENT_QUOTES, 'UTF-8') ?>)</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<table><thead><tr><th>Check</th><th>Status</th><th>Severity</th><th>Info</th></tr></thead><tbody>
<?php foreach ($checks as $check): ?>
<tr>
<td><?= htmlspecialchars($check['name'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= $check['ok'] ? 'OK' : 'FAIL' ?></td>
<td><?= htmlspecialchars((string) ($check['severity'] ?? 'n/a'), ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars($check['info'], ENT_QUOTES, 'UTF-8') ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</section>
</main></body></html>
