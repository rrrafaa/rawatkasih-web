
BEGIN;

-- 1. Ganti aturan status yang diperbolehkan.
ALTER TABLE public.care_task_occurrences
    DROP CONSTRAINT care_task_occurrences_status_check;

ALTER TABLE public.care_task_occurrences
    ADD CONSTRAINT care_task_occurrences_status_check
    CHECK (
        status IN ('pending', 'completed', 'postponed', 'missed')
    );

-- 2. Alasan wajib untuk postponed dan missed.
ALTER TABLE public.care_task_occurrences
    DROP CONSTRAINT care_task_occurrences_check;

ALTER TABLE public.care_task_occurrences
    ADD CONSTRAINT care_task_occurrences_check
    CHECK (
        status NOT IN ('postponed', 'missed')
        OR length(trim(coalesce(reason, ''))) > 0
    );

-- 3. Sesuaikan fungsi normalisasi dan validasi status.
CREATE OR REPLACE FUNCTION app_private.handle_task_update()
RETURNS trigger
LANGUAGE plpgsql
SET search_path TO ''
AS $function$
BEGIN
    IF NEW.status IN ('postponed', 'missed')
       AND nullif(trim(coalesce(NEW.reason, '')), '') IS NULL THEN
        RAISE EXCEPTION 'Alasan wajib untuk tugas ditunda atau terlewat';
    END IF;

    IF NEW.status = 'completed' THEN
        IF TG_OP = 'INSERT' OR NEW.status IS DISTINCT FROM OLD.status THEN
            NEW.completed_by := auth.uid();
            NEW.completed_at := now();
        END IF;

        NEW.reason := NULL;
    ELSE
        NEW.completed_by := NULL;
        NEW.completed_at := NULL;
    END IF;

    RETURN NEW;
END;
$function$;

COMMIT;

NOTIFY pgrst, 'reload schema';