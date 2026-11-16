# Orang 2 — Catatan Perawatan & Kabar Keluarga

Pemilik kode domain `CareUpdates`. Tabel: `daily_notes, family_updates`.

- Controller: `app/Http/Controllers/CareUpdates/`.
- Form Request: `app/Http/Requests/CareUpdates/`.
- Model: `app/Models/` sesuai tabel yang dimiliki.
- View: `resources/views/modules/care-updates/`.
- Route: `routes/modules/care-updates.php` (sudah berada di grup auth; tambahkan `role:admin`, `role:caregiver` atau `role:family` per endpoint).
- Test: `tests/Feature/Modules/CareUpdates/`.
- Migration tambahan: `supabase/migrations/`, jangan ubah baseline atau data bersama secara manual.
- Branch contoh: `feat/care-updates/nama-fitur`, PR ke branch integrasi tim; review minimal satu anggota lain.

Lihat `docs/TEAM.md` untuk acceptance criteria, dependensi, dan kontrak bersama. Folder ini adalah ruang pengembangan; CRUD modul belum diimplementasikan pada tahap autentikasi.
