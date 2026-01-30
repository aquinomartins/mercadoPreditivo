<?php
// public/market.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$marketId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM markets WHERE id = ?');
$stmt->execute([$marketId]);
$market = $stmt->fetch();

if (!$market) {
    http_response_code(404);
    echo 'Mercado não encontrado.';
    exit;
}

$user = get_current_user_data($pdo);
$prices = calculate_prices($market);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= e($market['title']) ?> - Mercado</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<div class="container">
    <header class="header">
        <h1><?= e($market['title']) ?></h1>
        <nav>
            <a href="/public/index.php">Voltar</a>
            <?php if ($user): ?>
                <span class="balance">Saldo: <strong id="user-balance"><?= number_format((float) $user['balance'], 2, ',', '.') ?></strong> créditos</span>
                <a href="/public/dashboard.php">Dashboard</a>
                <a href="/public/logout.php">Sair</a>
            <?php else: ?>
                <a href="/public/login.php">Entrar</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="market-details">
        <p><?= e($market['description']) ?></p>
        <div class="market-meta">
            <span>Status: <strong id="market-status"><?= e($market['status']) ?></strong></span>
            <span>Fecha em: <?= e($market['close_at']) ?></span>
        </div>
        <div class="prices">
            <span>YES: <strong id="price-yes"><?= number_format($prices['yes'], 4, ',', '.') ?></strong></span>
            <span>NO: <strong id="price-no"><?= number_format($prices['no'], 4, ',', '.') ?></strong></span>
        </div>
    </section>

    <section class="market-actions">
        <?php if ($user): ?>
            <form id="buy-form" class="card">
                <input type="hidden" name="market_id" value="<?= (int) $market['id'] ?>">
                <label>Lado
                    <select name="side" required>
                        <option value="yes">SIM</option>
                        <option value="no">NÃO</option>
                    </select>
                </label>
                <label>Quantidade de shares
                    <input type="number" name="shares" min="0.0001" step="0.0001" required>
                </label>
                <button type="submit" id="buy-button">Comprar</button>
                <div id="buy-message" class="alert" hidden></div>
            </form>
        <?php else: ?>
            <p>Faça login para comprar shares.</p>
        <?php endif; ?>
    </section>

    <section class="market-position">
        <h2>Sua posição</h2>
        <div class="positions">
            <span>YES: <strong id="position-yes">0</strong></span>
            <span>NO: <strong id="position-no">0</strong></span>
        </div>
    </section>
</div>

<script>
    window.MARKET_ID = <?= (int) $market['id'] ?>;
</script>
<script src="/assets/market.js"></script>
</body>
</html>
