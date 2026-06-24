<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Hardcoded JSON data
$json = '{
  "cpp_no_spaj": "PP11611973",
  "cpp_nama_pp": "LINNA MARSELLA THE",
  "cpp_nama_tt": "LINNA MARSELLA THE",
  "cpp_tgl_asu": "23 Juni 2026",
  "cpp_dbo": "11 Maret 1970",
  "cpp_age": "56 Tahun",
  "cpp_premi": "183.000.000,00 IDR",
  "cpp_periode": "6 Bulan",
  "cpp_masa_asu": "1 (Satu) Tahun",
  "cpp_akhir_asu": "22 Juni 2027"
}';

$data = json_decode($json, true);

// Template from GET param, default example.html
$template = isset($_GET['template']) ? $_GET['template'] : 'example.html';
$html = file_get_contents($template);
if (!$html) {
    http_response_code(404);
    die('Template not found');
}

// Replace {key} placeholders
foreach ($data as $key => $value) {
    $html = str_replace('{' . $key . '}', $value, $html);
}

// Generate PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultMediaType', 'print');
$options->set('defaultFont', 'Calibri');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Return as file download
$filename = $data['cpp_no_spaj'] . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($dompdf->output()));
echo $dompdf->output();
