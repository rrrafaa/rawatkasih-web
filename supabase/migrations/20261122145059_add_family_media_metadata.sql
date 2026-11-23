BEGIN;

-- Berkas tetap di penyimpanan privat; kolom ini hanya menyimpan kunci objek, bukan data biner atau URL publik.
ALTER TABLE public.residents
    ADD COLUMN IF NOT EXISTS profile_photo_path text;

ALTER TABLE public.profiles
    ADD COLUMN IF NOT EXISTS avatar_path text;

-- Boleh kosong agar kabar keluarga lama tetap didukung.
ALTER TABLE public.family_updates
    ADD COLUMN IF NOT EXISTS title text,
    ADD COLUMN IF NOT EXISTS category text,
    ADD COLUMN IF NOT EXISTS photo_caption text;

-- Nilai filter Kabar Harian; NULL menandai data lama tanpa kategori.
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conrelid = 'public.family_updates'::regclass
          AND conname = 'family_updates_category_check'
    ) THEN
        ALTER TABLE public.family_updates
            ADD CONSTRAINT family_updates_category_check
            CHECK (category IS NULL OR category IN ('aktivitas', 'kebersamaan'));
    END IF;
END $$;

COMMENT ON COLUMN public.residents.profile_photo_path IS
    'Private storage object path of the resident portrait; access requires family relation and photo_share consent.';
COMMENT ON COLUMN public.profiles.avatar_path IS
    'Private storage object path for the account avatar; user-scoped authorization is required.';
COMMENT ON COLUMN public.family_updates.title IS
    'Optional headline of the update; NULL means a legacy update without a title.';
COMMENT ON COLUMN public.family_updates.category IS
    'Optional Kabar Harian category: aktivitas or kebersamaan; NULL for legacy updates.';
COMMENT ON COLUMN public.family_updates.photo_caption IS
    'Optional family-visible description of the update image.';

NOTIFY pgrst, 'reload schema';

COMMIT;
