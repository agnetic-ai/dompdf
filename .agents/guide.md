# Guide How to Parse DomPDF

- Objek Baru yang tidak ada di Parameter Json

```bash
    - cpp_tgl_129_asu
    - cpp_tgl_130_asu
    - hasil_investasi
    - nilai_polis
    - total_claim
```

- cpp_tgl_129_asu adalah perhitungan dari 129 hari dari objek parameter {cpp_tgl_asu}
- cpp_tgl_130_asu adalah perhitungan dari 130 hari dari objek parameter {cpp_tgl_asu}
- hasil_investasi adalah perhitungan total dari objek parameter {cpp_premi} x {cpp_mti_pa} x ({cpp_tgl_130_asu}-{cpp_tgl_asu} / {total_days})
- nilai_polis adalah perhitungan total dari objek parameter {cpp_premi} + {hasil_investasi}
- total_claim adalah perhitungan total dari objek parameter {cpp_up} + {nilai_polis}

# RULES

- Untuk format {cpp_tgl_130_asu}-{cpp_tgl_asu} yang sebelumnya 31 Oktober 2026 - 23 Juni 2026 menjadi = 130

# Buat file .env — GANTI nilai dalam kurung siku!

cat > /root/.hermes/.env <<'EOF'

# Token bot dari @BotFather

TELEGRAM_BOT_TOKEN=8473059510:AAEIRIlugYaCZIg-wIip2bMXQae-tVQ8PIg

# Telegram User ID kamu (angka, bukan username)

TELEGRAM_OWNER_ID=1605260429

# API Key 9Router (ambil dari dashboard 9Router → Settings → API Keys)

OPENAI_API_KEY=<api key dari dashboard 9router>

# URL 9Router — JANGAN DIGANTI, ini sudah benar untuk setup lokal

OPENAI_BASE_URL=http://localhost:20128/v1

# Model default yang dipakai Hermes

DEFAULT_MODEL=free_smart_fallback
EOF

# Amankan file .env agar hanya root yang bisa baca

chmod 600 /root/.hermes/.env
