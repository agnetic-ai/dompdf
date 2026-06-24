<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Accept JSON from POST body or query param
$json = file_get_contents('php://input');
if (empty($json)) {
    http_response_code(400);
    echo json_encode(['error' => 'No JSON body']);
    exit;
}

$data = json_decode($json, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Load template
$template = isset($data['template']) ? $data['template'] : 'example.html';
$html = file_get_contents($template);
if (!$html) {
    http_response_code(404);
    echo json_encode(['error' => 'Template not found']);
    exit;
}

// Replace {key} placeholders with JSON values
foreach ($data as $key => $value) {
    if ($key === 'template') continue;
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
$filename = ($data['cpp_no_spaj'] ?? 'document') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($dompdf->output()));
echo $dompdf->output();
