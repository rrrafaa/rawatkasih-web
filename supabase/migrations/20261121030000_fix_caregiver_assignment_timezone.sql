
BEGIN;

CREATE OR REPLACE FUNCTION app_private.is_caregiver_for(p_resident uuid)
RETURNS boolean
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path TO ''
AS $function$
  SELECT EXISTS (
    SELECT 1
    FROM public.caregiver_assignments a
    JOIN public.profiles p ON p.id = a.user_id
    WHERE p.id = (SELECT auth.uid())
      AND p.is_active
      AND p.role = 'caregiver'
      AND a.resident_id = p_resident
      AND a.start_date <= (now() AT TIME ZONE 'Asia/Jakarta')::date
      AND (
        a.end_date IS NULL
        OR a.end_date >= (now() AT TIME ZONE 'Asia/Jakarta')::date
      )
  );
$function$;

COMMIT;