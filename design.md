# Design System — RawatKasih Web

> *Dokumentasi standar UI/UX tim untuk konsistensi visual dan perilaku di seluruh aplikasi.*

---

## Layout & Struktur Halaman

### Layout Utama (App Layout)

**Tag layout utama:**
```blade
<x-layouts::app.sidebar>
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
```

**Variasi layout:**
- **App Layout** (`layout.app`) — Dashboard & halaman utama pengguna dengan sidebar Flux
- **Header Layout** (`layout.app.header`) — Layout dengan header top navigation
- **Admin Layout** (`layout.admin`) — Portal admin dengan sidebar custom
- **Caregiver Layout** (`layout.caregiver`) — Workspace caregiver dengan sidebar khusus
- **RawatKasih Layout** (`layout.rawatkasih`) — Layout dasar untuk autentikasi & halaman publik
- **Role Layout** (`layout.role`) — Untuk halaman yang membutuhkan peran khusus

### Struktur Container & Grid

**App Layout (Flux-based):**
```blade
<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
```

**Admin Layout:**
```css
.adm-layout {
    display: flex;
    min-height: 100vh;
}
.adm-main {
    width: calc(100% - 260px);
    margin-left: 260px;
    padding: 42px 48px;
}
```

**Family Visits Layout (Shell-based):**
```css
.fv-shell {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    min-height: 100dvh;
}
.fv-main {
    padding: 32px clamp(24px, 3.2vw, 48px);
}
```

---

## Palet Warna & Tipografi

### Palet Warna Utama

**RawatKasih CSS:**
| Token | Warna | Penggunaan |
|-------|-------|------------|
| `--rk-green` | `#005338` | Aksen utama, tombol utama, link |
| `--rk-bg` | `#f7f8f7` | Background halaman |
| `--rk-text` | `#1b2420` | Warna teks utama |
| `--rk-muted` | `#68776f` | Teks sekunder, label |
| `--rk-heading` | `'Plus Jakarta Sans', sans-serif` | Font heading |

**Admin CSS:**
| Token | Warna | Penggunaan |
|-------|-------|------------|
| `--adm-primary` | `#005338` | Aksen hijau utama |
| `--adm-aqua` | `#85F8C4` | Highlight/interaksi |
| `--adm-accent` | `#FF932C` | Tombol aksi/ekstra |
| `--adm-ink` | `#1b2420` | Teks utama |
| `--adm-muted` | `#68776f` | Teks sekunder, border |

**Family Visits CSS:**
| Token | Warna | Penggunaan |
|-------|-------|------------|
| `--fv-green` | `#005338` | Aksen utama |
| `--fv-mint` | `#e5fcf0` | Background card/status selesai |
| `--fv-peach` | `#fff1e2` | Background status ditunda |

### Status Badge

| Status | Kelas | Warna Background | Warna Teks |
|--------|-------|------------------|------------|
| Pending | `.fv-badge-pending` | `#fff1e2` | `#86550d` |
| Approved | `.fv-badge-approved` | `#e5fcf0` | `#005338` |
| Rejected | `.fv-badge-rejected` | `#fae8e6` | `#9d3634` |
| Cancelled | `.fv-badge-cancelled` | `#eef0ef` | `#52615a` |

### Tipografi

**Font Family:**
- **Heading:** `Plus Jakarta Sans, sans-serif` (font-weight: 700)
- **Body:** `Inter, Arial, sans-serif` (font-size: 14px, line-height: 1.5)

**Ukuran Heading:**
```css
.fv-page-heading h1 { font-size: 30px; line-height: 44px; }
.fv-page-heading p { font-size: 15px; line-height: 22px; }
```

---

## Daftar Komponen Reusable

### Komponen Blade

**1. Visit Status Badge**
```blade
<x-visit-status :status="$visit->status" />
```
- Props: `status` (VisitStatus enum)
- Output: `<span class="fv-badge fv-badge-{status->value}">{status->label()}</span>`

**2. App Logo (Brand)**
```blade
<x-rk-brand />
```
- Lokasi: `resources/views/components/rk-brand.blade.php`
- Konten: Logo SVG + teks "RawatKasih"

**3. Desktop User Menu**
```blade
<x-desktop-user-menu />
```
- Menampilkan avatar, nama, email, dan menu logout
- Menggunakan Flux sidebar profile

**4. Sidebar Layout**
```blade
<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
```

### Komponen Flux

Tim menggunakan [Flux UI](https://fluxui.com) sebagai komponen utama:

```blade
<!-- Sidebar -->
<flux:sidebar sticky collapsible="mobile">
    <flux:sidebar.header>...</flux:sidebar.header>
    <flux:sidebar.nav>...</flux:sidebar.nav>
    <flux:spacer />
    <flux:sidebar.nav>...</flux:sidebar.nav>
</flux:sidebar>

<!-- Header -->
<flux:header container>
    <flux:navbar>...</flux:navbar>
    <flux:spacer />
</flux:header>

<!-- Toast -->
@persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
@endpersist
```

---

## Pola Tabel, Card & Form

### Tabel Responsif

```blade
<div class="adm-table-wrap" tabindex="0" role="region">
    <table class="adm-table">
        <thead>
            <tr>
                <th scope="col">Kolom 1</th>
                ...
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>...</td>
                ...
            </tr>
        </tbody>
    </table>
</div>
```

**CSS Tabel:**
```css
.adm-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    white-space: nowrap;
}
.adm-table th {
    padding: 15px 18px;
    background: #F8FAF8;
    color: var(--adm-muted);
    font-size: 12px;
    font-weight: 600;
}
.adm-table td {
    padding: 17px 18px;
    border-top: 1px solid #EDF1EE;
    vertical-align: middle;
}
.adm-table tbody tr:hover {
    background: #FBFDFB;
}
```

### Card

```css
/* Admin Card */
.adm-visit-card {
    display: grid;
    justify-items: start;
    gap: 12px;
    padding: 18px;
    border: 1px solid var(--adm-border);
    border-radius: 14px;
    background: var(--adm-surface);
}

/* Family Visits Card */
.fv-card {
    padding: 24px;
    background: #fff;
    border-radius: 20px;
}
```

### Form Input

**Standar Input:**
```blade
<input class="adm-form-control" 
       min-length="44px" 
       @error('field') aria-invalid="true" @enderror>
<p class="adm-form-error">{{ $errors->first('field') }}</p>
```

**CSS Input:**
```css
.adm-form-control {
    display: block;
    width: 100%;
    min-height: 46px;
    padding: 11px 13px;
    border: 1px solid var(--adm-border);
    border-radius: 10px;
    background: white;
    color: var(--adm-ink);
}
.adm-form-control:focus {
    border-color: var(--adm-primary);
    outline: 2px solid rgba(0, 83, 56, 0.12);
}
.adm-form-control[aria-invalid=true] {
    border-color: #b54c4b;
}
```

### Empty State

```blade
<div class="rk-care-empty rk-stitch-empty-residents">
    <h2>Judul empty state</h2>
    <p>Deskripsi empty state</p>
</div>
```

**CSS Empty State:**
```css
.rk-care-empty {
    background: #fff;
    border: 1px dashed #cbd9ce;
    border-radius: 12px;
    text-align: center;
    padding: 25px 20px;
    color: #718078;
    font-size: 14px;
}
```

---

## Aturan Wajib PRD RawatKasih

Berikut adalah standar aksesibilitas & UX yang harus dipatuhi untuk semua halaman:

### 1. Aksesibilitas

**Ukuran Minimum Interaksi:**
- Tombol/input minimal: **44x44px** (`min-height: 44px`)
- Area klik navigasi: `min-height: 46px` (nav links)
- Link logout: `min-height: 44px`

**Focus Visible:**
```css
.rk-body :focus-visible {
    outline: 3px solid #b66719;
    outline-offset: 4px;
}
```

**ARIA Labels:**
```blade
<button aria-label="Tutup dialog">×</button>
<a aria-current="page">Navigation item</a>
<div role="alert">Error message</div>
```

### 2. Responsivitas

**Breakpoint Utama:**
- Mobile: `max-width: 760px`
- Tablet: `min-width: 761px` and `max-width: 1050px`
- Desktop: `min-width: 1051px`

**Range yang Didukung:** **360px sampai 1366px**

```css
@media (max-width: 760px) {
    .fv-main {
        padding: 24px 18px;
    }
    .adm-nav {
        flex-direction: row;
        overflow-x: auto;
    }
}
```

### 3. Indikator Status

Status **harus** ditampilkan dengan:
1. **Teks Bahasa Indonesia** (misal: "Belum", "Selesai", "Ditunda", "Terlewat")
2. **Warna + Ikon** sebagai pendukung visual

```css
.rk-status-pending    { background: #f1f3f1; color: #657168; }
.rk-status-completed  { background: #e5f3e9; color: #28633d; }
.rk-status-postponed  { background: #fff2dc; color: #94601f; }
.rk-status-missed     { background: #fbe9e7; color: #a4433b; }
```

### 4. Peringatan SOP Darurat Form Insiden

Untuk form yang melibatkan keputusan medis atau insiden:
- Gunakan dialog konfirmasi dengan `aria-modal="true"`
- Sertakan warning visual: `.adm-visit-warning`
- Pastikan ada tombol batal yang jelas

```blade
<p class="adm-visit-warning">Jadwal sudah lewat. Pengajuan ini hanya bisa ditolak.</p>
```

### 5. Error Handling

```blade
@error('field')
    <p class="adm-form-error" role="alert">{{ $message }}</p>
@enderror
```

---

## Pola UI Khusus

### Admin Portal Navigation

```html
<nav class="adm-nav" aria-label="Navigasi admin">
    <a href="..." class="adm-nav-link {{ request()->routeIs('route.name') ? 'is-active' : '' }}">
        <svg class="adm-nav-icon">...</svg>
        <span>Nama Menu</span>
    </a>
</nav>
```

### Caregiver Sidebar

```css
.rk-care-nav-link {
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 48px;
    padding: 12px 16px;
    border-radius: 12px;
    color: #1b2420;
}
.rk-care-nav-link.is-active {
    background: #005338;
    color: #fff;
}
```

### Tabs & Filters

```blade
<nav class="rk-care-view-tabs" aria-label="Navigasi tampilan">
    <a class="rk-care-view-tab {{ $isActive ? 'is-active' : '' }}">Tab 1</a>
    <a class="rk-care-view-tab {{ $isActive ? 'is-active' : '' }}">Tab 2</a>
</nav>
```

### Progress Track

```css
.rk-stitch-progress-track {
    width: 100%;
    height: 6px;
    background: #e5fcf0;
    border-radius: 20px;
}
.rk-stitch-progress-value {
    height: 100%;
    background: #005338;
    transition: width 250ms ease;
}
```

---

## Komponen UI Lanjutan

### Dialog/Styled Modal

```blade
<div class="rk-care-dialog-overlay" id="care-task-dialog" hidden>
    <section class="rk-care-dialog" role="dialog" aria-modal="true">
        <header class="rk-care-dialog-header">
            <h2>Judul Dialog</h2>
            <button type="button" class="rk-care-dialog-close" aria-label="Tutup dialog">×</button>
        </header>
        <form>...</form>
    </section>
</div>
```

### Dropdown Menu

```blade
<flux:dropdown position="bottom" align="start">
    <flux:profile :name="auth()->user()->name" />
    <flux:menu>
        <flux:menu.item :href="route('profile.edit')">Settings</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

---

## Prinsip Desain Tim

1. **Konsistensi Warna:** Selalu gunakan `--rk-green` (#005338) sebagai aksen utama, kecuali ada status khusus (danger, warning)

2. **Typografi:** Kombinasi Inter untuk body text, Plus Jakarta Sans untuk heading - hindari perubahan font

3. **Spacing:** Gunakan gap pada flex/grid, bukan margin pecahan. Padding umum: 16px-24px pada desktop, 12px-18px pada mobile

4. **Border Radius:** 
   - Card: 12px-16px
   - Button: 10px
   - Input: 8px-10px
   - Badge: 20px

5. **Shadow:** Gunakan shadow lembut, contoh: `box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08)`

6. **Hover States:** Pastikan ada feedback visual untuk interaksi

---

## Reference File

| File | Lokasi | Deskripsi |
|------|--------|-----------|
| RawatKasih CSS | `public/css/rawatkasih.css` | Styling utama untuk halaman publik & auth |
| Admin CSS | `public/css/admin-care-tasks.css` | Styling portal admin |
| Family Visits CSS | `public/css/family-visits.css` | Styling modul kunjungan keluarga |
| Main Layout | `resources/views/layouts/app.blade.php` | Struktur halaman utama |
| Admin Layout | `resources/views/layouts/admin.blade.php` | Struktur halaman admin |
| Caregiver Layout | `resources/views/layouts/caregiver.blade.php` | Struktur halaman caregiver |
| Components | `resources/views/components/` | Reusable component library |

---

*Dokumen ini dibuat untuk memastikan konsistensi UI/UX di seluruh proyek RawatKasih Web. Semua perubahan pada komponen atau styling harus selalu merujuk pada dokumen ini.*