
BEGIN;

CREATE OR REPLACE FUNCTION app_private.generate_care_task_occurrences(
    p_task_date date,
    p_resident_ids uuid[]
)
RETURNS integer
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path TO ''
AS $function$
DECLARE
    v_inserted integer;
BEGIN
    INSERT INTO public.care_task_occurrences (
        template_id,
        task_date,
        title,
        category,
        scheduled_time,
        instructions
    )
    SELECT
        t.id,
        p_task_date,
        t.title,
        t.category,
        t.scheduled_time,
        t.instructions
    FROM public.care_task_templates t
    WHERE t.is_active = true
      AND t.resident_id = ANY(p_resident_ids)
      AND (
          t.frequency = 'daily'
          OR (
              t.frequency = 'weekly'
              AND EXTRACT(DOW FROM p_task_date)::integer
                  = ANY(t.days_of_week)
          )
      )
    ON CONFLICT (template_id, task_date) DO NOTHING;

    GET DIAGNOSTICS v_inserted = ROW_COUNT;

    RETURN v_inserted;
END;
$function$;

ALTER FUNCTION app_private.generate_care_task_occurrences(date, uuid[])
OWNER TO postgres;

REVOKE ALL ON FUNCTION
    app_private.generate_care_task_occurrences(date, uuid[])
FROM PUBLIC, anon, authenticated;


CREATE OR REPLACE FUNCTION public.generate_today_care_task_occurrences()
RETURNS integer
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path TO ''
AS $function$
DECLARE
    v_today date;
    v_resident_ids uuid[];
    v_inserted integer;
BEGIN
    v_today := (now() AT TIME ZONE 'Asia/Jakarta')::date;

    IF NOT EXISTS (
        SELECT 1
        FROM public.profiles p
        WHERE p.id = (SELECT auth.uid())
          AND p.is_active = true
          AND p.role = 'caregiver'
    ) THEN
        RAISE EXCEPTION 'Akses hanya untuk caregiver aktif.'
            USING ERRCODE = '42501';
    END IF;

    SELECT COALESCE(
        array_agg(DISTINCT a.resident_id),
        ARRAY[]::uuid[]
    )
    INTO v_resident_ids
    FROM public.caregiver_assignments a
    WHERE a.user_id = (SELECT auth.uid())
      AND a.start_date <= v_today
      AND (
          a.end_date IS NULL
          OR a.end_date >= v_today
      );

    v_inserted :=
        app_private.generate_care_task_occurrences(
            v_today,
            v_resident_ids
        );

    RETURN v_inserted;
END;
$function$;

ALTER FUNCTION public.generate_today_care_task_occurrences()
OWNER TO postgres;

REVOKE ALL ON FUNCTION
    public.generate_today_care_task_occurrences()
FROM PUBLIC, anon;

GRANT EXECUTE ON FUNCTION
    public.generate_today_care_task_occurrences()
TO authenticated;

NOTIFY pgrst, 'reload schema';

COMMIT;