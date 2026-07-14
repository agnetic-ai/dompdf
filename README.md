# RIPLAY Personal — Generator PDF (CodeIgniter 3.1.8)

Generate PDF RIPLAY Personal (Capital Proteksi Plus) IDR / USD dari data JSON,
render pakai DomPDF 0.8.3. Kompatibel **PHP 5.4+**.

## Struktur

```
application/
  controllers/RiplayPersonal.php   Controller tipis: request -> data -> stream PDF
  helpers/cpp_pdf_helper.php        Engine: validasi, format duit/tanggal, render DomPDF
template/riplay-personal/
  IDR-New.html                      Template PDF IDR
  USD-New.html                      Template PDF USD
  sample-IDR.json                   Data contoh IDR
  sample-USD.json                   Data contoh USD
composer.json                       Dependency DomPDF 0.8.3 (pin platform PHP 5.4)
vendor/                             Hasil composer install (di-gitignore)
```

## Integrasi ke app CI 3.1.8

1. Copy `application/controllers/RiplayPersonal.php` ke folder controllers app kamu.
2. Copy `application/helpers/cpp_pdf_helper.php` ke folder helpers app kamu.
3. Copy folder `template/riplay-personal/` ke root project (sejajar `application/`).
4. Copy `vendor/` (DomPDF) ke root project, atau jalankan `composer install` (lihat bawah).

Helper mencari `vendor/` dan `template/` relatif dari root project
(dua level di atas `application/helpers/`). Kalau struktur app kamu beda,
sesuaikan `cpp_project_root()` dan `cpp_vendor_autoload_path()` di helper.

## Route

- `GET  /riplaypersonal` — halaman tombol Generate PDF (IDR / USD)
- `GET  /riplaypersonal/generate?sample=IDR` — generate dari sample IDR
- `GET  /riplaypersonal/generate?sample=USD` — generate dari sample USD
- `POST /riplaypersonal/generate` — body JSON data `cpp_*` -> generate dari payload
- `GET  /riplaypersonal/generate?data={...}` — data JSON via query string

## Format data JSON

Field wajib: `cpp_nama_pp`, `cpp_nama_tt`, `cpp_tgl_asu` (format `"23 Juni 2026"`),
`cpp_dbo`, `cpp_age`, `cpp_premi`, `cpp_periode`, `cpp_akhir_asu`, `cpp_mti_pa`,
`cpp_up`, `cpp_currency` (`IDR` atau `USD`). Lihat `sample-IDR.json` / `sample-USD.json`.

Tabel manfaat opsional lewat key `data_tabel` (array of objek dengan kolom
`periode`, `bulan`, `jumlah_hari`, `mti_jumlah_hari`, `saldo_investasi`,
`manfaat_investasi`, `klaim`).

## Install dependency (composer)

DomPDF 0.8.3 adalah versi terakhir yang support PHP 5.4. **WAJIB pakai Composer 2.2 LTS**
— Composer 2.3+ menolak autoload di PHP < 7.2.5.

```bash
# download composer 2.2 (sekali saja)
curl -sS https://getcomposer.org/download/2.2.22/composer.phar -o composer22.phar

# install (jalankan dengan PHP target, mis. 5.4/5.6)
php composer22.phar install
```

`composer.json` sudah di-pin ke `platform.php = 5.4.45` dan
`policy.advisories.block = false` (DomPDF 0.8.3 kena advisory karena versi lama —
ini disengaja demi kompatibilitas PHP 5.4).

## Ekstensi PHP yang dibutuhkan

`mbstring`, `dom` (libxml), `gd` (untuk render logo/gambar di PDF).

## Catatan teknis

- **Timezone**: helper set `Asia/Jakarta` otomatis kalau `date.timezone` kosong
  (PHP 5.4 fatal saat `new DateTime()` tanpa timezone).
- **Memory**: template USD ~140KB butuh ~200MB saat render. Helper auto-bump
  `memory_limit` ke 256M kalau di bawah itu.
- **Case-sensitive**: nama file template harus persis `IDR-New.html` / `USD-New.html`
  (Linux case-sensitive).
- **PHP 8**: DomPDF 0.8.3 TIDAK jalan di PHP 8 (error `DOMImplementation` null).
  Kalau server upgrade ke PHP 8, pakai DomPDF 3.x (lihat branch lain).
