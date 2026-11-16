# Orang 3 — Portal Keluarga & Manajemen Kunjungan

Pemilik kode domain `FamilyVisits`. Tabel: `visit_requests`.

- Controller: `app/Http/Controllers/FamilyVisits/`.
- Form Request: `app/Http/Requests/FamilyVisits/`.
- Model: `app/Models/` sesuai tabel yang dimiliki.
- View: `resources/views/modules/family-visits/`.
- Route: `routes/modules/family-visits.php` (sudah berada di grup auth; tambahkan `role:admin`, `role:caregiver` atau `role:family` per endpoint).
- Test: `tests/Feature/Modules/FamilyVisits/`.
- Migration tambahan: `supabase/migrations/`, jangan ubah baseline atau data bersama secara manual.
- Branch contoh: `feat/family-visits/nama-fitur`, PR ke branch integrasi tim; review minimal satu anggota lain.

Lihat `docs/TEAM.md` untuk acceptance criteria, dependensi, dan kontrak bersama. Folder ini adalah ruang pengembangan; CRUD modul belum diimplementasikan pada tahap autentikasi.
