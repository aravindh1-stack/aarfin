<?php
// ... (Security check at the top is unchanged) ...
require_once __DIR__ . '/../app/core/init.php';

echo "Starting Weekly Payment Creation Process with Running Balance...\n";

// Get current and previous week details
$currentWeek = (int)date('W');
$currentYear = (int)date('Y');
$previousTimestamp = strtotime('-1 week');
$previousWeek = (int)date('W', $previousTimestamp);
$previousYear = (int)date('Y', $previousTimestamp);

try {
    $members = $pdo->query("SELECT id, name, contribution_amount FROM members WHERE status = 'Active'")->fetchAll();
    echo "Found " . count($members) . " active members.\n\n";

    foreach ($members as $member) {
        echo "Processing Member: " . $member['name'] . "\n";

        // Check if payment for the CURRENT week already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE member_id = ? AND payment_week = ? AND payment_year = ?");
        $stmt->execute([$member['id'], $currentWeek, $currentYear]);
        if ($stmt->fetchColumn() > 0) {
            echo " -> Payment for current week already exists. Skipping.\n";
            continue;
        }

        // Find the previous week's balance
        $balance_from_last_week = 0;
        $prevStmt = $pdo->prepare("SELECT amount, amount_paid FROM payments WHERE member_id = ? AND payment_week = ? AND payment_year = ?");
        $prevStmt->execute([$member['id'], $previousWeek, $previousYear]);
        $prevPayment = $prevStmt->fetch();

        if ($prevPayment) {
            $balance = $prevPayment['amount'] - $prevPayment['amount_paid'];
            if ($balance > 0) {
                $balance_from_last_week = $balance;
                echo " -> Found a balance of " . $balance_from_last_week . " from last week.\n";
            }
        }

        // Calculate the total due for the new week
        $new_total_due = $member['contribution_amount'] + $balance_from_last_week;

        // Create the new 'Pending' payment record for the current week
        $insertStmt = $pdo->prepare("INSERT INTO payments (member_id, amount, amount_paid, payment_week, payment_year, status) VALUES (?, ?, 0.00, ?, ?, 'Pending')");
        $insertStmt->execute([$member['id'], $new_total_due, $currentWeek, $currentYear]);
        echo " -> SUCCESS: Created new 'Pending' payment with total due: " . $new_total_due . "\n";
    }

    echo "\nProcess Completed.\n";

} catch (PDOException $e) {
    echo "\nDATABASE ERROR: " . $e->getMessage() . "\n";
}