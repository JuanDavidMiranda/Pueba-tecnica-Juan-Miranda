<?php

declare(strict_types=1);

$cssVersion = (string) filemtime(__DIR__ . '/../public/assets/css/app.css');
$jsVersion = (string) filemtime(__DIR__ . '/../public/assets/js/app.js');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14352c">
    <title>SIHOS · Censo de pacientes</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES) ?>">
</head>
<body>
<?php require __DIR__ . '/partials/login.php'; ?>
<?php require __DIR__ . '/partials/census.php'; ?>
<?php require __DIR__ . '/partials/patient-dialog.php'; ?>
<?php require __DIR__ . '/partials/delete-dialog.php'; ?>
    <div id="toasts" class="toasts" aria-live="polite"></div>
    <noscript>Active JavaScript para usar el censo de pacientes.</noscript>
    <script type="module" src="/assets/js/app.js?v=<?= htmlspecialchars($jsVersion, ENT_QUOTES) ?>"></script>
</body>
</html>
