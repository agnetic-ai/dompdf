<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// PHP 5.4 melempar warning fatal saat instantiate DateTime bila date.timezone
// belum di-set di php.ini. Set default aman kalau belum dikonfigurasi.
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Jakarta');
}

function cpp_template_files()
{
    return array(
        'USD' => __DIR__ . '/USD-New.html',
        'IDR' => __DIR__ . '/IDR-New.html',
    );
}

function cpp_required_fields()
{
    return array(
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
    );
}

function cpp_currency(array $data)
{
    $currency = strtoupper(trim((string) (isset($data['cpp_currency']) ? $data['cpp_currency'] : '')));

    $templateFiles = cpp_template_files();
    if (!array_key_exists($currency, $templateFiles)) {
        throw new InvalidArgumentException('Unsupported cpp_currency: ' . $currency . '. Gunakan USD atau IDR.');
    }

    return $currency;
}

function cpp_template_file(array $data)
{
    $templateFiles = cpp_template_files();
    return $templateFiles[cpp_currency($data)];
}

function cpp_validate_data(array $data)
{
    $missingFields = array();
    foreach (cpp_required_fields() as $field) {
        if (!array_key_exists($field, $data) || !is_scalar($data[$field]) || (string) $data[$field] === '') {
            $missingFields[] = $field;
        }
    }

    if ($missingFields !== array()) {
        throw new InvalidArgumentException('Missing required data fields: ' . implode(', ', $missingFields));
    }

    cpp_currency($data);
    cpp_validate_data_table($data);
}

function cpp_pdf_filename(array $data)
{
    $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) (isset($data['cpp_no_spaj']) ? $data['cpp_no_spaj'] : 'template-codex'));
    $trimmed = trim((string) $filename, '-');
    return $trimmed !== '' ? $trimmed : 'template-codex';
}

function cpp_html_value($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cpp_money_fields()
{
    return array(
        'cpp_premi',
        'cpp_up',
        'cpp_mti_total',
        'hasil_investasi',
        'nilai_polis',
        'total_claim',
    );
}

function cpp_required_template_fields($html)
{
    if (preg_match_all('/\{([A-Za-z0-9_]+)\}/', $html, $matches) < 1) {
        return array();
    }

    return array_values(array_unique($matches[1]));
}

function cpp_data_table_columns()
{
    return array(
        'periode',
        'bulan',
        'jumlah_hari',
        'mti_jumlah_hari',
        'saldo_investasi',
        'manfaat_investasi',
        'klaim',
    );
}

function cpp_data_table_money_columns()
{
    return array(
        'mti_jumlah_hari',
        'saldo_investasi',
        'manfaat_investasi',
        'klaim',
    );
}

function cpp_parse_money_value($value)
{
    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    $normalized = preg_replace('/[^\d,.\-]/', '', trim((string) $value));
    if ($normalized === null || $normalized === '') {
        return null;
    }

    $isNegative = strpos($normalized, '-') !== false;
    $normalized = str_replace('-', '', $normalized);

    if ($normalized === '' || preg_match('/\d/', $normalized) !== 1) {
        return null;
    }

    $lastComma = strrpos($normalized, ',');
    $lastDot = strrpos($normalized, '.');

    if ($lastComma !== false && $lastDot !== false) {
        $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
        $decimalPosition = strrpos($normalized, $decimalSeparator);
        $integerPart = substr($normalized, 0, $decimalPosition);
        $fractionalPart = substr($normalized, $decimalPosition + 1);
        $integerDigits = preg_replace('/\D/', '', $integerPart);
        $fractionalDigits = preg_replace('/\D/', '', $fractionalPart);

        if ($integerDigits === null || $integerDigits === '') {
            return null;
        }

        $normalized = $integerDigits . ($fractionalDigits !== '' ? '.' . $fractionalDigits : '');
    } else {
        $separator = $lastComma !== false ? ',' : ($lastDot !== false ? '.' : null);

        if ($separator !== null) {
            $parts = explode($separator, $normalized);
            $lastPart = end($parts);
            $separatorCount = count($parts) - 1;
            $hasDecimalPart = strlen($lastPart) !== 3 || $separatorCount === 1 && strlen($lastPart) <= 2;

            if ($hasDecimalPart) {
                $fractionalPart = array_pop($parts);
                $integerDigits = preg_replace('/\D/', '', implode('', $parts));
                $fractionalDigits = preg_replace('/\D/', '', $fractionalPart);

                if ($integerDigits === null || $integerDigits === '') {
                    return null;
                }

                $normalized = $integerDigits . ($fractionalDigits !== '' ? '.' . $fractionalDigits : '');
            } else {
                $normalized = preg_replace('/\D/', '', $normalized);
            }
        } else {
            $normalized = preg_replace('/\D/', '', $normalized);
        }
    }

    if ($normalized === null || $normalized === '' || !is_numeric($normalized)) {
        return null;
    }

    $number = (float) $normalized;
    return $isNegative ? -$number : $number;
}

function cpp_parse_percent_value($value)
{
    if (is_int($value) || is_float($value)) {
        $number = (float) $value;
    } else {
        $normalized = preg_replace('/[^\d,.\-]/', '', trim((string) $value));
        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (strpos($normalized, ',') !== false && strpos($normalized, '.') !== false) {
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

function cpp_parse_int_value($value)
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

function cpp_indonesian_months()
{
    return array(
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
    );
}

function cpp_parse_indonesian_date($value)
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

    return new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));
}

function cpp_format_indonesian_date(DateTime $date)
{
    $months = array(
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
    );

    return (int) $date->format('j') . ' ' . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
}

function cpp_enrich_derived_data(array $data)
{
    $currency = cpp_currency($data);
    $startDate = cpp_parse_indonesian_date($data['cpp_tgl_asu']);
    $premium = cpp_parse_money_value($data['cpp_premi']);
    $sumInsured = cpp_parse_money_value($data['cpp_up']);
    $investmentRate = cpp_parse_percent_value($data['cpp_mti_pa']);
    $totalDaysInput = isset($data['total_days'])
        ? $data['total_days']
        : (isset($data['cpp_mti_hari']) ? $data['cpp_mti_hari'] : null);
    $totalDays = cpp_parse_int_value($totalDaysInput);

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

    $date129 = clone $startDate;
    $date129->modify('+129 days');
    $date130 = clone $startDate;
    $date130->modify('+130 days');

    $data['total_days'] = $totalDays;
    $data['claim_elapsed_days'] = $claimElapsedDays;
    $data['cpp_tgl_129_asu'] = cpp_format_indonesian_date($date129);
    $data['cpp_tgl_130_asu'] = cpp_format_indonesian_date($date130);
    $data['hasil_investasi'] = $currency === 'USD' ? cpp_format_usd_currency($investmentResult) : $investmentResult;
    $data['nilai_polis'] = $currency === 'USD' ? cpp_format_usd_currency($policyValue) : $policyValue;
    $data['total_claim'] = $currency === 'USD' ? cpp_format_usd_currency($totalClaim) : $totalClaim;

    if (!array_key_exists('cpp_mti_total', $data) || !is_scalar($data['cpp_mti_total']) || (string) $data['cpp_mti_total'] === '') {
        $investmentTotal = $premium * $investmentRate;
        $data['cpp_mti_total'] = $currency === 'USD' ? cpp_format_usd_currency($investmentTotal) : $investmentTotal;
    }

    return $data;
}

function cpp_format_idr_currency($value)
{
    $number = cpp_parse_money_value($value);
    if ($number === null) {
        return (string) $value;
    }

    return 'Rp. ' . number_format((float) $number, 2, ',', '.');
}

function cpp_format_usd_currency($value)
{
    $number = cpp_parse_money_value($value);
    if ($number === null) {
        return (string) $value;
    }

    return 'USD ' . number_format((float) $number, 2, '.', ',');
}

function cpp_format_idr_amount($value)
{
    $number = cpp_parse_money_value($value);
    if ($number === null) {
        return (string) $value;
    }

    return number_format((float) $number, 2, ',', '.');
}

function cpp_format_usd_amount($value)
{
    $number = cpp_parse_money_value($value);
    if ($number === null) {
        return (string) $value;
    }

    return number_format((float) $number, 2, '.', ',');
}

function cpp_display_value($key, $value, $currency)
{
    if ($key === 'cpp_mti_total') {
        if ($currency === 'IDR') {
            return cpp_format_idr_amount($value);
        }

        if ($currency === 'USD') {
            return cpp_format_usd_amount($value);
        }
    }

    if ($currency === 'IDR' && in_array($key, cpp_money_fields(), true)) {
        return cpp_format_idr_currency($value);
    }

    return (string) $value;
}

function cpp_data_table_display_value($column, $value, $currency)
{
    if (!in_array($column, cpp_data_table_money_columns(), true)) {
        return (string) $value;
    }

    if ($currency === 'IDR') {
        return cpp_format_idr_amount($value);
    }

    if ($currency === 'USD') {
        return cpp_format_usd_amount($value);
    }

    return (string) $value;
}

function cpp_validate_data_table(array $data)
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

        $missingColumns = array();
        foreach (cpp_data_table_columns() as $column) {
            if (!array_key_exists($column, $row) || (!is_scalar($row[$column]) && $row[$column] !== null)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns !== array()) {
            throw new InvalidArgumentException(
                'Invalid data_tabel row #' . ($index + 1) . ': missing/invalid columns: ' . implode(', ', $missingColumns)
            );
        }
    }
}

function cpp_validate_template_data(array $data, $html)
{
    $missingFields = array();
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

    if ($missingFields !== array()) {
        throw new InvalidArgumentException('Missing required template data fields: ' . implode(', ', $missingFields));
    }
}

// function cpp_render_data_table_rows(array $rows, $currency)
// {
//     $htmlRows = array();

//     foreach ($rows as $row) {
//         $cells = array();
//         foreach (cpp_data_table_columns() as $column) {
//             $cells[] = '<td><span class="highlight-yellow">' . cpp_html_value(cpp_data_table_display_value($column, $row[$column], $currency)) . '</span></td>';
//         }

//         $htmlRows[] = '<tr>' . implode('', $cells) . '</tr>';
//     }

//     return implode("\n", $htmlRows);
// }

function cpp_data_table_column_styles()
{
    // Class + width tiap kolom, HARUS identik dengan <colgroup> di template.
    // DomPDF 0.8.3 menentukan lebar kolom dari sel baris pertama <tbody>,
    // jadi setiap <td> hasil generate WAJIB membawa width & class sendiri.
    return array(
        'periode'           => array('class' => 'benefit-col-period',           'width' => '5%'),
        'bulan'             => array('class' => 'benefit-col-month',            'width' => '24%'),
        'jumlah_hari'       => array('class' => 'benefit-col-days',             'width' => '5%'),
        'mti_jumlah_hari'   => array('class' => 'benefit-col-daily-investment', 'width' => '16.5%'),
        'saldo_investasi'   => array('class' => 'benefit-col-balance',          'width' => '16.5%'),
        'manfaat_investasi' => array('class' => 'benefit-col-investment',       'width' => '16.5%'),
        'klaim'             => array('class' => 'benefit-col-claim',            'width' => '16.5%'),
    );
}

function cpp_render_data_table_rows(array $rows, $currency)
{
    $htmlRows = array();
    $columnStyles = cpp_data_table_column_styles();

    foreach ($rows as $row) {
        $cells = array();
        foreach (cpp_data_table_columns() as $column) {
            $style = isset($columnStyles[$column]) ? $columnStyles[$column] : array('class' => '', 'width' => '');
            $class = $style['class'];
            $width = $style['width'];

            // Kolom uang dapat class tambahan agar rata kanan.
            if (in_array($column, cpp_data_table_money_columns(), true)) {
                $class = trim($class . ' benefit-money-cell');
            }

            $attrs = '';
            if ($class !== '') {
                $attrs .= ' class="' . cpp_html_value($class) . '"';
            }
            if ($width !== '') {
                // width attribute + inline style: dua-duanya dibaca DomPDF 0.8.3.
                $attrs .= ' width="' . cpp_html_value($width) . '" style="width: ' . cpp_html_value($width) . '"';
            }

            $cells[] = '<td' . $attrs . '><span class="highlight-yellow">'
                . cpp_html_value(cpp_data_table_display_value($column, $row[$column], $currency))
                . '</span></td>';
        }

        $htmlRows[] = '<tr>' . implode('', $cells) . '</tr>';
    }

    return implode("
", $htmlRows);
}


function cpp_render_html(array $data)
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
    $replacements = array();
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

function cpp_render_pdf(array $data)
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

function cpp_usd_required_fields()
{
    return cpp_required_fields();
}

function cpp_usd_validate_data(array $data)
{
    cpp_validate_data($data);
}

function cpp_usd_pdf_filename(array $data)
{
    return cpp_pdf_filename($data);
}

function cpp_usd_html_value($value)
{
    return cpp_html_value($value);
}

function cpp_usd_render_pdf(array $data)
{
    return cpp_render_pdf($data);
}
