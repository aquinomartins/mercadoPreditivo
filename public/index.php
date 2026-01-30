<?php
// public/index.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$user = get_current_user_data($pdo);

$stmt = $pdo->query('SELECT * FROM markets ORDER BY close_at ASC');
$markets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Mercado Preditivo</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<div class="container">
    <header class="header">
        <h1>Mercado de Previsões</h1>
        <nav>
            <?php if ($user): ?>
                <span>Olá, <?= e($user['name']) ?> | Saldo: <?= number_format((float) $user['balance'], 2, ',', '.') ?> créditos</span>
                <a href="/public/dashboard.php">Dashboard</a>
                <?php if (!empty($user['is_admin'])): ?>
                    <a href="/admin/markets.php">Admin</a>
                <?php endif; ?>
                <a href="/public/logout.php">Sair</a>
            <?php else: ?>
                <a href="/public/login.php">Entrar</a>
                <a href="/public/register.php">Cadastrar</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="market-list">
        <?php foreach ($markets as $market): ?>
            <?php $prices = calculate_prices($market); ?>
            <article class="market-card">
                <h2><a href="/public/market.php?id=<?= (int) $market['id'] ?>"><?= e($market['title']) ?></a></h2>
                <p><?= e($market['description']) ?></p>
                <div class="market-meta">
                    <span>Status: <?= e($market['status']) ?></span>
                    <span>Fecha em: <?= e($market['close_at']) ?></span>
                </div>
                <div class="prices">
                    <span>YES: <?= number_format($prices['yes'], 4, ',', '.') ?></span>
                    <span>NO: <?= number_format($prices['no'], 4, ',', '.') ?></span>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$markets): ?>
            <p>Nenhum mercado disponível ainda.</p>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
