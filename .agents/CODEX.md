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

_Proyek ini menggunakan pola arsitektur **[Clean Architecture / Layered Architecture / Microservices]**._

- **Presentation Layer:** Menangani UI dan input pengguna (Controller, Router, Views).
- **Domain / Business Logic Layer:** Berisi _use cases_, aturan bisnis inti, dan entitas.
- **Data / Infrastructure Layer:** Menangani akses database, API eksternal, dan integrasi repositori.

### 2.2 Alur Data (Data Flow)

1. Request masuk melalui API Gateway / Router.
2. Router meneruskan ke Controller untuk validasi input awal.
3. Controller memanggil Service/Use Case yang berisi logika bisnis.
4. Service berinteraksi dengan Repository untuk mengambil atau menyimpan data ke database.

---

## 3. Struktur Direktori (Directory Structure)

```text
├── src/
│   ├── config/          # Konfigurasi aplikasi dan variabel lingkungan (env)
│   ├── controllers/     # Menangani HTTP request & response
│   ├── domain/          # Entitas bisnis inti dan aturan domain
│   ├── middlewares/     # Middleware untuk autentikasi, logging, dll.
│   ├── models/          # Skema database / ORM models
│   ├── repositories/    # Abstraksi akses database (Query, CRUD)
│   ├── services/        # Logika bisnis inti (Use Cases)
│   ├── utils/           # Fungsi pembantu (helpers) yang reusable
│   └── app.js           # Entry point aplikasi
├── docker/              # Konfigurasi lingkungan Docker (Development/Production)
├── tests/               # Unit testing dan Integration testing
├── README.md            # Panduan instalasi dan menjalankan proyek
└── CODEX.md             # Dokumen standar ini
```
````
