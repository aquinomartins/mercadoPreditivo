<?php
// api/buy_shares.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$marketId = (int) ($_POST['market_id'] ?? 0);
$side = $_POST['side'] ?? '';
$shares = (float) ($_POST['shares'] ?? 0);

if (!in_array($side, ['yes', 'no'], true) || $shares <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Dados inválidos.']);
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT * FROM markets WHERE id = ? FOR UPDATE');
    $stmt->execute([$marketId]);
    $market = $stmt->fetch();

    if (!$market || $market['status'] !== 'open') {
        throw new RuntimeException('Mercado fechado.');
    }

    $closeAt = new DateTime($market['close_at']);
    if ($closeAt <= new DateTime()) {
        throw new RuntimeException('Mercado já fechou.');
    }

    $prices = calculate_prices($market);
    $price = $side === 'yes' ? $prices['yes'] : $prices['no'];
    $totalCost = $shares * $price;

    $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
    $stmt->execute([$_SESSION['user_id']]);
    $balance = (float) $stmt->fetchColumn();

    if ($balance < $totalCost) {
        throw new RuntimeException('Saldo insuficiente.');
    }

    $stmt = $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?');
    $stmt->execute([$totalCost, $_SESSION['user_id']]);

    if ($side === 'yes') {
        $stmt = $pdo->prepare('UPDATE markets SET liquidity_yes = liquidity_yes + ? WHERE id = ?');
    } else {
        $stmt = $pdo->prepare('UPDATE markets SET liquidity_no = liquidity_no + ? WHERE id = ?');
    }
    $stmt->execute([$shares, $marketId]);

    $stmt = $pdo->prepare('INSERT INTO positions (user_id, market_id, side, shares) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE shares = shares + VALUES(shares)');
    $stmt->execute([
        $_SESSION['user_id'],
        $marketId,
        $side,
        $shares,
    ]);

    $stmt = $pdo->prepare('INSERT INTO transactions (user_id, market_id, side, shares, price, total_cost, type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $_SESSION['user_id'],
        $marketId,
        $side,
        $shares,
        $price,
        $totalCost,
        'buy',
    ]);

    $stmt = $pdo->prepare('SELECT * FROM markets WHERE id = ?');
    $stmt->execute([$marketId]);
    $updatedMarket = $stmt->fetch();

    $prices = calculate_prices($updatedMarket);

    $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $newBalance = $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT side, shares FROM positions WHERE user_id = ? AND market_id = ?');
    $stmt->execute([$_SESSION['user_id'], $marketId]);
    $positions = $stmt->fetchAll();

    $positionYes = '0.000000';
    $positionNo = '0.000000';
    foreach ($positions as $position) {
        if ($position['side'] === 'yes') {
            $positionYes = $position['shares'];
        }
        if ($position['side'] === 'no') {
            $positionNo = $position['shares'];
        }
    }

    $pdo->commit();

    echo json_encode([
        'balance' => $newBalance,
        'prices' => $prices,
        'position' => [
            'yes' => $positionYes,
            'no' => $positionNo,
        ],
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
