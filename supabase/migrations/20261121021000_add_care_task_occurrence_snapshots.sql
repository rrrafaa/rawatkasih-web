
BEGIN;

ALTER TABLE public.care_task_occurrences
ADD COLUMN title text,
ADD COLUMN category text,
ADD COLUMN scheduled_time time,
ADD COLUMN instructions text;

UPDATE public.care_task_occurrences AS o
SET
    title = t.title,
    category = t.category,
    scheduled_time = t.scheduled_time,
    instructions = t.instructions
FROM public.care_task_templates AS t
WHERE t.id = o.template_id;

ALTER TABLE public.care_task_occurrences
ALTER COLUMN title SET NOT NULL,
ALTER COLUMN category SET NOT NULL;

COMMENT ON COLUMN public.care_task_occurrences.title
IS 'Snapshot judul tugas saat occurrence dibuat';

COMMENT ON COLUMN public.care_task_occurrences.category
IS 'Snapshot kategori tugas saat occurrence dibuat';

COMMENT ON COLUMN public.care_task_occurrences.scheduled_time
IS 'Snapshot waktu tugas saat occurrence dibuat';

COMMENT ON COLUMN public.care_task_occurrences.instructions
IS 'Snapshot instruksi tugas saat occurrence dibuat';

COMMIT;