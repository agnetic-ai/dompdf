<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RiplayPersonal
 *
 * Generate PDF RIPLAY Personal (Capital Proteksi Plus) IDR / USD via DomPDF 0.8.3.
 * Engine ada di helper cpp_pdf. Controller tipis: baca request -> data array ->
 * cpp_render_pdf() -> stream PDF ke browser.
 *
 * Route (default CI):
 *   GET  /riplaypersonal                     -> halaman tombol Generate PDF
 *   GET  /riplaypersonal/generate?sample=IDR -> generate dari sample JSON IDR
 *   GET  /riplaypersonal/generate?sample=USD -> generate dari sample JSON USD
 *   POST /riplaypersonal/generate  (body: JSON data cpp_*) -> generate dari payload
 */
class RiplayPersonal extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('cpp_pdf');
    }

    /**
     * Halaman tombol Generate PDF (IDR / USD).
     */
    public function index()
    {
        $html = '<!doctype html>'
            . '<html lang="id"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Generate RIPLAY Personal PDF</title>'
            . '<style>'
            . 'body{font-family:Arial,sans-serif;margin:40px;color:#1f2937}'
            . '.actions{display:flex;gap:12px;flex-wrap:wrap}'
            . 'button{border:0;border-radius:6px;padding:12px 18px;font-size:15px;'
            . 'font-weight:700;color:#fff;background:#005691;cursor:pointer}'
            . 'button.usd{background:#047857}'
            . '</style></head><body>'
            . '<h1>Generate PDF RIPLAY Personal</h1>'
            . '<div class="actions">'
            . '<form method="get" action="' . site_url('riplaypersonal/generate') . '">'
            . '<input type="hidden" name="sample" value="IDR">'
            . '<button type="submit">Generate PDF IDR</button></form>'
            . '<form method="get" action="' . site_url('riplaypersonal/generate') . '">'
            . '<input type="hidden" name="sample" value="USD">'
            . '<button class="usd" type="submit">Generate PDF USD</button></form>'
            . '</div></body></html>';

        $this->output
            ->set_content_type('text/html', 'UTF-8')
            ->set_output($html);
    }

    /**
     * Generate & stream PDF.
     */
    public function generate()
    {
        try {
            $data = $this->resolve_request_data();
        } catch (InvalidArgumentException $e) {
            return $this->fail(400, $e->getMessage());
        } catch (RuntimeException $e) {
            return $this->fail(500, $e->getMessage());
        }

        try {
            $pdf = cpp_render_pdf($data);
        } catch (InvalidArgumentException $e) {
            return $this->fail(400, $e->getMessage());
        } catch (RuntimeException $e) {
            return $this->fail(500, $e->getMessage());
        } catch (Exception $e) {
            return $this->fail(500, 'Gagal generate PDF: ' . $e->getMessage());
        }

        $filename = cpp_pdf_filename($data) . '.pdf';

        $this->output
            ->set_content_type('application/pdf')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('Content-Length: ' . strlen($pdf))
            ->set_output($pdf);
    }

    /**
     * Tentukan sumber data: query ?sample=IDR|USD, body JSON, atau query ?data={...}.
     */
    private function resolve_request_data()
    {
        $sample = $this->input->get('sample');
        if (is_string($sample) && trim($sample) !== '') {
            return $this->data_from_sample($sample);
        }

        $rawJson = $this->input->raw_input_stream;
        if (is_string($rawJson) && trim($rawJson) !== '') {
            return $this->data_from_json($rawJson);
        }

        $queryData = $this->input->get('data');
        if (is_string($queryData) && trim($queryData) !== '') {
            return $this->data_from_json($queryData);
        }

        throw new InvalidArgumentException(
            'Request data kosong. Kirim JSON body, query ?data={...}, atau ?sample=IDR|USD.'
        );
    }

    /**
     * Baca sample JSON dari template/riplay-personal/sample-{IDR|USD}.json.
     */
    private function data_from_sample($sample)
    {
        $sample = strtoupper(trim($sample));
        $files = array(
            'IDR' => cpp_template_dir() . '/sample-IDR.json',
            'USD' => cpp_template_dir() . '/sample-USD.json',
        );

        if (!array_key_exists($sample, $files)) {
            throw new InvalidArgumentException('Sample JSON tidak dikenal. Gunakan IDR atau USD.');
        }

        $json = @file_get_contents($files[$sample]);
        if ($json === false) {
            throw new RuntimeException('Sample JSON tidak ditemukan: ' . basename($files[$sample]));
        }

        return $this->data_from_json($json);
    }

    /**
     * Decode JSON string -> array. PHP 5.4 safe (tanpa JSON_THROW_ON_ERROR).
     */
    private function data_from_json($json)
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

    /**
     * Kirim response error plain-text.
     */
    private function fail($statusCode, $message)
    {
        $this->output
            ->set_status_header($statusCode)
            ->set_content_type('text/plain', 'UTF-8')
            ->set_output($message);
    }
}
