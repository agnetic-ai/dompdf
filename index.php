<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultMediaType', 'screen');
$options->set('defaultFont', 'Calibri');

$dompdf = new Dompdf($options);

$html = file_get_contents('riplay_fixed.html');

// Fix: border-collapse: collapse → separate (DomPDF page-break compatibility)
$html = str_replace(
    'border-collapse: collapse;',
    'border-collapse: separate; border-spacing: 0;',
    $html
);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output to browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="riplay_100_percent.pdf"');
echo $dompdf->output();
