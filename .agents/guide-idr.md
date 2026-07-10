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
