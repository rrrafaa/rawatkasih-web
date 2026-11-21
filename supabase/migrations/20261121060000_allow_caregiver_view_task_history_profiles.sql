
BEGIN;

-- Memeriksa apakah profil pengguna pernah menjadi pelaku
-- perubahan pada occurrence yang boleh dikelola pengguna saat ini.
CREATE OR REPLACE FUNCTION app_private.can_view_profile_for_task(
    p_profile uuid
)
RETURNS boolean
LANGUAGE sql
STABLE
SECURITY DEFINER
SET search_path TO ''
AS $function$
    SELECT EXISTS (
        SELECT 1
        FROM public.care_task_status_history h
        JOIN public.care_task_occurrences o
            ON o.id = h.occurrence_id
        WHERE h.changed_by = p_profile
          AND app_private.can_manage_task(o.template_id)
    );
$function$;

ALTER FUNCTION app_private.can_view_profile_for_task(uuid)
    OWNER TO postgres;

-- Batasi eksekusi fungsi.
REVOKE ALL ON FUNCTION app_private.can_view_profile_for_task(uuid)
    FROM PUBLIC;

REVOKE ALL ON FUNCTION app_private.can_view_profile_for_task(uuid)
    FROM anon;

GRANT EXECUTE ON FUNCTION app_private.can_view_profile_for_task(uuid)
    TO authenticated;

-- Ganti kebijakan baca profil.
DROP POLICY IF EXISTS profiles_read ON public.profiles;

CREATE POLICY profiles_read
ON public.profiles
FOR SELECT
TO authenticated
USING (
    id = (SELECT auth.uid())
    OR (SELECT app_private.is_admin())
    OR (SELECT app_private.can_view_profile_for_task(id))
);

COMMIT;

NOTIFY pgrst, 'reload schema';