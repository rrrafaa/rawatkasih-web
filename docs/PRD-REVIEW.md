# Peninjauan RawatKasih terhadap PRD

Tanggal: 16 November 2026 (Asia/Jakarta). Sumber: PDF pengguna `Projek Akhir PemWeb Lanjut (3).pdf`, PRD 1.0, 29 halaman. Baseline kode: `b9b8df5` (`Initial commit`) pada `rrrafaa/rawatkasih-web`. Peninjauan memakai PRD sebagai kriteria, dengan permintaan pengguna yang terbaru sebagai lingkup implementasi. Instruksi pembagian A–D di PDF tidak menggantikan pembagian Orang 1–4 dari pengguna.

**Kesimpulan: baseline belum memenuhi MVP PRD.** Baseline adalah starter kit Laravel/Livewire dengan akun lokal, registrasi publik dan satu dashboard generik. Perubahan ini menyiapkan autentikasi Supabase, UI auth sesuai Figma, shell tiga role, MVC, dan ruang kerja tim. CRUD operasional sengaja belum dibuat. Dashboard kosong tidak dihitung sebagai dashboard fungsional MVP.

## Bukti dan matriks cakupan

| Persyaratan | Baseline | Hasil tahap ini / pekerjaan berikutnya |
|---|---|---|
| RK-AUTH-01 login/logout tiga role, akun aktif | `User` lokal tidak punya role/is_active; satu `/dashboard` | Login Supabase Auth + profile aktif, redirect role, logout, refresh, validasi identitas/profil setiap request; feature test lulus |
| RK-AUTH-02 akses role dan relasi | Belum ada middleware role/Policy domain | URL tiga role dilindungi server; ResidentPolicy dan RLS menjadi fondasi. Belum seluruh endpoint domain tersedia untuk uji IDOR lengkap |
| RK-AUTH-03 reset password | Broker password lokal Laravel | Flow token_hash Supabase tersedia dan diuji dengan HTTP fake. Template email/Site URL/SMTP serta uji inbox nyata masih perlu konfigurasi |
| RK-ADM-01–03, RK-ADM-05 | Tidak ada CRUD domain | Tabel/profil/relasi/consent ada di Supabase; UI/controller CRUD belum ada dan pemilik belum ditentukan |
| RK-ADM-04, RK-CG-01–02 | Tidak ada | Tabel, unique occurrence dan alasan status tersedia; model baca + ruang Orang 1. Fitur operasional masih backlog |
| Catatan, kabar, foto | Tidak ada | RLS dan bucket privat tersedia; ruang Orang 2. Form/draft/publikasi/unduhan belum diimplementasikan |
| Portal keluarga, kunjungan | Tidak ada | Relasi dan tabel tersedia; ruang Orang 3. Form, status, empty state dan keputusan admin masih backlog |
| Insiden, dashboard, audit | Tidak ada | Tabel dan trigger tersedia; ruang Orang 4. UI, statistik nyata dan monitoring belum ada |
| MVC | Starter auth campur komponen/fitur starter | Auth memiliki Controller sendiri; query domain di Model; View Blade hanya antarmuka; service auth/session dan middleware terpisah |
| Git dan kolaborasi | `.gitignore` ada, baris pertama rusak; belum ada panduan | `.gitignore` diperbaiki, env/privat/dump diabaikan, docs per anggota dan template PR tersedia |
| Figma | Tampilan starter generik | Login `101:3` dan lupa kata sandi `101:18` diadaptasi ke Blade; logo resmi diekspor sebagai aset lokal, font lokal, responsive |

## Perbedaan PRD yang diselaraskan

1. Bagian 1/9/13 menyebut MySQL, sedangkan bagian 6/8/10 menyebut PostgreSQL dan pengguna memilih Supabase. Proyek aktual memakai PostgreSQL.
2. Bagian 9 menyebut sesi Laravel dan Eloquent user lokal. Database aktual sudah memakai Supabase Auth + profiles UUID. Dipilih satu sumber identitas: Supabase Auth, disambungkan ke guard Laravel dan sesi terenkripsi. Model domain memakai Data API/JWT, bukan koneksi postgres berprivilege yang melewati RLS. Ini perubahan arsitektur dari rancangan awal PRD yang harus dicatat.
3. ID fitur ringkas berbeda dari katalog 5.1. Pemetaan lengkap ada di `docs/TEAM.md`; nomor tidak digunakan untuk memindahkan lingkup anggota.
4. PHP ^8.3 pada composer tidak sama dengan persyaratan dependency dev yang terkunci. CI proyek menggunakan PHP 8.5; pengujian tahap ini juga PHP 8.5.
5. Status database aktual memakai deferred/archived/in_progress, sedangkan PRD memakai postponed/hidden/handling. Gunakan pemetaan label, jangan mengganti nilai pada data bersama tanpa migration yang ditinjau.

## Temuan database yang masih perlu dikerjakan

Temuan berikut berasal dari inspeksi policy/constraint/fungsi, bukan klaim bahwa seluruh eksploit atau alur bisnis telah diuji end-to-end.

| Prioritas | Bukti | Dampak / pemilik |
|---|---|---|
| Tinggi | `check_visit_change` mengizinkan state lama pending/approved/rejected pada jalur admin | Keputusan dapat diubah kembali; belum membatasi keputusan sekali hanya dari pending. Orang 3 perlu memperketat transaksi/trigger dan menguji concurrent decision |
| Tinggi | `family_updates.updates_read` menyembunyikan seluruh row bila ada photo_path tanpa photo_share | Teks yang masih berizin ikut hilang. Orang 2 perlu pemisahan DTO/media agar pencabutan foto tidak otomatis menyembunyikan teks |
| Tinggi | `daily_notes` mempunyai UPDATE policy tanpa correction reference atau versi konten | Catatan dapat ditimpa tanpa mekanisme koreksi terperinci sesuai PRD. Orang 2 bersama Orang 4 perlu koreksi append-only |
| Tinggi | `audit_change` menyimpan action/table/id/actor/waktu, tidak menyimpan revisi nilai; histori status khusus hanya tugas/insiden | Belum cukup bukti rekonstruksi semua koreksi sensitif. Orang 4 perlu kontrak audit yang tidak menyalin rahasia, serta mekanisme revisi domain |
| Sedang | Tidak ada riwayat migration remote sebelum pekerjaan ini | Skema awal belum dapat dibuat ulang dari repository. Tim perlu baseline lingkungan uji dan proses migration sebelum pengembangan parallel penuh |
| Sedang | Advisor melaporkan 12 foreign key tanpa indeks pendukung | Perlu penilaian query/statistik saat implementasi modul; tidak menghapus indeks hanya karena belum digunakan pada dataset kecil |
| Sedang | Leaked password protection belum aktif | Konfigurasi Auth proyek perlu ditinjau sebelum pilot; [panduan resmi](https://supabase.com/docs/guides/auth/password-security#password-strength-and-leaked-password-protection) |

Dua temuan auth sudah diperbaiki pada proyek: hak EXECUTE fungsi event trigger RLS yang terlalu terbuka, dan RPC profil yang sebelumnya tidak memeriksa is_active. Migration SQL yang sama dicatat di `supabase/migrations/20261116023335_harden_auth_helpers.sql`.

## Dependensi yang diperbaiki

Audit baseline menemukan 13 advisory pada 8 paket. Dependensi terdampak diperbarui dalam constraint yang ada, termasuk Laravel 13.38 → 13.42, Symfony 8.1.11 untuk komponen terdampak, PHPUnit 13.4.5 dan Pest 5.3.5. Composer melaporkan tidak ada security advisory setelah pembaruan. Tidak mematikan pemeriksaan keamanan untuk melewati konflik versi.

## Validasi yang dilakukan

- 25 test PHP/Pest (107 assertions) pada implementasi auth sebelum pemeriksaan tambahan akhir: login, tiga role dan penolakan lintas-role, nonaktif, role tidak sah, kredensial salah, throttling, refresh, revoked refresh, logout saat outage, recovery invalid/expired dan retry update setelah gangguan.
- PHPStan level 7 lulus; Pint lulus; build Vite lulus dengan font lokal. Lockfile npm diselaraskan agar `npm ci` berjalan.
- Browser Chromium: `/login` 900×690 dan 360×800 serta `/forgot-password` 900×690 merespons 200; tidak ada horizontal overflow, aset logo terisi, toggle password berfungsi. Hasil visual dibandingkan dengan screenshot Figma. Tombol lihat password dan tautan kembali adalah tambahan aksesibilitas/alur.
- Supabase RLS melalui transaksi rollback: keluarga melihat satu resident/profil sendiri dan nol daily_notes/incidents/audit_logs; caregiver melihat dua resident sesuai dua assignment/profil sendiri dan nol audit_logs; admin melihat tiga profil, dua resident dan audit. Akses anon ke tabel profil ditolak pada level privilege. Ini sampel verifikasi, bukan pengganti 20 kasus IDOR PRD.
- Privilege fungsi diperiksa ulang: anon/authenticated tidak dapat mengeksekusi helper DDL, anon tidak dapat memanggil update_my_profile, authenticated boleh memperbarui profil sendiri. Uji rollback membuktikan profil nonaktif ditolak oleh RPC.
- Browser juga mengirim satu percobaan login dengan akun uji yang tidak ada ke Supabase nyata: pesan kredensial umum tampil dan kolom password dikosongkan. Validasi TLS tetap aktif; sertifikat proxy sesi dipasang hanya pada container QA.
- Tidak ada password akun nyata yang tersedia; login sukses ke akun nyata dan pengiriman/reset melalui inbox belum diuji. Feature test HTTP fake membuktikan logika aplikasi; tidak membuktikan konfigurasi email produksi.

## Syarat agar seluruh PRD dapat dinyatakan terpenuhi

Implementasikan modul dan CRUD fondasi yang belum teralokasi, selesaikan temuan database, aktifkan konfigurasi email, tarik baseline skema, dan buktikan UAT-01–08 serta NFR keamanan/aksesibilitas/kinerja dengan data uji. Role shell kosong hanya memenuhi batas tahap saat ini dari pengguna.
