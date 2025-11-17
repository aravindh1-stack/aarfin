<?php
// Load our application's core file
require_once __DIR__ . '/../core/init.php';

// FIX #1: Use a more reliable path to load the Dompdf library
require_once __DIR__ . '/../lib/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get the week and year from the URL
$selectedWeek = (int)($_GET['week'] ?? date('W'));
$selectedYear = (int)($_GET['year'] ?? date('Y'));
$params = [$selectedWeek, $selectedYear];

// Fetch the data from the database
$stmt = $pdo->prepare("
    SELECT p.status, p.amount, m.name
    FROM payments p
    JOIN members m ON p.member_id = m.id
    WHERE p.payment_week = ? AND p.payment_year = ?
    ORDER BY m.name ASC
");
$stmt->execute($params);
$paymentList = $stmt->fetchAll();

// Build pending/unpaid members list (all active members for this week)
$stmtPending = $pdo->prepare("
    SELECT
        m.name,
        m.contribution_amount,
        p.amount AS payment_amount,
        p.amount_paid,
        p.status
    FROM members m
    LEFT JOIN payments p
        ON p.member_id = m.id
        AND p.payment_week = ?
        AND p.payment_year = ?
    WHERE m.status = 'Active'
    ORDER BY m.name ASC
");
$stmtPending->execute($params);
$pendingRows = $stmtPending->fetchAll();

// Calculate totals and basic stats from existing payment rows
$totalAmountAll = 0;
$totalPendingAll = 0;
$paidTotal = 0;
$paidCount = 0;
$pendingCount = 0;

foreach ($paymentList as $payment) {
    $amount = (float)$payment['amount'];
    $totalAmountAll += $amount;

    if ($payment['status'] === 'Paid') {
        $paidTotal += $amount;
        $paidCount++;
    }

    if ($payment['status'] === 'Pending' || $payment['status'] === 'Partial') {
        $totalPendingAll += $amount;
        $pendingCount++;
    }
}

// Derive pending members with balances (all active members for the week)
$pendingMembers = [];
foreach ($pendingRows as $row) {
    $due = $row['payment_amount'] !== null ? (float)$row['payment_amount'] : (float)$row['contribution_amount'];
    $paid = $row['amount_paid'] !== null ? (float)$row['amount_paid'] : 0.0;
    $balance = max(0, $due - $paid);

    if ($balance > 0) {
        $statusLabel = 'Unpaid';
        if ($paid > 0 && $paid < $due) {
            $statusLabel = 'Partial';
        }
        if ($row['status'] === 'Pending') {
            $statusLabel = 'Pending';
        }

        $pendingMembers[] = [
            'name' => $row['name'],
            'due' => $due,
            'paid' => $paid,
            'balance' => $balance,
            'status' => $statusLabel,
        ];
    }
}

// Override pending totals using all pending/unpaid members (including those without a payment row)
$pendingCount = count($pendingMembers);
$totalPendingAll = 0;
foreach ($pendingMembers as $m) {
    $totalPendingAll += (float)$m['balance'];
}

// Recompute overall total as paid + pending balances
$totalAmountAll = $paidTotal + $totalPendingAll;

$generatedDate = date('d M Y');

// --- Generate the HTML content for the PDF ---
$html = "
<!DOCTYPE html>
<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 24px; }
    h1 { font-size: 20px; margin: 0; }
    h2 { font-size: 13px; margin: 0; color: #6B7280; }
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        font-size: 11px;
    }
    .header-table,
    .header-table th,
    .header-table td {
        border: 1px solid #E5E7EB;
    }
    .header-title-cell {
        background-color: #F9FAFB;
        padding: 10px 0 8px 0;
        text-align: center;
        font-weight: bold;
    }
    .header-meta-cell {
        background-color: #F9FAFB;
        padding: 6px 8px 6px 8px;
        text-align: center;
        color: #4B5563;
        font-size: 10px;
    }
    .header-meta-label {
        font-weight: bold;
        color: #111827;
    }
    .summary { width: 100%; border-collapse: collapse; margin: 12px 0 18px 0; font-size: 10px; }
    .summary th, .summary td { padding: 6px 8px; border: 1px solid #E5E7EB; }
    .summary th { background-color: #F3F4F6; text-align: left; }
    .summary-title { font-weight: bold; font-size: 11px; padding-bottom: 4px; }
    .summary-small { color: #6B7280; font-size: 9px; }
    table.payments { width: 100%; border-collapse: collapse; font-size: 10px; }
    table.payments th, table.payments td { border: 1px solid #E5E7EB; padding: 6px 8px; }
    table.payments th { background-color: #F9FAFB; text-align: left; font-weight: bold; }
    .text-right { text-align: right; }
    .status-badge { display: inline-block; padding: 2px 6px; border-radius: 999px; font-size: 9px; font-weight: bold; }
    .status-paid { background-color: #ECFDF3; color: #166534; border: 1px solid #BBF7D0; }
    .status-pending { background-color: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .status-partial { background-color: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
    .no-data { text-align: center; padding: 18px 0; color: #6B7280; }
</style>
</head>
<body>
    <table class='header-table'>
        <tr>
            <td colspan='2' class='header-title-cell'>Aarfin Weekly Report</td>
        </tr>
        <tr>
            <td class='header-meta-cell'>
                <span class='header-meta-label'>Week:</span> Week {$selectedWeek}, {$selectedYear}
            </td>
            <td class='header-meta-cell'>
                <span class='header-meta-label'>Generated on:</span> {$generatedDate}
            </td>
        </tr>
    </table>

    <div class='summary-title'>Summary</div>
    <table class='summary'>
        <tr>
            <th>Total Amount</th>
            <th>Paid Amount</th>
            <th>Pending Amount</th>
        </tr>
        <tr>
            <td>" . formatCurrency($totalAmountAll) . "</td>
            <td>" . formatCurrency($paidTotal) . "</td>
            <td>" . formatCurrency($totalPendingAll) . "</td>
        </tr>
        <tr>
            <th>Paid Entries</th>
            <th>Pending / Partial Entries</th>
            <th>Total Entries</th>
        </tr>
        <tr>
            <td>{$paidCount}</td>
            <td>{$pendingCount}</td>
            <td>" . count($paymentList) . "</td>
        </tr>
    </table>

    <div class='summary-small'>This report lists all member payments recorded for the selected week, including their amount and payment status.</div>

    <h2 style='margin-top:16px; margin-bottom:6px; font-size:12px; color:#111827;'>Detailed Payments</h2>
    <table class='payments'>
        <thead>
            <tr>
                <th>Member Name</th>
                <th class='text-right'>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";

if (count($paymentList) > 0) {
    foreach ($paymentList as $payment) {
        $formattedAmount = formatCurrency($payment['amount']);
        $status = $payment['status'];
        $statusLower = strtolower($status);
        $statusClass = 'status-pending';
        if ($status === 'Paid') { $statusClass = 'status-paid'; }
        elseif ($status === 'Partial') { $statusClass = 'status-partial'; }

        $html .= "
            <tr>
                <td>" . htmlspecialchars($payment['name']) . "</td>
                <td class='text-right'>{$formattedAmount}</td>
                <td><span class='status-badge {$statusClass}'>" . htmlspecialchars($status) . "</span></td>
            </tr>";
    }
} else {
    $html .= "<tr><td colspan='3' class='no-data'>No payment data found for this period.</td></tr>";
}

$html .= "
        </tbody>
    </table>

    <h2 style='margin-top:18px; margin-bottom:6px; font-size:12px; color:#111827;'>Pending / Unpaid Members</h2>
    <table class='payments'>
        <thead>
            <tr>
                <th>Member Name</th>
                <th class='text-right'>Due</th>
                <th class='text-right'>Paid</th>
                <th class='text-right'>Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";

if (count($pendingMembers) > 0) {
    foreach ($pendingMembers as $m) {
        $dueText = formatCurrency($m['due']);
        $paidText = formatCurrency($m['paid']);
        $balanceText = formatCurrency($m['balance']);
        $status = $m['status'];
        $statusClass = 'status-pending';
        if ($status === 'Paid') { $statusClass = 'status-paid'; }
        elseif ($status === 'Partial') { $statusClass = 'status-partial'; }

        $html .= "
            <tr>
                <td>" . htmlspecialchars($m['name']) . "</td>
                <td class='text-right'>{$dueText}</td>
                <td class='text-right'>{$paidText}</td>
                <td class='text-right'>{$balanceText}</td>
                <td><span class='status-badge {$statusClass}'>" . htmlspecialchars($status) . "</span></td>
            </tr>";
    }
} else {
    $html .= "<tr><td colspan='5' class='no-data'>No pending members for this week.</td></tr>";
}

$html .= "
        </tbody>
    </table>
</body>
</html>
";

// --- Create the PDF ---
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

// FIX #2: Corrected the typo from $dompopdf to $dompdf
$dompdf->render();

$filename = "Aarfin_Report_W{$selectedWeek}_{$selectedYear}.pdf";

// Stream the file to the browser to force a download
$dompdf->stream($filename, ["Attachment" => true]);