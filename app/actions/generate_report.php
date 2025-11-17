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

// Calculate totals
$totalAmountAll = 0;
$totalPendingAll = 0;
foreach ($paymentList as $payment) {
    $amount = (float)$payment['amount'];
    $totalAmountAll += $amount;
    if ($payment['status'] === 'Pending' || $payment['status'] === 'Partial') {
        $totalPendingAll += $amount;
    }
}

// --- Generate the HTML content for the PDF ---
$html = "
<!DOCTYPE html>
<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h1 { text-align: center; color: #333; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .paid { color: green; }
    .pending { color: orange; }
    .partial { color: blue; }
</style>
</head>
<body>
    <h1>Aarfin Report</h1>
    <h2>Week: {$selectedWeek}, Year: {$selectedYear}</h2>
    <p><strong>Total Amount:</strong> " . formatCurrency($totalAmountAll) . "</p>
    <p><strong>Total Pending:</strong> " . formatCurrency($totalPendingAll) . "</p>
    <table>
        <thead>
            <tr>
                <th>Member Name</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";

if (count($paymentList) > 0) {
    foreach ($paymentList as $payment) {
        $statusClass = strtolower($payment['status']);
        $formattedAmount = formatCurrency($payment['amount']);
        $html .= "
            <tr>
                <td>" . htmlspecialchars($payment['name']) . "</td>
                <td>{$formattedAmount}</td>
                <td class='{$statusClass}'>" . htmlspecialchars($payment['status']) . "</td>
            </tr>";
    }
} else {
    $html .= "<tr><td colspan='3' style='text-align:center;'>No payment data found for this period.</td></tr>";
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