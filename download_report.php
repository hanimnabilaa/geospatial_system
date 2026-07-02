<?php
session_start();
require_once 'db.php';
// Memuatkan autoloader untuk pustaka Dompdf
require_once 'dompdf/autoload.inc.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

// Memastikan hanya pengguna dengan peranan 'technician' mempunyai akses
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'technician') {
    exit("Unauthorized");
}

try {
    // ⚙️ OPTIMASI FYP: Memanggil Stored Procedure untuk mengambil data aduan selesai & metrik SLA
    $stmt = $pdo->query("CALL sp_get_resolved_complaints_sla()");
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Menutup cursor database bagi membebaskan sambungan pelayan
    $stmt->closeCursor();
} catch (PDOException $e) { 
    die("Database Error: " . $e->getMessage()); 
}

// 1. Membina struktur kandungan HTML untuk fail PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        h1 { color: #1e3a8a; text-align: center; margin-bottom: 5px; }
        .meta-info { text-align: center; color: #6b7280; font-size: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f3f4f6; padding: 10px; border: 1px solid #cbd5e1; text-align: left; color: #1e3a8a; }
        td { padding: 10px; border: 1px solid #e2e8f0; }
        .status { font-weight: bold; color: #10b981; }
        .delayed { font-weight: bold; color: #ef4444; }
    </style>
</head>
<body>

    <h1>SmartCity GIS - Performance Report</h1>
    <div class="meta-info">Generated on: ' . date('d M Y, H:i') . '</div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ID</th>
                <th style="width: 50%;">Location</th>
                <th style="width: 20%;">Resolution Time</th>
                <th style="width: 20%;">SLA Status</th>
            </tr>
        </thead>
        <tbody>';

// 2. Memetakan data rekod kerja dari database ke dalam baris jadual HTML
foreach ($history as $row) {
    // Penilaian SLA: Jika masa penyelesaian melebihi 48 jam, tandakan sebagai DELAYED
    $is_delayed = ($row['hours_taken'] > 48);
    $status = $is_delayed ? '<span class="delayed">DELAYED</span>' : '<span class="status">ON-TIME</span>';
    
    $html .= '<tr>
                <td>#' . htmlspecialchars($row['complaint_id']) . '</td>
                <td>' . htmlspecialchars($row['address']) . '</td>
                <td>' . htmlspecialchars($row['hours_taken']) . ' Hours</td>
                <td>' . $status . '</td>
              </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

// 3. Konfigurasi dan Permulaan Enjin Dompdf
$options = new Options();
$options->set('isRemoteEnabled', true); // Membawa sokongan pemuatan imej/aset luar jika diperlukan
$dompdf = new Dompdf($options);

// Memasukkan kod HTML ke dalam enjin penukar PDF
$dompdf->loadHtml($html);

// Menetapkan saiz kertas kepada A4 dengan orientasi menegak (Portrait)
$dompdf->setPaper('A4', 'portrait');

// Melakukan proses render kod HTML kepada fail PDF objek binari
$dompdf->render();

// 4. Memicu muat turun fail PDF secara automatik kepada pengguna
$dompdf->stream("Work_Performance_Report_" . date('Ymd') . ".pdf", array("Attachment" => 1));
exit();
?>