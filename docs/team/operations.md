# Orang 4 — Insiden & Monitoring Operasional

Pemilik kode domain `Operations`. Tabel: `incidents, audit_logs`.

- Controller: `app/Http/Controllers/Operations/`.
- Form Request: `app/Http/Requests/Operations/`.
- Model: `app/Models/` sesuai tabel yang dimiliki.
- View: `resources/views/modules/operations/`.
- Route: `routes/modules/operations.php` (sudah berada di grup auth; tambahkan `role:admin`, `role:caregiver` atau `role:family` per endpoint).
- Test: `tests/Feature/Modules/Operations/`.
- Migration tambahan: `supabase/migrations/`, jangan ubah baseline atau data bersama secara manual.
- Branch contoh: `feat/operations/nama-fitur`, PR ke branch integrasi tim; review minimal satu anggota lain.

Lihat `docs/TEAM.md` untuk acceptance criteria, dependensi, dan kontrak bersama. Folder ini adalah ruang pengembangan; CRUD modul belum diimplementasikan pada tahap autentikasi.
