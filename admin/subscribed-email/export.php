<?php
require_once __DIR__ . '/../auth.php';

$format = isset($_GET['format']) ? strtolower((string)$_GET['format']) : 'csv';

if ($format === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    $filename = 'subscribed_emails_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Disposition: attachment; filename=' . $filename);
} else {
    header('Content-Type: text/csv; charset=UTF-8');
    $filename = 'subscribed_emails_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}
header('Pragma: no-cache');
header('Expires: 0');

$mysqli = getMysqliConnection();

// Open output stream
$output = fopen('php://output', 'w');

if ($format === 'xls') {
    // HTML table that Excel can open, with friendly column width (no BOM)
    echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"></head><body>";
    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"4\" style=\"border-collapse:collapse;\">";
    echo "<colgroup><col style=\"width:40ch\"></colgroup>";
    echo "<thead><tr><th>Email</th></tr></thead><tbody>";
} else {
    // Header row (no BOM)
    fputcsv($output, ['Email']);
}

try {
    if ($res = $mysqli->query("SELECT email FROM subscribed_email ORDER BY id ASC")) {
        while ($row = $res->fetch_assoc()) {
            if ($format === 'xls') {
                $email = htmlspecialchars((string)$row['email'], ENT_QUOTES, 'UTF-8');
                echo '<tr><td>' . $email . '</td></tr>';
            } else {
                fputcsv($output, [(string)$row['email']]);
            }
        }
        $res->free();
    }
} catch (Throwable $e) {
    // Write an error row if query fails
    if ($format === 'xls') {
        echo '<tr><td>Unable to export subscribed emails</td></tr>';
    } else {
        fputcsv($output, ['Unable to export subscribed emails']);
    }
}

if ($format === 'xls') {
    echo '</tbody></table></body></html>';
} else {
    fclose($output);
}
exit;
?>


