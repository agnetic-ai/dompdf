<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$options->set('defaultMediaType', 'print');
$options->set('defaultFont', 'Calibri');

$dompdf = new Dompdf($options);

// $html = file_get_contents('CPP-USD.html');
$html = file_get_contents('example.html');

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="template-gemini.pdf"');
echo $dompdf->output();
