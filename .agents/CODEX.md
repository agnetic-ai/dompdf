````markdown
# CODEX: Panduan Standar Pengembangan & Arsitektur Proyek

Dokumen ini berfungsi sebagai sumber kebenaran tunggal (_Single Source of Truth_) mengenai arsitektur, standar kode, alur kerja, dan keputusan teknis yang diadopsi dalam proyek ini. Semua kontributor wajib memahami dan mengikuti pedoman ini.

---

## 1. Ringkasan Proyek & Stack Teknologi

### 1.1 Informasi Umum

- **Nama Proyek:** dompfg
- **Deskripsi Singkat:** Generate PDF berdasarkan type USD atau IDR
- **Pemilik Proyek / Tim:** [zabuton-ai / IT]

### 1.2 Stack Teknologi Utama

- **Frontend:** [laravel, TailwindCSS]

---

## 2. Arsitektur Sistem & Pola Desain

### 2.1 Pola Arsitektur (Architecture Pattern)

_Proyek ini menggunakan pola arsitektur **monolith**._

### 2.2 Alur Data (Data Flow)

1. Request masuk melalui index.php
2. Router dari index.php di teruskan ke cpp-pdf-generator.php
3. Lalu cpp-pdf-generator.php memvalidari currency dari parameter USD/IDR
4. CPP-IDR-New.html berfungsi sebagai template IDR
5. CPP-USD-New.html berfungsi sebagai template USD

---

## 3. Struktur Direktori (Directory Structure)

```text
├── .agents/
│   └── CODEX.md           # Panduan untuk CODEX
├── index.php              # Index awal
├── cpp-pdf-generator      # pdf generator, validasi parametar, parsing parameter
├── CPP-IDR-New.html       # template IDR
├── CPP-USD-New.html       # template USD
├── SPAJ_JSON_IDR_20260623033241.json       # Contoh parameter JSON
├── README.md            # Panduan instalasi dan menjalankan proyek
└── CODEX.md             # Dokumen standar ini
```
````
