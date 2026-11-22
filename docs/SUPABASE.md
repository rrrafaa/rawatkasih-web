# Integrasi Supabase

Proyek: `arxxfqpgbxthnybznkiw`; API: `https://arxxfqpgbxthnybznkiw.supabase.co`.

## Kontrak aktual

Akun ada pada `auth.users` dan profil pada `public.profiles` (UUID yang sama). Role valid: `admin`, `caregiver`, `family`; profil harus aktif. Password tidak dibaca/disimpan oleh aplikasi Laravel. Public signup pada proyek terverifikasi nonaktif (`disable_signup=true`). Akun baru dibuat melalui proses admin Supabase sampai modul RK-ADM-01 tersedia; baris `profiles` perlu dibuat/diaktifkan dengan ID Auth yang sesuai.

Semua tabel bisnis yang diperiksa mempunyai RLS. Scope caregiver dibatasi assignment; keluarga oleh family link verified + consent. Kontak darurat dan catatan sensitif berada pada `resident_private_details`. `Resident` menyeleksi kolom profil terbatas. Tidak menggabungkan data privat ke DTO keluarga.

Status aktual berbeda istilah dari PRD: occurrence `deferred` berarti ditunda (`postponed` di PRD), update `archived` berarti disembunyikan (`hidden`), insiden `in_progress` berarti ditangani (`handling`). Sesuaikan label UI dengan nilai aktual; jangan mengirim nilai PRD yang melanggar constraint.

Snapshot metadata tanpa isi data: `docs/supabase-schema.json`. Ini dokumentasi, bukan migration baseline yang dapat membuat ulang proyek kosong. Riwayat remote awal kosong walaupun tabel sudah ada. Sebelum pengembangan skema penuh, tarik baseline terotorisasi dari lingkungan uji dengan versi CLI yang digunakan tim. Migration hardening pada PR ini hanya berisi perubahan fungsi/privilege, bukan pembuatan seluruh skema.

## Auth dan sesi

Laravel meminta token password dari Auth API, membaca profil melalui Data API dengan JWT pengguna, lalu menyimpan access/refresh token dalam sesi terenkripsi. Pada request terlindungi, token kedaluwarsa diperbarui dan identitas diverifikasi ke Auth API; profil/role/status aktif dibaca ulang. Browser hanya menerima cookie sesi Laravel. `SUPABASE_PUBLISHABLE_KEY` adalah satu-satunya API key yang dibutuhkan; tidak perlu password Postgres atau service_role.

## Konfigurasi email reset yang masih perlu diterapkan

Tools koneksi saat pengerjaan tidak menyediakan pembacaan/perubahan konfigurasi template Auth. Pengiriman email nyata belum diuji karena tidak tersedia akun/inbox uji terotorisasi. Jangan menyatakan alur email produksi lulus sebelum langkah berikut selesai.

1. Pada Auth → URL Configuration, set Site URL ke URL aplikasi yang benar (lokal `http://localhost:8090`, staging/produksi HTTPS).
2. Pada Auth → Email Templates → Reset Password, gunakan template `supabase/templates/recovery.html`, yang mengarahkan ke `{{ .SiteURL }}/reset-password?token_hash={{ .TokenHash }}`. Ini diperlukan karena fragment token pada template default tidak terbaca oleh server Laravel.
3. Konfigurasikan SMTP yang dapat mengirim ke akun uji dan pastikan batas pengiriman serta token expiry memadai. Jangan memakai password SMTP di repository.
4. Minta tautan melalui `/forgot-password`, buka dari inbox uji, simpan password baru, dan pastikan tautan yang sama serta tautan kedaluwarsa ditolak. Cek login password lama gagal dan password baru berhasil.
5. Aktifkan leaked-password protection jika tersedia pada paket proyek. Status saat pemeriksaan: belum aktif.

Link reset menggunakan token rahasia; pastikan proxy/access log menyamarkan query `token_hash`. Response tidak di-cache dan tidak mengirim Referer. Tidak ada resource eksternal pada halaman reset.

## Storage

Bucket `rawatkasih-private` sudah privat, maksimum 3.145.728 byte (3 MiB), MIME JPEG/PNG/WebP. Fondasi saat ini tidak menyediakan endpoint unggah/unduh. Orang 2 wajib menerapkan otorisasi dan consent saat publikasi serta pengambilan file; jangan memakai public URL atau signed URL berumur panjang yang tetap berlaku setelah izin dicabut.

## Perubahan yang diterapkan pada proyek

Migration remote `20261116023335_harden_auth_helpers`:

- Mencabut EXECUTE `rls_auto_enable()` dari PUBLIC, anon, authenticated; event trigger tetap ada.
- Membatasi `update_my_profile` pada profil aktif milik `auth.uid()`, hanya nama dan telepon; role/is_active tidak menjadi parameter.
- Menolak pemanggilan RPC profil oleh anon.

Privilege sudah diperiksa ulang; akun nonaktif ditolak melalui pengujian transaksi rollback. Advisor tetap menandai `update_my_profile` sebagai SECURITY DEFINER yang dapat dipanggil authenticated: ini disengaja untuk mengubah nama/telepon sendiri, dan perlu ditinjau bila fungsi diperluas. [Penjelasan advisor](https://supabase.com/docs/guides/database/database-linter?lint=0029_authenticated_security_definer_function_executable).

## Metadata foto dan kabar keluarga (22 November 2026)

Migration `20261122145059_add_family_media_metadata.sql` telah diterapkan pada proyek Supabase bersama. Kolom opsional baru: `residents.profile_photo_path`, `profiles.avatar_path`, serta `family_updates.title`, `family_updates.category`, `family_updates.photo_caption`. Nilai kategori baru hanya `aktivitas` atau `kebersamaan` (atau NULL untuk baris legacy). `family_updates.photo_path` sudah ada sejak skema awal dan tetap digunakan. `daily_notes` tidak berubah dan tetap internal.

Path foto adalah **object key storage privat**, bukan URL publik atau signed URL permanen. Belum ada file yang diunggah ataupun route produksi untuk memuatnya. RLS dan consent yang sudah ada tetap berlaku; sebelum mengaktifkan rendering media nyata, implementasikan pembatasan akses gambar per resident/user dan tinjau kebijakan `family_updates` jika teks kabar harus tetap terlihat saat `photo_share` dicabut.

Migration ini sudah terdaftar pada remote sebagai versi `20261122145059`; **jangan jalankan ulang di proyek bersama**. Untuk environment baru, urutkan sesuai nomor migration dan audit skema dasar serta migrasi lain yang belum sinkron lebih dahulu.
