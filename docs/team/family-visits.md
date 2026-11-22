# Orang 3 — Portal Keluarga & Manajemen Kunjungan

Modul `FamilyVisits` mengimplementasikan portal keluarga dan alur `visit_requests`. Lingkup ini tidak mencakup keuangan, CRUD pengguna/lansia, ataupun penulisan kabar oleh caregiver.

## Cakupan dan acuan

| Kebutuhan | Implementasi |
|---|---|
| RK-FAM-01: dashboard dan ringkasan lansia | Nama, umur, kamar, status relasi, pilihan lansia yang boleh diakses, serta empty state ketika relasi belum tersedia |
| Bagian kabar RK-FAM-01/02 | Tiga cerita fiktif berlabel dummy, filter kategori/tanggal, dan foto contoh dari desain; belum membaca `family_updates` |
| Kunjungan: RK-FAM-02 pada pembagian tugas; RK-FAM-03 pada katalog PRD | Form tanggal/jam WIB, jumlah dan nama pengunjung, catatan, pengajuan pending, pembatalan milik sendiri, filter status, riwayat dengan pagination, dan panduan |
| RK-VIS-01 | Daftar/detail admin dengan angka aktual, persetujuan atau penolakan satu kali, alasan penolakan wajib, actor/waktu dan audit dari database |
| Privasi | JWT pengguna, RLS, middleware role, Policy per permintaan, relasi terverifikasi dan consent terkini, proyeksi profil terbatas, media melalui route privat |

Sumber persyaratan adalah PDF **Projek Akhir PemWeb Lanjut (3)** yang diunggah pengguna. Pembagian tugas pada permintaan pengguna menjadi batas implementasi; perbedaan nomor PRD dipetakan berdasarkan nama fitur seperti pada [TEAM.md](../TEAM.md). Modul ini tidak menyatakan seluruh PRD aplikasi sudah selesai.

Desain: [Figma v2 — Portal Keluarga](https://www.figma.com/design/dEsADF72zaAkTdeS8brN6b/PAWL---Rawat-Kasih?node-id=160-1205). Frame acuan: beranda `160:1205`, kabar `160:1290`, kunjungan kosong `160:1365`, form `160:1426`, diajukan `160:1501`, profil `160:1572`, dan admin `99:374`. Warna, font, susunan kartu, sidebar, dan aset contoh mengikuti desain; layar kecil menggunakan susunan responsif. Nama/kamar berasal dari Supabase. Foto resident demo dipilih menggunakan UUID yang dikonfigurasi (bukan nama); avatar keluarga memakai route privat dan gambar ilustrasi saat file `<user_uuid>.jpg` belum tersedia. Foto profil nyata dari Supabase masih menunggu kontrak storage. Angka contoh pada desain diganti angka aktual atau diberi label contoh.

## MVC dan materi perkuliahan

| Bagian | Direktori / fungsi |
|---|---|
| Model | `app/Models/FamilyVisits/`: `VisitRequest`, `Resident`, `FamilyLink`, `AccountProfile`, dan gateway `FamilyVisitData` |
| View | `resources/views/modules/family-visits/`; komponen status di `resources/views/components/visit-status.blade.php` |
| Controller | `app/Http/Controllers/FamilyVisits/`: portal, kunjungan keluarga, keputusan admin |
| Validasi | `app/Http/Requests/FamilyVisits/` |
| Otorisasi | `app/Policies/FamilyVisitPolicy.php`, enum `VisitStatus`, dan middleware role bersama |
| Route | `routes/modules/family-visits.php`, dimuat di dalam grup `auth` |
| UI | `public/css/family-visits.css`, `public/js/family-visits.js` |
| Kontrak modul 2 | `app/Contracts/FamilyNewsProvider.php` dan implementasi sementara `DummyFamilyNewsProvider` |
| Pengujian | `tests/Feature/Modules/FamilyVisits/`, `tests/Support/FamilyVisitFake.php`, `tests/Database/family_visit_workflow.sql` |

Penyesuaian dengan materi yang diunggah:

- **PAWL02 Routing:** grup prefix/nama, resource controller terbatas pada aksi yang dibutuhkan, named route pada Blade, dan implicit binding `{visit}`. Binding `VisitRequest` mengambil rekaman melalui gateway dengan token pengguna, bukan koneksi SQLite.
- **PAWL04 CRUD dan Validasi:** Form Request, pemetaan eksplisit data tervalidasi, `@csrf`, `@method('PATCH')`, `old()`, dan pesan error. Status/actor tidak diambil dari input browser. Tidak ada edit/hapus permanen karena PRD mengharuskan riwayat kunjungan tetap ada.
- **PAWL05 Eloquent Relationship:** model Eloquent memakai UUID, casts, fillable terbatas, `belongsTo` untuk resident/requester/reviewer, serta `hasMany` untuk kunjungan dan family links. Gateway memuat relasi sekali melalui embedding PostgREST lalu menghidrasi model dan menandai relasi sudah dimuat, sehingga View tidak menjalankan query atau lazy loading.
- **PAWL06 REST API:** Data API menggunakan GET untuk pembacaan dan POST RPC untuk mutasi transaksional; route web menggunakan GET/POST/PATCH. HTTP 403/404 untuk akses ditolak, 409 untuk konflik keputusan, dan validasi gagal tidak menghasilkan pesan sukses. Modul menggunakan Blade, sehingga tidak menambahkan REST API publik kedua.
- **PAWL07 Authentication/Session/Middleware** dan **PAWL08 Authorization/Role Management:** fondasi sesi/login tetap digunakan; middleware role membedakan keluarga/admin/caregiver, Policy memeriksa pemilik dan status, sedangkan RLS/trigger tetap mengikat akses database pada `auth.uid()`.
- **PAWL09 Microservices:** tetap satu aplikasi modular sesuai arsitektur PRD. Kontrak provider mengisolasi ketergantungan kabar tanpa membuat layanan atau database baru.

**Adaptasi transport Eloquent:** relasi/casts Eloquent dipakai sebagai model domain, tetapi query dan perubahan runtime memakai Supabase HTTP Data API/RPC melalui `FamilyVisitData`. Model tersebut tidak memanggil `save()`, `create()` atau koneksi SQL lokal. Ini mempertahankan integrasi publishable key + JWT + RLS yang sudah dipakai repositori, tanpa memasukkan password PostgreSQL atau service role. Tabel bisnis tidak disalin ke SQLite.

## Route utama

Semua route di bawah memerlukan autentikasi. Route keluarga memakai `role:family`, route admin memakai `role:admin`.

| Metode | URL | Nama route |
|---|---|---|
| GET | `/family` | `family.dashboard` |
| GET | `/family/kabar` | `family.news` |
| GET | `/family/profil` | `family.profile` |
| GET | `/family/kunjungan` | `family.visits.index` |
| GET | `/family/kunjungan/create` | `family.visits.create` |
| POST | `/family/kunjungan` | `family.visits.store` |
| GET | `/family/kunjungan/{visit}` | `family.visits.show` |
| PATCH | `/family/kunjungan/{visit}/batalkan` | `family.visits.cancel` |
| GET | `/admin/kunjungan` | `admin.visits.index` |
| GET | `/admin/kunjungan/{visit}` | `admin.visits.show` |
| PATCH | `/admin/kunjungan/{visit}` | `admin.visits.update` |

Keluarga dapat memilih `?resident=<uuid>` dari relasi yang diizinkan. ID asing atau milik keluarga lain menghasilkan 404. Riwayat keluarga hanya berisi permintaan pengguna tersebut, termasuk bila ada keluarga lain yang terhubung ke lansia sama. Admin dapat membuka daftar melalui tautan **Tinjau pengajuan kunjungan** pada dashboard admin. Profil akun bersifat baca saja sesuai lingkup desain ini; perubahan data dibantu pengelola.

## Supabase dan alur status

Proyek: `arxxfqpgbxthnybznkiw`. Migrasi tambahan **`supabase/migrations/20261117162713_family_visit_workflow.sql` sudah diterapkan ke proyek ini pada 17 November 2026**. Jangan menerapkan ulang, melakukan reset, atau mengubah baseline pada proyek bersama. Untuk proyek pengembangan terpisah, gunakan baseline repositori lalu migrasi tambahan sesuai urutannya.

Migrasi tidak mengubah atau menghapus baris kunjungan lama. Tambahan kolom nullable menjaga kompatibilitas baris lama: `requested_end_at`, `primary_visitor_name`, `submission_key`. UI memiliki fallback untuk field lama yang kosong. Pengajuan baru wajib menyertakan seluruh detail baru.

Empat RPC menggunakan **SECURITY INVOKER**, `search_path` kosong, dan hanya dapat dieksekusi pengguna `authenticated`:

- `submit_family_visit`: actor berasal dari `auth.uid()`, status awal pending, jadwal mendatang, mulai/selesai pada tanggal WIB yang sama, 1–20 pengunjung, nama 2–120 karakter, catatan maksimal 1000 karakter. Indeks unik `(requester_id, submission_key)` mencegah duplikasi akibat pengiriman ulang; kunci sama dengan detail berbeda menghasilkan 409.
- `cancel_family_visit`: mengunci rekaman; hanya keluarga pemilik dengan akses aktif yang dapat membatalkan pending.
- `review_family_visit`: mengunci rekaman; hanya admin aktif yang dapat approve/reject pending. Penolakan wajib beralasan, persetujuan menolak jadwal yang sudah lewat. Reviewer dan waktu ditetapkan database.
- `family_visit_counts`: hitungan status aktual dengan batas RLS pengguna.

Trigger `check_visit_change` juga melindungi perubahan langsung lewat Data API. Detail pengajuan tidak dapat diubah setelah dibuat. Status akhir tidak dapat diputuskan ulang; keputusan bersamaan akan menghasilkan konflik. Trigger audit yang sudah ada mencatat perubahan pada transaksi yang sama. Tidak ada endpoint menghapus riwayat/audit.

Database tetap menjadi sumber kebenaran untuk RLS, consent, actor dan status. Field pilihan di Controller/Policy memberi perlindungan tambahan di aplikasi. Perubahan consent atau relasi yang terjadi setelah form dibuka masih diperiksa ulang saat RPC dijalankan.

## Kabar dummy dan serah terima Orang 2

Data nyata: akun, relasi keluarga terverifikasi, consent, ringkasan lansia, permintaan kunjungan, status dan hitungan admin. Data dummy: **hanya tiga cerita beserta gambar contoh dari Figma**, tanggal tetap 14 November 2026, tanpa hubungan dengan kondisi lansia yang sebenarnya. Banner dan metadata kartu menyebutkan contoh/dummy. Tidak ada insert ke `family_updates` atau `daily_notes`.

Konfigurasi `.env` opsional:

```dotenv
FAMILY_DEMO_NEWS=true
FAMILY_DEMO_PORTRAIT_RESIDENT_ID=11111111-1111-4111-8111-111111111111
FAMILY_FACILITY_NAME="Wisma Kasih Utama"
```

Set `FAMILY_DEMO_NEWS=false` untuk mengosongkan kabar contoh. Lalu jalankan `php artisan config:clear` bila konfigurasi sebelumnya di-cache.

Orang 2 cukup mengganti binding `FamilyNewsProvider` pada `AppServiceProvider` dengan implementasi produksinya. Method `publishedFor($residentId, $photoConsent)` mengembalikan daftar DTO terurut terbaru dengan field `id`, `title`, `summary`, `category`, `published_at` (ISO 8601), `author`, `media` (ID aset untuk dummy; URL privat untuk kabar nyata; atau null), `is_demo` (false untuk kabar nyata). Implementasi produksi wajib memakai JWT pengguna, hanya memuat kabar terpublikasi yang berizin, serta memeriksa consent pada setiap akses media. Kategori UI saat ini `aktivitas` dan `kebersamaan`; perluas kontrak/filter bersama jika kategori modul 2 berbeda.

Aset kabar demo berada di `resources/resident/contoh-kabar/`, foto resident demo di `resources/resident/sulastri/profil.jpg`, dan avatar ilustrasi di `resources/keluarga/`. UUID `FAMILY_DEMO_PORTRAIT_RESIDENT_ID` harus cocok dengan resident dummy yang terkait; nama tidak digunakan untuk menentukan foto. Route `family.demo-media` memeriksa autentikasi, relasi dan consent foto terkini, mengirim `Cache-Control: no-store, private`. Ketika izin foto dicabut, gambar disembunyikan dan akses langsung ditolak; teks tetap dapat terlihat jika izin kabar masih berlaku. Penyimpanan/foto nyata tetap menjadi tanggung jawab modul Orang 2.

## Menjalankan dan memverifikasi

Gunakan instalasi dan `.env` pada README: PHP 8.5, Composer 2, Node.js 22+, publishable key Supabase, tanpa service role. Akun nyata harus sudah tersedia di Supabase Auth dan `profiles` aktif. Keluarga perlu relasi verified, lansia aktif, serta consent sesuai RLS. Tidak ada akun/password uji yang disimpan di repositori. Login keluarga akan membuka portal; login admin tetap membuka dashboard admin dengan tautan ke antrean kunjungan.

```bash
composer ci:check
npm run build
php artisan route:list --path=kunjungan
```

Hasil verifikasi implementasi:

- **73 pengujian / 303 assertions lulus**, termasuk pengujian fondasi; Pint dan PHPStan level 7 lulus. Test fitur memakai HTTP fake Supabase, bukan akun asli.
- Build frontend lulus.
- Browser Chromium: 10 halaman pada desktop 1280 px dan ponsel 360 px, tanpa overflow horizontal/gambar gagal; empat alur pengajuan/pembatalan dan penolakan admin/status keluarga lulus. Browser memakai fixture HTTP lokal dengan session/CSRF Laravel sebenarnya; tidak memakai kredensial keluarga nyata.
- **Uji database langsung pada Supabase lulus**: SQL `tests/Database/family_visit_workflow.sql` memakai role `authenticated`, actor uji dari profil aktif, dan seluruhnya dalam transaksi `ROLLBACK`. Memeriksa duplikasi, input tidak valid, pembatasan role, pembatalan, approval, alasan penolakan, keputusan ulang, serta audit actor. Baris kunjungan awal tetap satu dan tidak ada baris QA tersisa setelah pengujian.

SQL ini hanya untuk operator pada lingkungan pengujian yang memiliki fixture profil/relasi/consent sesuai prasyarat file; jangan menjadikannya seed atau migrasi produksi. Akun dengan relasi/consent nyata tetap perlu menjalani UAT bersama pemilik akun sebelum rilis. Kabar produksi menunggu Orang 2; CRUD profil/relasi/consent dan modul lainnya tetap mengikuti pembagian tim.

Branch lanjutan anggota: `feat/family-visits/nama-fitur`. Gunakan PR, sertakan perubahan kontrak/migrasi dan jalankan pemeriksaan di atas. Jangan mengubah file modul anggota lain atau menerapkan ulang migrasi yang sudah ada di proyek bersama.

### Aset profil

Logo RawatKasih disimpan di `resources/asset/` dan dilayani lewat URL logo yang sudah dipakai aplikasi. Foto akun keluarga disimpan sebagai `resources/keluarga/<uuid-profil>.jpg`. Portal memakai ilustrasi bawaan jika akun belum memiliki berkas foto. Keluarga hanya dapat membuka foto akunnya sendiri, dan admin dapat membuka foto pengaju melalui detail kunjungan. Pada tahap demo, penempatan foto masih melalui repositori. Untuk media produksi, kolom referensi foto telah ditambahkan pada migration Tahap 2 sebagaimana dijelaskan di bawah.

## Tahap 2 — Kontrak metadata foto

Sejak 22 November 2026, Supabase bersama memiliki kolom nullable `residents.profile_photo_path`, `profiles.avatar_path`, serta `family_updates.title`, `family_updates.category`, `family_updates.photo_caption` dari migration `20261122145059_add_family_media_metadata.sql`. `family_updates.photo_path` telah tersedia sebelumnya. `daily_notes` tidak dimodifikasi.

Perubahan pada `App\\Models\\FamilyUpdate` menambahkan kolom-kolom tersebut ke proyeksi pembacaan; portal keluarga masih menggunakan provider dummy sampai tahap integrasi storage dan provider produksi. Jangan menampilkan object key privat langsung di view atau mengubah `family_updates` menjadi daftar catatan perawatan internal.
