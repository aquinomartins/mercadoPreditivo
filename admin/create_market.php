<?php
// admin/create_market.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$errors = [];
$title = '';
$description = '';
$closeAt = '';
$liquidityYes = '100.000000';
$liquidityNo = '100.000000';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $closeAt = trim($_POST['close_at'] ?? '');
    $liquidityYes = trim($_POST['liquidity_yes'] ?? '100');
    $liquidityNo = trim($_POST['liquidity_no'] ?? '100');

    if ($title === '') {
        $errors[] = 'Título é obrigatório.';
    }
    if ($closeAt === '') {
        $errors[] = 'Data de fechamento é obrigatória.';
    }
    if (!is_numeric($liquidityYes) || !is_numeric($liquidityNo)) {
        $errors[] = 'Liquidez inválida.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO markets (title, description, created_at, close_at, status, liquidity_yes, liquidity_no) VALUES (?, ?, NOW(), ?, ?, ?, ?)');
        $stmt->execute([
            $title,
            $description,
            $closeAt,
            'open',
            $liquidityYes,
            $liquidityNo,
        ]);
        header('Location: /admin/markets.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar Mercado</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<div class="container">
    <header class="header">
        <h1>Novo mercado</h1>
        <nav>
            <a href="/admin/markets.php">Voltar</a>
        </nav>
    </header>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="card">
        <label>Título
            <input type="text" name="title" value="<?= e($title) ?>" required>
        </label>
        <label>Descrição
            <textarea name="description" rows="4"><?= e($description) ?></textarea>
        </label>
        <label>Data/hora de fechamento
            <input type="datetime-local" name="close_at" value="<?= e($closeAt) ?>" required>
        </label>
        <label>Liquidez inicial YES
            <input type="number" name="liquidity_yes" step="0.000001" value="<?= e($liquidityYes) ?>" required>
        </label>
        <label>Liquidez inicial NO
            <input type="number" name="liquidity_no" step="0.000001" value="<?= e($liquidityNo) ?>" required>
        </label>
        <button type="submit">Criar mercado</button>
    </form>
</div>
</body>
</html>
