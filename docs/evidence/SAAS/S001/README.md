# S001 — Kontrak username dan tenant nonaktif

Tanggal: 17 November 2026  
Sumber keputusan: user mengaktifkan Goal “kerjakan semua todo SAAS-TODO.md”.

## Keputusan

1. Username tetap unik global pada v1 SaaS. Client baru memakai username berbeda, misalnya `admin.sumberhp`; login tidak menambah kode toko atau subdomain.
2. Tenant nonaktif tidak dapat login atau memakai area internal. Tracking publik untuk TTS lama tetap tersedia dan tetap mengikuti pembatasan data tracking existing.

## Batas

- Tidak menambah panel platform, billing, paket, trial, subdomain, custom domain, membership multi-toko, plugin, API, queue, atau dependency multi-tenancy.
- Keputusan ini tidak melepas gate backup/restore, QA independen, atau verifikasi data sebelum migrasi produksi.
