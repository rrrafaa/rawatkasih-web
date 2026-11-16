# Audit prinsip PAWL12 — Authentication dan Keamanan REST API

**Referensi:** *PAWL12 — Modul Praktikum Authentication dan Keamanan REST API* (Laravel 11 + Sanctum 4, November 2026). Modul ini menggunakan contoh Katalog Buku API. Dokumen ini membedakan **prinsip keamanan** dengan **instruksi instalasi yang khusus untuk API Sanctum**.

**Arsitektur RawatKasih saat ini:** Laravel MVC/Blade (aplikasi web satu origin), Supabase Authentication sebagai identitas, JWT pengguna saat server mengakses Supabase Data API, session Laravel terenkripsi pada server, cookie HttpOnly di browser, middleware peran, dan RLS. Saat ini tidak ada REST API aplikasi yang menerbitkan personal access token kepada klien.

## Matriks penerapan

| Prinsip PAWL12 | Status | Implementasi RawatKasih / tindak lanjut |
| --- | --- | --- |
| Auth endpoint, validasi kredensial | Sudah | `LoginRequest` → `LoginController` → Supabase Auth; pesan gagal tidak mengungkap keberadaan email |
| Protect routes | Sudah (web) | Middleware `auth` dan `role`; route khusus admin/caregiver/family, tidak memakai `auth:sanctum` |
| Roles, Policy, anti-IDOR | Sebagian | `EnsureRole`, `ResidentPolicy`, dan JWT pemanggil untuk Data API/RLS; endpoint domain CRUD belum seluruhnya diimplementasikan/diperiksa |
| Rate limit login 5/menit | Sudah | Named limiter `login` (email + IP yang di-hash) pada POST `/login`; batas terlampaui mengembalikan **429** dan **Retry-After** untuk HTML maupun JSON |
| Rate limit recovery | Sudah | Named limiter `password-recovery` (5/menit kombinasi email + IP) pada POST `/forgot-password`; reset password menggunakan route throttle sendiri |
| Sesi dan masa berlaku token | Sudah (Supabase) | Refresh saat mendekati kedaluwarsa; verifikasi identitas dengan Auth API; token tidak diberikan ke frontend |
| Logout/revoke | Sudah (dengan batasan JWT) | Supabase logout scope lokal dicoba, session Laravel selalu diinvalidasi; JWT yang sudah diterbitkan masih dapat berlaku hingga kedaluwarsa |
| Validasi dan pembatasan field output | Sebagian | Model memakai `select` kolom eksplisit; untuk REST API baru gunakan Resource/DTO dan hindari menampilkan kolom privat |
| Error API berbentuk JSON | Siap secara konfigurasi | `bootstrap/app.php` memaksa JSON untuk `api/*` dan permintaan JSON; endpoint `/api/*` bisnis belum dibuat |
| Token abilities | Belum relevan | Tidak ada token Sanctum untuk klien. Pada masa depan gunakan scope/permission server-side dan RLS/Policy, atau rancang autentikasi API khusus |
| Sanctum expiry/pruning | Tidak berlaku | Tidak ada tabel `personal_access_tokens`; siklus refresh dan pencabutan mengikuti Supabase, jangan menjalankan migrasi Sanctum secara otomatis |
| CORS allowlist | Belum diperlukan | Blade dan backend berada pada origin sama. Jika API terpisah ditambahkan, konfigurasi allowlist origin secara eksplisit; CORS bukan pengganti autentikasi |
| Penyaringan aksi menulis | Harus diterapkan tiap modul | Form Request untuk input, Gate/Policy untuk objek, JWT pengguna + RLS untuk data, actor/owner diambil dari identitas server, bukan body request |

## Keputusan arsitektur

**Jangan menyalin langkah `php artisan install:api` dan `auth:sanctum` dari modul hanya untuk login Blade.** RawatKasih sudah punya penyedia identitas di Supabase; menambah autentikasi token paralel tanpa kebutuhan justru memperumit pencabutan sesi, konsistensi role, dan perlindungan data.

Jika tim **benar-benar memerlukan REST API untuk klien lain**, rancang dulu kontrak terpisah: autentikasi request menggunakan token terverifikasi yang sesuai arsitektur, respons JSON 401/403/422/429, output DTO/Resource minimum, authorization tiap record, rate limit per identitas, CORS terbatas, audit, dan pengujian Postman. Jangan langsung memakai `User::createToken()` dengan model `SupabaseUser` yang sekarang.

## Pengujian yang diperlukan sebelum menyatakan aman

- [x] Feature test untuk login tiga role, penolakan role lain, dan profil tidak aktif sudah tersedia.
- [x] Feature test login lima kali lalu 429 + Retry-After dan request JSON tersedia.
- [x] Feature test recovery lima kali lalu 429 + Retry-After tersedia.
- [x] Feature test session refresh, logout saat upstream down, dan kredensial salah tersedia.
- [ ] **Jalankan ulang** seluruh test PHP dan frontend build pada lingkungan pengembangan/CI setelah perubahan ini; hasilnya belum diverifikasi dalam sesi audit ini.
- [ ] Uji login/logout dan reset email end-to-end memakai akun uji terotorisasi tanpa mengekspos data.
- [ ] Uji Policy/RLS dan IDOR untuk setiap endpoint modul ketika fitur CRUD selesai.
- [ ] Bila ada API lintas origin, tambahkan tes origin CORS diizinkan/ditolak serta JSON 401/403/429.
- [ ] Tentukan timeout/masa berlaku sesi produksi, konfigurasi email reset, HTTPS dan cookie Secure.
- [ ] Tinjau penggunaan Redis/shared rate-limit store jika aplikasi berjalan pada lebih dari satu server.

**Batas lingkup:** Audit ini berfokus pada kode aplikasi dan kesesuaian prinsip PAWL12. Tidak menjalankan perubahan tabel Supabase, menonaktifkan RLS, menerbitkan token Sanctum, ataupun mengubah password pengguna.
