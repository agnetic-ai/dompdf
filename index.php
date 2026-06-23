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

$html = file_get_contents('template_riplay_100_percent.html');

// Fix broken CSS from Aspose export
$html = str_replace('max- !important;', 'max-width: 100% !important;', $html);
$html = str_replace(' !important; font-size:', ' font-size:', $html);
$html = str_replace("font-size: 8.5pt !important;", "font-size: 10pt !important;", $html);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output to browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="riplay_100_percent.pdf"');
echo $dompdf->output();
