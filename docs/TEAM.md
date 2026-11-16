# Pembagian kerja empat anggota

Pembagian pada permintaan pengguna menggantikan pembagian A–D di PRD. Setiap anggota mengerjakan satu fitur dari Model → Policy → Form Request → Controller → View → pengujian. Jangan membuat empat salinan aplikasi atau empat database bisnis yang tidak terhubung.

| Pemilik | Modul dan ID dari permintaan | Tabel utama | Domain / route file |
|---|---|---|---|
| Orang 1 | Perawatan & Manajemen Tugas: RK-ADM-04, RK-CG-01–02 | care_task_templates, care_task_occurrences; care_task_status_history | CareTasks / care-tasks.php |
| Orang 2 | Catatan Perawatan & Kabar Keluarga: RK-CG-03, bagian kabar RK-FAM-01 | daily_notes, family_updates | CareUpdates / care-updates.php |
| Orang 3 | Portal Keluarga & Manajemen Kunjungan: RK-FAM-01–02, RK-VIS-01 | visit_requests | FamilyVisits / family-visits.php |
| Orang 4 | Insiden & Monitoring Operasional: RK-CG-04, RK-DASH-01, audit | incidents, audit_logs; incident_status_history | Operations / operations.php |

Ada petunjuk direktori untuk setiap orang di `docs/team/`. Model berada di `app/Models/`; folder Controller, Form Request, View, dan test modul sudah dibuat. Model yang ada saat ini adalah fondasi pembacaan; fitur simpan dan alur domain harus ditambahkan beserta validasi dan Policy, bukan dianggap sudah selesai.

## Acceptance criteria per modul

**Orang 1:** Admin membuat/nonaktifkan template per lansia. Caregiver hanya melihat penugasan aktif pada tanggal panti Asia/Jakarta; checklist pending/completed/deferred/missed, alasan deferred/missed, actor dan waktu tersimpan. Histori dipertahankan, unique template+tanggal mencegah duplikasi. Jangan menganggap `deferred` dan `postponed` sebagai dua status berbeda.

**Orang 2:** DailyNote internal terpisah dari FamilyUpdate. Draft tidak terlihat keluarga. Kabar dipublikasikan setelah pemeriksaan consent; JPEG/PNG/WebP maksimal 3 MB pada bucket privat `rawatkasih-private`. Setiap unduhan harus memeriksa relasi, status publikasi, dan consent terkini. Pencabutan izin foto tidak seharusnya menghapus kabar teks yang masih berizin. Orang 2 memiliki query/DTO timeline yang dipakai Orang 3.

**Orang 3:** Beranda hanya menampilkan lansia dengan family link verified serta consent yang berlaku; informasi profil dibatasi. Kunjungan memvalidasi tanggal mendatang, jam, jumlah pengunjung, serta status. Pending dapat dibatalkan pemilik; hanya admin yang dapat memutuskan satu kali; penolakan perlu alasan. Tampilkan empty state, status/riwayat, dan panduan kunjungan. Jangan membuat query `family_updates` sendiri yang melewati kontrak Orang 2.

**Orang 4:** Caregiver membuat insiden untuk lansia yang ditugaskan. Admin melihat daftar/detail, menindaklanjuti dan menutup dengan jejak actor/waktu. Dashboard membaca angka nyata modul 1–3, bukan membuat tabel/angka duplikat. Audit hanya baca dari UI, tidak ada tombol hapus. Tetapkan format audit bersama sebelum modul lain menulis.

## Fondasi bersama dan pekerjaan yang belum memiliki pemilik

Autentikasi, role, konfigurasi, layout bersama, kontrak skema, serta kebijakan akses merupakan fondasi lintas modul. **CRUD pengguna/lansia, family_links, caregiver_assignments dan consents (RK-ADM-01–03, RK-ADM-05) belum teralokasi pada pembagian baru.** Keempat anggota perlu menetapkan pemiliknya agar MVP lengkap; fondasi login bukan pengganti fitur admin tersebut. Jangan diam-diam memindahkan semua pekerjaan ini ke Orang 4.

## Cara bekerja

1. Tim menetapkan branch integrasi (`develop` bila digunakan). Branch `main` untuk versi demo yang lolos pemeriksaan.
2. Setiap fitur dibuat di branch sendiri, contoh `feat/care-tasks/checklist`, `feat/care-updates/timeline`, `feat/family-visits/request`, `feat/operations/incidents`.
3. Mulai dari kontrak tabel aktual di `docs/supabase-schema.json`. Penambahan skema menggunakan migration baru yang ditinjau bersama; jangan mengedit migration yang sudah diterapkan.
4. Hindari mengubah route auth, session, layout bersama atau Model anggota lain tanpa koordinasi dalam PR. Berkomunikasi mengenai perubahan DTO timeline/statistik sebelum implementasi.
5. Controller memakai Form Request dan `Gate::authorize`/Policy. Query Model membawa token pengguna sehingga RLS tetap berlaku. Tidak memakai service_role untuk memudahkan query modul.
6. Jalankan `composer test` dan `npm run build`. Tambahkan uji jalur berhasil, invalid input, akses lintas-role/lansia, dan perubahan status yang tidak sah untuk setiap fitur sensitif.
7. Buka PR; anggota lain meninjau. CI harus lulus sebelum merge. Beri ringkasan migration, langkah uji, tangkapan layar bila relevan, dan ID PRD.
8. `.env`, password, data lansia asli, foto privat, dump database, serta file runtime tidak di-commit. Pengembangan memakai data dummy pada lingkungan uji; jangan reset proyek Supabase bersama.

## Pemetaan konflik ID PRD

| Nama fitur | Tabel ringkas / permintaan | Katalog PRD 5.1 |
|---|---|---|
| Catatan internal | RK-CG-03 | RK-CG-03 |
| Kabar dan foto | Bagian RK-CG-03/RK-FAM-01 | RK-CG-04, RK-CG-05; RK-FAM-02 untuk baca timeline |
| Kunjungan keluarga | RK-FAM-02 | RK-FAM-03 |
| Insiden | RK-CG-04 | RK-CG-06 dan RK-INC-01 |

Dalam PR cantumkan nama fitur dan kedua ID jika berbeda; lingkup kerja tidak berubah hanya karena nomor bertabrakan.
