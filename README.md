# 💚 RawatKasih — Platform Perawatan Lansia & Portal Keluarga

<p align="center">
  <img src="public/images/rawatkasih-mark.svg" alt="Logo RawatKasih" width="96">
</p>

**RawatKasih** adalah aplikasi web untuk membantu koordinasi perawatan lansia, pencatatan aktivitas caregiver, penyampaian kabar kepada keluarga, dan pemantauan operasional. Proyek ini dikembangkan untuk **Proyek Akhir Pemrograman Web Lanjut** menggunakan **Laravel (MVC), Blade, dan Supabase**.

Fondasi yang tersedia saat ini mencakup **login/logout tiga peran (Admin, Caregiver, Keluarga)**, integrasi Supabase Authentication, sesi Laravel terenkripsi, pembatasan akses berbasis peran, serta UI autentikasi yang mengacu pada desain Figma. **Modul operasional masih dalam pengembangan**; halaman dashboard per peran saat ini baru berupa kerangka.

> **Dokumentasi kolaborasi:** README ini tidak memuat password, token, session, atau kredensial pribadi. Konfigurasi akses backend yang diperlukan anggota kelompok harus dibagikan melalui saluran privat. Jangan mengunggah isi `.env`, screenshot token, atau data pribadi lansia ke repository.

## 🧩 1. Fitur Aplikasi

| Fitur | Deskripsi | Status saat ini |
| --- | --- | --- |
| **Login & Logout** | Login dengan akun Supabase, validasi form, penanganan error, sesi terenkripsi, dan logout | **Sudah diimplementasikan** sebagai fondasi |
| **Hak Akses Tiga Peran** | Peran `admin`, `caregiver`, dan `family`; pengguna diarahkan ke dashboard sesuai profil | **Sudah diimplementasikan** untuk route/shell |
| **Lupa & Reset Kata Sandi** | Form permintaan reset dan penyetelan kata sandi baru melalui Supabase | **Alur kode tersedia**; konfigurasi email dan uji inbox nyata belum selesai |
| **UI Autentikasi** | Halaman login dan pemulihan kata sandi berbasis Blade, mengikuti Figma | **Sudah tersedia**; tetap perlu pengecekan visual di perangkat masing-masing |
| **Manajemen Lansia & Pengguna** | Data lansia, penugasan caregiver, verifikasi keluarga, serta consent | **Belum diimplementasikan** sebagai CRUD aplikasi |
| **Perawatan & Manajemen Tugas** | Template tugas, checklist harian, status, dan alasan penundaan | **Modul anggota 1 — dikembangkan** |
| **Catatan Perawatan & Kabar Keluarga** | Catatan internal, kabar terpublikasi, dan foto sesuai izin | **Modul anggota 2 — dikembangkan** |
| **Portal Keluarga & Kunjungan** | Profil lansia terverifikasi, timeline kabar, dan pengajuan kunjungan | **Modul anggota 3 — dikembangkan** |
| **Insiden & Monitoring** | Pencatatan insiden, tindak lanjut admin, dashboard, dan audit | **Modul anggota 4 — dikembangkan** |

**Catatan:** Keberadaan tabel di Supabase atau halaman dashboard kosong **bukan** bukti bahwa fitur CRUD telah selesai. Cakupan dan temuan detail ada pada [peninjauan PRD](docs/PRD-REVIEW.md).

## 🛠️ 2. Teknologi yang Digunakan

- **Bahasa pemrograman:** PHP, JavaScript, HTML, dan CSS.
- **Framework backend:** Laravel 13 dengan pola **MVC (Model–View–Controller)**.
- **Antarmuka:** Laravel Blade, CSS RawatKasih, Tailwind CSS 4, dan komponen Flux/Livewire pada dependensi proyek.
- **Bundler frontend:** Vite Plus melalui `npm run dev` / `npm run build`.
- **Database & autentikasi:** Supabase Authentication, PostgreSQL, Data API, dan Row Level Security (**RLS**).
- **Session:** Session Laravel terenkripsi pada server; browser menerima cookie sesi Laravel.
- **Validasi & otorisasi:** Form Request, middleware peran, Policy, dan pembatasan RLS.
- **Pengujian:** Pest/PHPUnit, Laravel Pint, dan PHPStan/Larastan.
- **Desain antarmuka:** [Figma — PAWL / Rawat Kasih](https://www.figma.com/design/dEsADF72zaAkTdeS8brN6b/PAWL---Rawat-Kasih?node-id=101-3).

**Catatan versi:** Untuk instalasi dari lockfile saat ini, gunakan **PHP 8.5**, **Composer 2**, dan **Node.js 22+**. Walaupun `composer.json` menyatakan PHP `^8.3`, beberapa dependensi development yang dikunci memerlukan PHP 8.5. Jangan mengubah versi paket tanpa pengujian bersama.

## 💻 3. Prasyarat Sebelum Clone

Pastikan perangkat pengembangan memiliki:

1. **Git** untuk clone, branch, commit, dan Pull Request.
2. **PHP 8.5** beserta ekstensi yang dibutuhkan Composer/Laravel.
3. **Composer 2** untuk memasang dependency PHP.
4. **Node.js 22+** dan **npm** untuk dependency frontend.
5. **Visual Studio Code** atau editor serupa.
6. **Browser modern** (Chrome, Edge, atau Firefox).
7. **Akses internet** untuk instalasi dependency dan komunikasi ke Supabase.
8. **Akun Supabase yang sudah disiapkan oleh pengelola**, jika ingin menguji login nyata.

Periksa versi pada terminal:

```powershell
php -v
composer --version
node --version
npm --version
git --version
```

> Akun untuk pengujian login tidak disediakan di repository. Pengguna harus terdaftar di Supabase Auth, mempunyai baris profil aktif yang cocok, dan memiliki peran yang diizinkan.

## 🚀 4. Panduan Clone dan Menjalankan Project

### Langkah 1 — Clone repository

Buka **PowerShell** atau terminal VS Code:

```powershell
git clone https://github.com/rrrafaa/rawatkasih-web.git
cd rawatkasih-web
git fetch origin
git switch feat/rrrafaa-supabase-auth-mvc
git pull --ff-only origin feat/rrrafaa-supabase-auth-mvc
```

Repository: **https://github.com/rrrafaa/rawatkasih-web**.

> **Penting:** Panduan ini menggunakan branch `feat/rrrafaa-supabase-auth-mvc`, bukan otomatis branch `main`. Jika folder proyek sudah ada, tidak perlu clone ulang; masuk ke folder tersebut, cek `git status`, dan perbarui branch yang sesuai.

### Langkah 2 — Instal dependency

Jalankan dari **root proyek** (folder yang berisi `artisan`, `composer.json`, dan `package.json`):

```powershell
composer install
npm ci
```

Gunakan `npm ci` agar versi frontend mengikuti `package-lock.json`. Jika Composer gagal karena versi PHP tidak memenuhi lockfile, periksa versi PHP aktif terlebih dahulu.

### Langkah 3 — Siapkan konfigurasi lokal `.env`

**Windows / PowerShell:**

```powershell
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
}
```

**macOS / Linux:**

```bash
test -f .env || cp .env.example .env
```

Edit file `.env` milikmu, terutama bagian berikut:

```dotenv
APP_NAME=RawatKasih
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

SESSION_DRIVER=file
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=false

SUPABASE_URL=ISI_URL_PROYEK_YANG_DISETUJUI_TIM
SUPABASE_PUBLISHABLE_KEY=ISI_PUBLISHABLE_KEY_DARI_PENGELOLA
```

Nilai contoh di atas **bukan kredensial siap pakai**. Gunakan URL dan **publishable key** dari konfigurasi tim; jangan gunakan `service_role`/secret key. `.env.example` berisi contoh nilai proyek untuk pengembangan, tetapi `.env` lokal **tidak boleh di-commit**.

Jika `APP_KEY` masih kosong, jalankan **sekali**:

```powershell
php artisan key:generate
```

Jangan mengganti `APP_KEY` yang sudah digunakan: session dan data yang dienkripsi dengan kunci sebelumnya dapat menjadi tidak terbaca.

### Langkah 4 — Build asset frontend

```powershell
npm run build
php artisan optimize:clear
```

Styling utama RawatKasih ada di `public/css/rawatkasih.css`, sementara font/asset bundler berasal dari frontend build. Untuk mengedit frontend dengan hot reload, jalankan `npm run dev` pada terminal **kedua** dan biarkan terminal itu aktif.

### Langkah 5 — Jalankan Laravel

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Buka **http://127.0.0.1:8000/login**. Tekan **Ctrl + C** pada terminal server untuk menghentikannya.

**Pengujian UI awal:** Pastikan panel hijau dan form login tampil; jika belum, periksa **http://127.0.0.1:8000/css/rawatkasih.css** dan muat ulang halaman dengan **Ctrl + Shift + R**.

### Langkah 6 — Uji akun sesuai role

Gunakan akun yang telah dibuat pada **Supabase Authentication** dan mempunyai profil pada `public.profiles` dengan UUID yang sama, `is_active=true`, serta role yang valid.

| Role | Halaman setelah login | Keterangan |
| --- | --- | --- |
| Admin | `/admin` | Kerangka dashboard admin |
| Caregiver | `/caregiver` | Kerangka dashboard caregiver |
| Family | `/family` | Kerangka portal keluarga |

Pendaftaran publik belum tersedia. Jika email/password benar tetapi login ditolak, cek data profil dan izin pengguna bersama pengelola backend.

> **Jangan jalankan** `php artisan migrate:fresh`, `supabase db reset`, atau seeder bawaan ke proyek Supabase bersama. Tabel bisnis telah tersedia di Supabase; proses login ini tidak memerlukan migrasi tabel akun Laravel.

## 🏗️ 5. Struktur Folder (Laravel MVC)

```text
rawatkasih-web/
├── app/
│   ├── Enums/                     # Enum role
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/              # Login & pemulihan kata sandi
│   │   │   └── ...                # Controller per modul tim
│   │   ├── Middleware/            # Otorisasi role & respons privat
│   │   └── Requests/              # Validasi Form Request
│   ├── Models/                    # Model profil & domain Supabase
│   ├── Policies/                  # Otorisasi akses data
│   └── Services/                  # Supabase Client & session
├── bootstrap/
├── config/
│   └── supabase.php
├── docs/
│   ├── TEAM.md                    # Pembagian tugas anggota
│   ├── SUPABASE.md                # Kontrak backend
│   ├── PRD-REVIEW.md              # Status terhadap PRD
│   └── team/                      # Panduan tiap modul
├── public/
│   ├── css/rawatkasih.css        # Styling UI RawatKasih
│   ├── js/rawatkasih.js          # Interaksi UI autentikasi
│   └── images/                   # Logo resmi
├── resources/
│   ├── css/ & js/                # Asset frontend yang dibundel
│   └── views/
│       ├── auth/                 # Login & reset password
│       ├── layouts/              # Layout aplikasi
│       ├── components/           # Komponen Blade
│       ├── admin/                # Shell admin
│       ├── caregiver/            # Shell caregiver
│       ├── family/               # Shell keluarga
│       └── modules/              # Area view per modul
├── routes/
│   ├── web.php
│   └── modules/                  # Route tiap modul anggota
├── supabase/
│   ├── migrations/               # Migration perubahan yang ditinjau
│   └── templates/                # Template email recovery
├── tests/
│   └── Feature/                  # Pengujian HTTP/fitur
├── .env.example
├── composer.json
├── package.json
└── README.md
```

**Alur MVC:** `Browser → Route → Middleware → Controller → Model/Service → Supabase → Blade View`.

Model domain berkomunikasi dengan Supabase menggunakan JWT pengguna agar **RLS** tetap diterapkan. **Jangan menaruh query Supabase atau logika hak akses di Blade View.** Setiap modul sebaiknya memiliki Controller, Form Request, Policy, View, dan test yang sesuai.

## 👥 6. Pembagian Tugas Kelompok

Pengembangan fitur dibagi menjadi **empat modul** sesuai tanggung jawab masing-masing anggota. Pembagian ini menjadi acuan ruang lingkup implementasi, pengujian, dan Pull Request. Detail kontrak serta acceptance criteria tersedia di [`docs/TEAM.md`](docs/TEAM.md).

| Anggota | Modul | Kode fitur | Database utama |
| --- | --- | --- | --- |
| **Orang 1** | Perawatan & Manajemen Tugas | `RK-ADM-04`, `RK-CG-01`, `RK-CG-02` | `care_task_templates`, `care_task_occurrences` |
| **Orang 2** | Catatan Perawatan & Kabar Keluarga | `RK-CG-03` dan bagian kabar dari `RK-FAM-01` | `daily_notes`, `family_updates` |
| **Orang 3** | Portal Keluarga & Manajemen Kunjungan | `RK-FAM-01`, `RK-FAM-02`, `RK-VIS-01` | `visit_requests` |
| **Orang 4** | Insiden & Monitoring Operasional | `RK-CG-04` dan fitur dashboard admin | `incidents`, `audit_logs` |

### Orang 1 — Perawatan & Manajemen Tugas

**Kode fitur:** `RK-ADM-04`, `RK-CG-01`, `RK-CG-02`.

- Admin membuat template rutinitas perawatan.
- Caregiver melihat jadwal dan tugas harian.
- Checklist tugas **selesai**, **ditunda**, atau **terlewat**.
- Mencatat alasan penundaan dan waktu penyelesaian.
- Melihat riwayat pelaksanaan perawatan.

**Database:** `care_task_templates`, `care_task_occurrences`.

### Orang 2 — Catatan Perawatan & Kabar Keluarga

**Kode fitur:** `RK-CG-03` dan bagian kabar dari `RK-FAM-01`.

- Caregiver membuat catatan harian internal.
- Caregiver membuat kabar kondisi lansia untuk keluarga.
- Mengunggah dokumentasi foto berdasarkan izin.
- Mengelola draft dan publikasi kabar.
- Menampilkan timeline kabar yang sudah dipublikasikan.

**Database:** `daily_notes`, `family_updates`.

### Orang 3 — Portal Keluarga & Manajemen Kunjungan

**Kode fitur:** `RK-FAM-01`, `RK-FAM-02`, `RK-VIS-01`.

- Dashboard keluarga dan ringkasan profil lansia.
- Form pengajuan kunjungan keluarga.
- **Empty state** ketika belum ada kunjungan.
- Tampilan status dan riwayat permintaan kunjungan.
- Admin menyetujui atau menolak permintaan kunjungan.
- Panduan kunjungan untuk keluarga.

**Database:** `visit_requests`. Modul ini **menggunakan informasi kabar dari Orang 2**, bukan membuat sumber data kabar baru.

### Orang 4 — Insiden & Monitoring Operasional

**Kode fitur:** `RK-CG-04` dan fitur dashboard admin.

- Caregiver melaporkan insiden lansia.
- Admin melihat daftar dan detail insiden.
- Mencatat tindak lanjut dan status penyelesaian.
- Dashboard statistik tugas, insiden, dan kunjungan.
- Monitoring aktivitas operasional dan tampilan riwayat audit.

**Database:** `incidents`, `audit_logs`. **Statistik dashboard harus membaca data dari modul lainnya**, bukan menduplikasi data tugas maupun kunjungan.

### Ketentuan integrasi antaranggota

- **Orang 2 → Orang 3:** Orang 2 menyediakan sumber data kabar/timeline yang sudah dipublikasikan; Orang 3 menampilkannya pada portal keluarga dengan tetap memperhatikan hak akses.
- **Orang 1 & Orang 3 → Orang 4:** Dashboard admin Orang 4 menggunakan data tugas, kunjungan, dan insiden dari modul masing-masing.
- **Seluruh anggota:** Gunakan fondasi Supabase Auth, role, MVC, Policy/RLS, desain, dan route bersama. Koordinasikan perubahan pada file bersama, seperti `routes/web.php`, layout, middleware, dan konfigurasi.

**Catatan cakupan:** CRUD pengguna dan lansia, `family_links`, `caregiver_assignments`, serta `consents` belum secara eksplisit dialokasikan pada pembagian di atas. Tim perlu menetapkan penanggung jawabnya secara terpisah agar tidak ada pekerjaan inti yang terlewat.

## 🗄️ 7. Backend dan Pengelolaan Data

Satu proyek Supabase bersama digunakan untuk:

- **Authentication:** akun, login, token dan pemulihan kata sandi.
- **PostgreSQL:** tabel `auth.users`, `public.profiles` dan data operasional perawatan.
- **Data API + RLS:** query dibatasi berdasarkan JWT pengguna, peran, relasi, dan izin.
- **Private Storage:** direncanakan untuk foto kabar keluarga yang memerlukan consent; endpoint unggah/unduh aplikasi belum selesai.

Aplikasi memakai sesi Laravel sisi server, **bukan menyimpan access token Supabase di localStorage**. Role dan status aktif dibaca dari tabel profil; atribut role dari form atau metadata pengguna tidak dipercaya.

**Jangan membuat ulang skema database bersama.** Snapshot struktur tersedia di [`docs/supabase-schema.json`](docs/supabase-schema.json), tetapi bukan migration lengkap untuk membangun database dari nol. Setiap perubahan skema harus melalui migration baru, review, dan persetujuan tim.

Dokumentasi lebih lanjut: [Integrasi Supabase](docs/SUPABASE.md).

## 🌿 8. Git Workflow untuk Kolaborasi

**Jangan mengerjakan semua fitur langsung pada `main`.** Gunakan branch fitur dan Pull Request ke branch integrasi yang telah disepakati tim.

### Ambil pembaruan fondasi

```powershell
git fetch origin
git switch feat/rrrafaa-supabase-auth-mvc
git pull --ff-only origin feat/rrrafaa-supabase-auth-mvc
git status
```

Jika sudah ada perubahan lokal, simpan/commit pekerjaan sebelum berpindah branch agar tidak tertimpa.

### Buat branch sesuai modul

Contoh **pilih satu**:

```powershell
git switch -c feat/care-tasks/checklist

# Contoh alternatif untuk anggota lain:
# git switch -c feat/care-updates/timeline
# git switch -c feat/family-visits/request
# git switch -c feat/operations/incidents
```

Branch contoh di atas dibuat dari fondasi auth saat ini. Untuk pekerjaan berikutnya, ikuti branch integrasi resmi (`develop` bila tim memakainya). Jangan menebak target merge dan jangan membuat branch baru dari branch fitur anggota lain.

### Commit, push, dan Pull Request

```powershell
git status
git add <file-yang-ingin-di-commit>
git diff --cached --name-only
git diff --cached
git commit -m "feat(care-tasks): tambah checklist perawatan"
git push -u origin feat/care-tasks/checklist
```

Periksa staged diff sebelum commit untuk memastikan tidak ada credential atau data privat. Buat Pull Request, jelaskan ruang lingkup, lampirkan langkah pengujian dan screenshot bila relevan, lalu minta review.

**Contoh format commit:** `feat(auth): ...`, `feat(care-updates): ...`, `fix(login): ...`, `test(visits): ...`, dan `docs(readme): ...`.

## 🔒 9. Keamanan Repository

- **Jangan commit** `.env`, `APP_KEY`, password, access/refresh token, service-role key, data lansia asli, foto privat, atau dump database.
- Gunakan **publishable key** sesuai konfigurasi server. Jangan mengganti masalah RLS dengan penggunaan `service_role` di route aplikasi.
- Jangan menonaktifkan RLS atau mengubah policy tanpa review.
- Jangan mengganti `APP_KEY` yang sudah dipakai; lindungi session/cookie aplikasi.
- Untuk production, gunakan HTTPS, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan `SESSION_ENCRYPT=true`.
- Jangan mengunggah screenshot yang menampilkan token, alamat email pribadi, atau data sensitif pengguna.

Pemeriksaan cepat sebelum push:

```powershell
git status --short
git diff --cached --name-only
git check-ignore -v .env
git ls-files -- .env
```

`git ls-files -- .env` semestinya **tidak** menampilkan file tersebut. Jika secret pernah terlanjur masuk commit, mengubah `.gitignore` saja tidak menghapusnya dari riwayat: laporkan dan rotasi kredensial bersama pengelola.

## 🧪 10. Pengujian Fungsional

Pengujian otomatis dasar:

```powershell
php artisan test tests/Feature/Auth/AuthDesignAssetsTest.php
php artisan test tests/Feature/Auth/SupabaseAuthenticationTest.php
composer test
npm run build
```

`composer test` mencakup pemeriksaan proyek dan dapat menjalankan lint/type checks selain tes PHP. Test autentikasi menggunakan **HTTP fake**; hasil lulus tidak sama dengan pembuktian akun nyata atau pengiriman email berhasil.

**Checklist sebelum Pull Request:**

- [ ] Dependency berhasil diinstal dan `npm run build` selesai.
- [ ] `/login` tampil dengan CSS/logo dan tidak terjadi horizontal overflow.
- [ ] Login dengan akun pengujian aktif berhasil menuju dashboard sesuai role.
- [ ] Akun tidak aktif, kredensial salah, dan akses ke role lain ditolak.
- [ ] Logout mengakhiri sesi lokal.
- [ ] Halaman `/forgot-password` tampil dan memvalidasi email.
- [ ] Alur reset email diuji end-to-end setelah konfigurasi template/SMTP disiapkan.
- [ ] Modul anggota memvalidasi input, policy, RLS, dan akses antar-lansia.
- [ ] Tidak ada data uji hardcoded yang dipresentasikan sebagai data produksi.
- [ ] Tidak ada informasi privat di commit, screenshot, atau Pull Request.

## 🩺 11. Troubleshooting

| Gejala | Pemeriksaan awal |
| --- | --- |
| **`MissingAppKeyException`** | Pastikan file `.env` ada dan `APP_KEY` terisi; jalankan `php artisan key:generate` **hanya jika key kosong**, lalu `php artisan optimize:clear`. |
| **Halaman login tampil polos/tanpa CSS** | Jalankan `npm ci` dan `npm run build`. Cek URL `/css/rawatkasih.css` serta tab Network browser; lakukan hard refresh. |
| **Vite manifest tidak ditemukan** | Jalankan `npm run build` atau nyalakan `npm run dev` pada terminal kedua. |
| **Port 8000 sudah dipakai** | Hentikan server lama dengan Ctrl+C atau pilih port lain dan sesuaikan `APP_URL`. |
| **Login selalu gagal** | Cek akun Supabase Auth, profil dengan UUID sama, `is_active=true` dan role `admin` / `caregiver` / `family`. |
| **Koneksi Supabase gagal** | Cek koneksi internet, URL, publishable key, dan status backend; jangan menempelkan credential ke issue publik. |
| **Reset password tidak menerima email** | Konfigurasi Auth Site URL, template `supabase/templates/recovery.html`, SMTP, dan inbox uji harus diperiksa. |
| **`composer install` gagal** | Periksa PHP 8.5, ekstensi PHP, versi Composer, dan output error dependency. |
| **Data modul tidak tampil** | Periksa implementasi modul, relasi pengguna, data uji, RLS, dan scope query; dashboard awal memang masih kosong. |

## 📚 12. Dokumentasi dan Status Pengembangan

Dokumen proyek yang perlu dibaca oleh seluruh anggota:

- [`docs/TEAM.md` — Pembagian tugas dan acceptance criteria](docs/TEAM.md).
- [`docs/SUPABASE.md` — Kontrak data, RLS, auth, recovery, dan storage](docs/SUPABASE.md).
- [`docs/PRD-REVIEW.md` — Perbandingan implementasi dengan PRD](docs/PRD-REVIEW.md).
- [`docs/PAWL12-SECURITY-REVIEW.md` — Audit autentikasi dan keamanan berdasarkan materi PAWL12](docs/PAWL12-SECURITY-REVIEW.md).
- [`docs/supabase-schema.json` — Snapshot metadata struktur database](docs/supabase-schema.json).
- [`docs/team/` — Panduan modul anggota](docs/team/).

**Status saat README diperbarui:** fondasi Supabase Auth, route peran, layout autentikasi dan struktur MVC telah tersedia. Implementasi CRUD operasional, dashboard data nyata, serta pengujian email recovery end-to-end masih menjadi pekerjaan lanjutan. README ini bukan pernyataan bahwa seluruh MVP telah selesai.

Tautan demo, screenshot hasil implementasi tiap modul, dan laporan akhir dapat ditambahkan **setelah benar-benar tersedia**.

---

**RawatKasih — Ruang perawatan yang hangat, aman, dan terhubung.**
