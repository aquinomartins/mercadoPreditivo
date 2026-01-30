<?php
// api/get_market.php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$marketId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM markets WHERE id = ?');
$stmt->execute([$marketId]);
$market = $stmt->fetch();

if (!$market) {
    http_response_code(404);
    echo json_encode(['error' => 'Mercado não encontrado.']);
    exit;
}

$prices = calculate_prices($market);

$positionYes = '0.000000';
$positionNo = '0.000000';

if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT side, shares FROM positions WHERE user_id = ? AND market_id = ?');
    $stmt->execute([$_SESSION['user_id'], $marketId]);
    $positions = $stmt->fetchAll();

    foreach ($positions as $position) {
        if ($position['side'] === 'yes') {
            $positionYes = $position['shares'];
        }
        if ($position['side'] === 'no') {
            $positionNo = $position['shares'];
        }
    }
}

$response = [
    'market' => [
        'id' => $market['id'],
        'title' => $market['title'],
        'description' => $market['description'],
        'status' => $market['status'],
        'close_at' => $market['close_at'],
        'result' => $market['result'],
    ],
    'prices' => [
        'yes' => $prices['yes'],
        'no' => $prices['no'],
    ],
    'position' => [
        'yes' => $positionYes,
        'no' => $positionNo,
    ],
];

if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $balance = $stmt->fetchColumn();
    $response['balance'] = $balance;
}

echo json_encode($response);
