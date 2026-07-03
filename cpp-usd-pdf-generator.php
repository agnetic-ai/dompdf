<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

const CPP_TEMPLATE_FILES = [
    'USD' => __DIR__ . '/CPP-USD-CODEX.html',
    'IDR' => __DIR__ . '/CPP-IDR-New.html',
];

function cpp_required_fields(): array
{
    return [
        'cpp_nama_pp',
        'cpp_nama_tt',
        'cpp_tgl_asu',
        'cpp_dbo',
        'cpp_age',
        'cpp_premi',
        'cpp_periode',
        'cpp_akhir_asu',
        'cpp_mti_pa',
        'cpp_up',
        'cpp_currency',
    ];
}

function cpp_currency(array $data): string
{
    $currency = strtoupper(trim((string) ($data['cpp_currency'] ?? '')));

    if (!array_key_exists($currency, CPP_TEMPLATE_FILES)) {
        throw new InvalidArgumentException('Unsupported cpp_currency: ' . $currency . '. Gunakan USD atau IDR.');
    }

    return $currency;
}

function cpp_template_file(array $data): string
{
    return CPP_TEMPLATE_FILES[cpp_currency($data)];
}

function cpp_validate_data(array $data): void
{
    $missingFields = [];
    foreach (cpp_required_fields() as $field) {
        if (!array_key_exists($field, $data) || !is_scalar($data[$field]) || (string) $data[$field] === '') {
            $missingFields[] = $field;
        }
    }

    if ($missingFields !== []) {
        throw new InvalidArgumentException('Missing required data fields: ' . implode(', ', $missingFields));
    }

    cpp_currency($data);
    cpp_validate_data_table($data);
}

function cpp_pdf_filename(array $data): string
{
    $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($data['cpp_no_spaj'] ?? 'template-codex'));
    return trim((string) $filename, '-') ?: 'template-codex';
}

function cpp_html_value($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cpp_money_fields(): array
{
    return [
        'cpp_premi',
        'cpp_up',
        'cpp_mti_total',
        'hasil_investasi',
        'nilai_polis',
        'total_claim',
    ];
}

function cpp_required_template_fields(string $html): array
{
    if (preg_match_all('/\{([A-Za-z0-9_]+)\}/', $html, $matches) < 1) {
        return [];
    }

    return array_values(array_unique($matches[1]));
}

function cpp_data_table_columns(): array
{
    return [
        'periode',
        'bulan',
        'jumlah_hari',
        'mti_jumlah_hari',
        'saldo_investasi',
        'manfaat_investasi',
        'klaim',
    ];
}

function cpp_data_table_money_columns(): array
{
    return [
        'mti_jumlah_hari',
        'saldo_investasi',
        'manfaat_investasi',
        'klaim',
    ];
}

function cpp_parse_money_value($value): ?float
{
    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    $normalized = preg_replace('/[^\d,.\-]/', '', trim((string) $value));
    if ($normalized === null || $normalized === '') {
        return null;
    }

    $lastComma = strrpos($normalized, ',');
    $lastDot = strrpos($normalized, '.');

    if ($lastComma !== false && $lastDot !== false) {
        if ($lastComma > $lastDot) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }
    } elseif ($lastComma !== false) {
        $normalized = str_replace(',', '.', $normalized);
    } elseif (substr_count($normalized, '.') > 1 || preg_match('/\.\d{3}$/', $normalized) === 1) {
        $normalized = str_replace('.', '', $normalized);
    }

    return is_numeric($normalized) ? (float) $normalized : null;
}

function cpp_parse_percent_value($value): ?float
{
    if (is_int($value) || is_float($value)) {
        $number = (float) $value;
    } else {
        $normalized = preg_replace('/[^\d,.\-]/', '', trim((string) $value));
        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        $number = (float) $normalized;
    }

    return $number > 1 ? $number / 100 : $number;
}

function cpp_parse_int_value($value): ?int
{
    if (is_int($value)) {
        return $value;
    }

    if (is_float($value)) {
        return (int) $value;
    }

    $normalized = preg_replace('/[^\d\-]/', '', trim((string) $value));
    if ($normalized === null || $normalized === '' || !is_numeric($normalized)) {
        return null;
    }

    return (int) $normalized;
}

function cpp_indonesian_months(): array
{
    return [
        'januari' => 1,
        'jan' => 1,
        'februari' => 2,
        'feb' => 2,
        'maret' => 3,
        'mar' => 3,
        'april' => 4,
        'apr' => 4,
        'mei' => 5,
        'juni' => 6,
        'jun' => 6,
        'juli' => 7,
        'jul' => 7,
        'agustus' => 8,
        'agu' => 8,
        'ags' => 8,
        'aug' => 8,
        'september' => 9,
        'sep' => 9,
        'oktober' => 10,
        'okt' => 10,
        'oct' => 10,
        'november' => 11,
        'nov' => 11,
        'desember' => 12,
        'des' => 12,
        'dec' => 12,
    ];
}

function cpp_parse_indonesian_date($value): DateTimeImmutable
{
    $date = trim((string) $value);
    if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/', $date, $matches) !== 1) {
        throw new InvalidArgumentException('Invalid cpp_tgl_asu format: gunakan format seperti "23 Juni 2026".');
    }

    $monthKey = strtolower($matches[2]);
    $months = cpp_indonesian_months();
    if (!array_key_exists($monthKey, $months)) {
        throw new InvalidArgumentException('Invalid cpp_tgl_asu month: ' . $matches[2]);
    }

    $day = (int) $matches[1];
    $month = $months[$monthKey];
    $year = (int) $matches[3];

    if (!checkdate($month, $day, $year)) {
        throw new InvalidArgumentException('Invalid cpp_tgl_asu date: ' . $date);
    }

    return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
}

function cpp_format_indonesian_date(DateTimeImmutable $date): string
{
    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    return (int) $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
}

function cpp_enrich_derived_data(array $data): array
{
    $startDate = cpp_parse_indonesian_date($data['cpp_tgl_asu']);
    $premium = cpp_parse_money_value($data['cpp_premi']);
    $sumInsured = cpp_parse_money_value($data['cpp_up']);
    $investmentRate = cpp_parse_percent_value($data['cpp_mti_pa']);
    $totalDays = cpp_parse_int_value($data['total_days'] ?? ($data['cpp_mti_hari'] ?? null));

    if ($premium === null) {
        throw new InvalidArgumentException('Invalid cpp_premi: value must contain a number.');
    }

    if ($sumInsured === null) {
        throw new InvalidArgumentException('Invalid cpp_up: value must contain a number.');
    }

    if ($investmentRate === null) {
        throw new InvalidArgumentException('Invalid cpp_mti_pa: value must contain a percentage number.');
    }

    if ($totalDays === null || $totalDays <= 0) {
        throw new InvalidArgumentException('Invalid total_days: value must be a positive number.');
    }

    $claimElapsedDays = 130;
    $investmentResult = $premium * $investmentRate * ($claimElapsedDays / $totalDays);
    $policyValue = $premium + $investmentResult;
    $totalClaim = $sumInsured + $policyValue;

    $data['total_days'] = $totalDays;
    $data['claim_elapsed_days'] = $claimElapsedDays;
    $data['cpp_tgl_129_asu'] = cpp_format_indonesian_date($startDate->modify('+129 days'));
    $data['cpp_tgl_130_asu'] = cpp_format_indonesian_date($startDate->modify('+130 days'));
    $data['hasil_investasi'] = $investmentResult;
    $data['nilai_polis'] = $policyValue;
    $data['total_claim'] = $totalClaim;

    if (!array_key_exists('cpp_mti_total', $data) || !is_scalar($data['cpp_mti_total']) || (string) $data['cpp_mti_total'] === '') {
        $data['cpp_mti_total'] = $premium * $investmentRate;
    }

    return $data;
}

function cpp_format_idr_currency($value): string
{
    $number = cpp_parse_money_value($value);
    if ($number === null) {
        return (string) $value;
    }

    return 'Rp. ' . number_format((float) round($number), 0, ',', '.');
}

function cpp_display_value(string $key, $value, string $currency): string
{
    if ($currency === 'IDR' && in_array($key, cpp_money_fields(), true)) {
        return cpp_format_idr_currency($value);
    }

    return (string) $value;
}

function cpp_data_table_display_value(string $column, $value, string $currency): string
{
    if ($currency === 'IDR' && in_array($column, cpp_data_table_money_columns(), true)) {
        return cpp_format_idr_currency($value);
    }

    return (string) $value;
}

function cpp_validate_data_table(array $data): void
{
    if (!array_key_exists('data_tabel', $data)) {
        return;
    }

    if (!is_array($data['data_tabel'])) {
        throw new InvalidArgumentException('Invalid data_tabel: value must be an array.');
    }

    foreach ($data['data_tabel'] as $index => $row) {
        if (!is_array($row)) {
            throw new InvalidArgumentException('Invalid data_tabel row #' . ($index + 1) . ': value must be an object.');
        }

        $missingColumns = [];
        foreach (cpp_data_table_columns() as $column) {
            if (!array_key_exists($column, $row) || (!is_scalar($row[$column]) && $row[$column] !== null)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns !== []) {
            throw new InvalidArgumentException(
                'Invalid data_tabel row #' . ($index + 1) . ': missing/invalid columns: ' . implode(', ', $missingColumns)
            );
        }
    }
}

function cpp_validate_template_data(array $data, string $html): void
{
    $missingFields = [];
    foreach (cpp_required_template_fields($html) as $field) {
        if ($field === 'data_tabel') {
            if (!array_key_exists('data_tabel', $data) || !is_array($data['data_tabel'])) {
                $missingFields[] = $field;
            }
            continue;
        }

        if (!array_key_exists($field, $data) || (!is_scalar($data[$field]) && $data[$field] !== null) || (string) $data[$field] === '') {
            $missingFields[] = $field;
        }
    }

    if ($missingFields !== []) {
        throw new InvalidArgumentException('Missing required template data fields: ' . implode(', ', $missingFields));
    }
}

function cpp_render_data_table_rows(array $rows, string $currency): string
{
    $htmlRows = [];

    foreach ($rows as $row) {
        $cells = [];
        foreach (cpp_data_table_columns() as $column) {
            $cells[] = '<td><span class="highlight-yellow">' . cpp_html_value(cpp_data_table_display_value($column, $row[$column], $currency)) . '</span></td>';
        }

        $htmlRows[] = '<tr>' . implode('', $cells) . '</tr>';
    }

    return implode("\n", $htmlRows);
}

function cpp_render_html(array $data): string
{
    cpp_validate_data($data);

    $templateFile = cpp_template_file($data);
    $html = file_get_contents($templateFile);
    if ($html === false) {
        throw new RuntimeException('Template not found: ' . basename($templateFile));
    }

    $data = cpp_enrich_derived_data($data);
    cpp_validate_template_data($data, $html);
    cpp_validate_data_table($data);

    $currency = cpp_currency($data);
    $replacements = [];
    foreach ($data as $key => $value) {
        if (is_scalar($value) || $value === null) {
            $replacements['{' . $key . '}'] = cpp_html_value(cpp_display_value((string) $key, $value, $currency));
        }
    }

    if (array_key_exists('data_tabel', $data) && is_array($data['data_tabel'])) {
        $replacements['{data_tabel}'] = cpp_render_data_table_rows($data['data_tabel'], $currency);
    }

    $html = strtr($html, $replacements);

    if (preg_match('/\{[A-Za-z0-9_]+\}/', $html, $matches) === 1) {
        throw new RuntimeException('Unresolved template placeholder: ' . $matches[0]);
    }

    return $html;
}

function cpp_render_pdf(array $data): string
{
    $html = cpp_render_html($data);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultMediaType', 'print');
    $options->set('defaultFont', 'Calibri');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}

function cpp_usd_required_fields(): array
{
    return cpp_required_fields();
}

function cpp_usd_validate_data(array $data): void
{
    cpp_validate_data($data);
}

function cpp_usd_pdf_filename(array $data): string
{
    return cpp_pdf_filename($data);
}

function cpp_usd_html_value($value): string
{
    return cpp_html_value($value);
}

function cpp_usd_render_pdf(array $data): string
{
    return cpp_render_pdf($data);
}
