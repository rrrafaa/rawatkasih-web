# Orang 1 — Perawatan & Manajemen Tugas

Pemilik kode domain `CareTasks`. Tabel: `care_task_templates, care_task_occurrences`.

- Controller: `app/Http/Controllers/CareTasks/`.
- Form Request: `app/Http/Requests/CareTasks/`.
- Model: `app/Models/` sesuai tabel yang dimiliki.
- View: `resources/views/modules/care-tasks/`.
- Route: `routes/modules/care-tasks.php` (sudah berada di grup auth; tambahkan `role:admin`, `role:caregiver` atau `role:family` per endpoint).
- Test: `tests/Feature/Modules/CareTasks/`.
- Migration tambahan: `supabase/migrations/`, jangan ubah baseline atau data bersama secara manual.
- Branch contoh: `feat/care-tasks/nama-fitur`, PR ke branch integrasi tim; review minimal satu anggota lain.

Lihat `docs/TEAM.md` untuk acceptance criteria, dependensi, dan kontrak bersama. Folder ini adalah ruang pengembangan; CRUD modul belum diimplementasikan pada tahap autentikasi.
