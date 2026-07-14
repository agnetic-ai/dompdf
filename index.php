<?php
require_once __DIR__ . '/cpp-pdf-generator.php';

function cpp_sample_json_files()
{
    return array(
        'IDR' => __DIR__ . '/SPAJ_JSON_IDR_20260623033241.json',
        'USD' => __DIR__ . '/SPAJ_JSON_USD_20260623033241.json',
    );
}

function fail_response($statusCode, $message)
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

function data_from_request()
{
    if (isset($_GET['sample']) && is_scalar($_GET['sample'])) {
        return data_from_sample((string) $_GET['sample']);
    }

    $rawJson = file_get_contents('php://input');
    if (($rawJson === false || trim($rawJson) === '') && PHP_SAPI === 'cli') {
        $rawJson = file_get_contents('php://stdin');
    }

    if (is_string($rawJson) && trim($rawJson) !== '') {
        return data_from_json($rawJson);
    }

    if (isset($_GET['data']) && is_scalar($_GET['data']) && trim((string) $_GET['data']) !== '') {
        return data_from_json((string) $_GET['data']);
    }

    $data = array();
    foreach ($_GET as $key => $value) {
        if ($key !== 'data' && is_scalar($value)) {
            $data[$key] = $value;
        }
    }

    if ($data === array()) {
        throw new InvalidArgumentException('Request data kosong. Kirim JSON body, query ?data={...}, atau field cpp_* via query string.');
    }

    return $data;
}

function data_from_sample($sample)
{
    $sample = strtoupper(trim($sample));
    $sampleFiles = cpp_sample_json_files();
    if (!array_key_exists($sample, $sampleFiles)) {
        throw new InvalidArgumentException('Sample JSON tidak dikenal. Gunakan IDR atau USD.');
    }

    $json = file_get_contents($sampleFiles[$sample]);
    if ($json === false) {
        throw new RuntimeException('Sample JSON tidak ditemukan: ' . basename($sampleFiles[$sample]));
    }

    return data_from_json($json);
}

function data_from_json($json)
{
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException('Invalid JSON data: ' . json_last_error_msg());
    }

    if (!is_array($data)) {
        throw new InvalidArgumentException('Invalid JSON data: root value must be an object.');
    }

    return $data;
}

function should_show_sample_buttons()
{
    if (PHP_SAPI === 'cli') {
        return false;
    }

    $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    if ($requestMethod !== 'GET') {
        return false;
    }

    $rawJson = file_get_contents('php://input');
    $hasJsonBody = is_string($rawJson) && trim($rawJson) !== '';
    $hasQueryData = isset($_GET['data']) && is_scalar($_GET['data']) && trim((string) $_GET['data']) !== '';
    $hasSampleRequest = isset($_GET['sample']);
    $hasFieldQuery = false;

    foreach ($_GET as $key => $value) {
        if ($key !== 'data' && $key !== 'sample' && is_scalar($value)) {
            $hasFieldQuery = true;
            break;
        }
    }

    return !$hasJsonBody && !$hasQueryData && !$hasSampleRequest && !$hasFieldQuery;
}

function render_sample_buttons()
{
    header('Content-Type: text/html; charset=UTF-8');
?>
    <!doctype html>
    <html lang="id">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Generate CPP PDF</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 40px;
                color: #1f2937;
            }

            .actions {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
            }

            button {
                border: 0;
                border-radius: 6px;
                padding: 12px 18px;
                font-size: 15px;
                font-weight: 700;
                color: #fff;
                background: #005691;
                cursor: pointer;
            }

            button.usd {
                background: #047857;
            }
        </style>
    </head>

    <body>
        <h1>Generate PDF CPP</h1>
        <div class="actions">
            <form method="get">
                <input type="hidden" name="sample" value="IDR">
                <button type="submit">Generate PDF IDR</button>
            </form>
            <form method="get">
                <input type="hidden" name="sample" value="USD">
                <button class="usd" type="submit">Generate PDF USD</button>
            </form>
        </div>
    </body>

    </html>
<?php
}

if (should_show_sample_buttons()) {
    render_sample_buttons();
    exit;
}

try {
    $data = data_from_request();
} catch (InvalidArgumentException $exception) {
    fail_response(400, $exception->getMessage());
} catch (RuntimeException $exception) {
    fail_response(500, $exception->getMessage());
}

try {
    $pdf = cpp_render_pdf($data);
} catch (InvalidArgumentException $exception) {
    fail_response(400, $exception->getMessage());
} catch (RuntimeException $exception) {
    fail_response(500, $exception->getMessage());
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . cpp_pdf_filename($data) . '.pdf"');
echo $pdf;
