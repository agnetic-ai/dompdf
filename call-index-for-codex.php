<?php

require_once __DIR__ . '/cpp-usd-pdf-generator.php';

try {
    $data = data_from_request();
    $pdf = cpp_render_pdf($data);
} catch (JsonException $exception) {
    fail_call_response(400, 'Invalid JSON data: ' . $exception->getMessage());
} catch (InvalidArgumentException $exception) {
    fail_call_response(400, $exception->getMessage());
} catch (RuntimeException $exception) {
    fail_call_response(500, $exception->getMessage());
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . cpp_pdf_filename($data) . '.pdf"');
echo $pdf;

function data_from_request(): array
{
    $rawJson = file_get_contents('php://input');
    if (is_string($rawJson) && trim($rawJson) !== '') {
        return data_from_json($rawJson);
    }

    if (isset($_GET['data']) && is_scalar($_GET['data']) && trim((string) $_GET['data']) !== '') {
        return data_from_json((string) $_GET['data']);
    }

    $data = [];
    foreach ($_GET as $key => $value) {
        if ($key !== 'data' && is_scalar($value)) {
            $data[$key] = $value;
        }
    }

    if ($data === []) {
        throw new InvalidArgumentException('Request data kosong. Kirim JSON body, query ?data={...}, atau field cpp_* via query string.');
    }

    return $data;
}

function data_from_json(string $json): array
{
    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($data)) {
        throw new InvalidArgumentException('Invalid JSON data: root value must be an object.');
    }

    return $data;
}

function fail_call_response(int $statusCode, string $message): never
{
    http_response_code($statusCode);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}
