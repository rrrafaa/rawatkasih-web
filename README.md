# RawatKasih

Fondasi Laravel MVC untuk RawatKasih: Supabase Auth, sesi Laravel terenkripsi, login sesuai Figma, dan halaman awal kosong untuk admin, caregiver, keluarga. Modul operasional dikerjakan oleh empat anggota sesuai [pembagian tim](docs/TEAM.md).

## Menjalankan

Gunakan PHP 8.5 untuk development/CI sesuai `composer.lock` saat ini, Composer 2 dan Node 22+. `composer.json` menyebut PHP ^8.3, tetapi dependensi dev yang terkunci memerlukan PHP 8.5. Jangan menurunkan versi tanpa menyelaraskan dependensi.

```sh
composer install
cp .env.example .env
php artisan key:generate
npm ci
npm run build
php artisan serve --port=8090
```

Isi `SUPABASE_PUBLISHABLE_KEY` di `.env` dengan publishable key aktif dari proyek `arxxfqpgbxthnybznkiw`. URL sudah disetel di `.env.example`. Key yang dibutuhkan bukan `service_role`. Jangan menyalin kunci rahasia ke source atau mengirimkannya ke browser.

Jika ingin memakai alamat lokal `http://127.0.0.1:8000/login`, jalankan `php artisan serve --host=127.0.0.1 --port=8000` dan atur `APP_URL=http://127.0.0.1:8000` di `.env`. Jangan timpa file `.env` lama atau mengganti `APP_KEY` aplikasi yang sudah digunakan. Perintah di atas (`npm run build`) untuk build asset frontend; saat mengembangkan style, jalankan `npm run dev` di terminal kedua. Jika halaman login terlihat seperti HTML tanpa CSS, periksa request `/css/rawatkasih.css` di browser DevTools > Network dan jalankan `php artisan optimize:clear`. Style inti halaman login disajikan dari `public/css/rawatkasih.css`, sehingga tidak memerlukan Vite aktif; bundle Vite tetap digunakan bila tersedia untuk font dan komponen lainnya.

Buka `http://localhost:8090/login`. Gunakan akun yang sudah dibuat pada Supabase Auth dan mempunyai baris `public.profiles` dengan ID yang sama, `is_active=true`, serta role `admin`, `caregiver`, atau `family`. Password dikelola Supabase Auth. Tidak ada akun/password demo tetap di repository dan tidak ada pendaftaran publik.

Tidak perlu menjalankan migration Laravel untuk login. Tabel bisnis yang sudah ada di Supabase diakses melalui Data API dengan JWT pengguna, sehingga RLS berlaku. Jangan menjalankan `migrate:fresh`, `db reset`, atau seeder starter ke proyek bersama.

## Autentikasi dan MVC

- `app/Http/Controllers/Auth/`: login/logout dan pemulihan kata sandi.
- `app/Services/`: komunikasi Supabase Auth, refresh token, sesi.
- `app/Models/Profile.php`: membaca profil aktif dan role dari database.
- `app/Models/*`: fondasi query domain Supabase, JWT pengguna, kolom eksplisit, pagination 25.
- `app/Http/Middleware/EnsureRole.php`: pemeriksaan role di server.
- `app/Policies/ResidentPolicy.php`: contoh Policy yang memeriksa hasil scope RLS.
- `resources/views/auth/`: antarmuka Blade; tidak ada query database dalam View.
- `resources/views/{admin,caregiver,family}/dashboard.blade.php`: halaman kosong per role.
- `routes/modules/`: ruang route untuk empat modul.

Role selalu dibaca ulang dari `profiles` setelah validasi `/auth/v1/user`; role dari form atau `user_metadata` tidak digunakan. Token hanya disimpan di sesi Laravel sisi server, bukan localStorage, cookie JWT, atau HTML. Cookie Laravel bersifat HttpOnly. Session ID dan CSRF token dirotasi pada login/logout. Logout berusaha mencabut refresh token di Supabase dan selalu menghancurkan sesi lokal. JWT Supabase yang telah diterbitkan dapat tetap berlaku sampai kedaluwarsa; logout tidak menjamin JWT lain langsung tidak berlaku. Halaman terlindungi tidak disimpan cache.

Arsitektur ini menyesuaikan PRD bagian auth/Eloquent dengan skema aktual `auth.users` + `public.profiles`; tidak membuat database akun Laravel kedua. File starter `User`, migration `users`, seeder, dan settings yang masih ada tidak dipakai oleh route aplikasi. Fitur settings/registrasi starter sengaja tidak diaktifkan.

## Pemulihan kata sandi

Lihat [konfigurasi Supabase](docs/SUPABASE.md). Template email dan URL aplikasi harus dikonfigurasi sebelum alur email nyata dapat dinyatakan selesai. Token baru dikonsumsi saat pengguna mengirim kata sandi baru, sehingga pemindai tautan email tidak menghabiskan token melalui GET.

## Pengujian

```sh
composer test
npm run build
```

Test autentikasi menggunakan HTTP fake dan tidak membutuhkan password akun atau akses database bersama. Test starter akun lokal diganti test Supabase untuk perilaku yang benar-benar dipakai. Bukti pemeriksaan database dan keterbatasannya ada di [laporan PRD](docs/PRD-REVIEW.md).

Untuk produksi: gunakan HTTPS, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, dan `APP_KEY` yang stabil. File session/cache sesuai untuk satu instance pilot; jika deployment memakai beberapa instance, ganti dengan penyimpanan sesi dan lock bersama. Jangan simpan session/cache di direktori publik.

## Status

Fondasi autentikasi tersedia; aplikasi belum memenuhi seluruh MVP PRD karena halaman operasional sengaja kosong. Lihat [laporan peninjauan](docs/PRD-REVIEW.md), [konfigurasi database](docs/SUPABASE.md), dan [pembagian kerja](docs/TEAM.md).
