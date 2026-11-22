BEGIN;
CREATE POLICY rk_account_avatar_read ON storage.objects FOR SELECT TO authenticated
USING (
 bucket_id='rawatkasih-private'
 AND name ~ '^profiles/[0-9a-f-]{36}/avatar/[0-9a-f-]{36}\\.(jpg|png|webp)$'
 AND (split_part(name,'/',2)=(SELECT auth.uid())::text OR (SELECT app_private.is_admin()))
);
CREATE POLICY rk_account_avatar_upload ON storage.objects FOR INSERT TO authenticated
WITH CHECK (
 bucket_id='rawatkasih-private'
 AND name ~ '^profiles/[0-9a-f-]{36}/avatar/[0-9a-f-]{36}\\.(jpg|png|webp)$'
 AND split_part(name,'/',2)=(SELECT auth.uid())::text
 AND EXISTS (SELECT 1 FROM public.profiles p WHERE p.id=(SELECT auth.uid()) AND p.role='family' AND p.is_active)
);
CREATE OR REPLACE FUNCTION public.set_my_avatar_path(p_path text) RETURNS void
LANGUAGE plpgsql SECURITY DEFINER SET search_path TO ''
AS $$
DECLARE v_user uuid := (SELECT auth.uid());
BEGIN
 IF v_user IS NULL OR NOT EXISTS (SELECT 1 FROM public.profiles p WHERE p.id=v_user AND p.is_active AND p.role='family') THEN
   RAISE EXCEPTION 'account not authorized' USING ERRCODE='42501';
 END IF;
 IF p_path IS NULL OR p_path !~ ('^profiles/' || v_user::text || '/avatar/[0-9a-f-]{36}\\.(jpg|png|webp)$') THEN
   RAISE EXCEPTION 'invalid avatar path' USING ERRCODE='22023';
 END IF;
 IF NOT EXISTS (SELECT 1 FROM storage.objects o WHERE o.bucket_id='rawatkasih-private' AND o.name=p_path) THEN
   RAISE EXCEPTION 'avatar object not found' USING ERRCODE='22023';
 END IF;
 UPDATE public.profiles SET avatar_path=p_path,updated_at=now() WHERE id=v_user;
END;
$$;
REVOKE ALL ON FUNCTION public.set_my_avatar_path(text) FROM PUBLIC,anon;
GRANT EXECUTE ON FUNCTION public.set_my_avatar_path(text) TO authenticated;
DROP POLICY IF EXISTS updates_read ON public.family_updates;
CREATE POLICY updates_read ON public.family_updates FOR SELECT TO authenticated
USING (app_private.can_manage_care(resident_id) OR (status='published' AND app_private.is_family_for(resident_id)));
NOTIFY pgrst,'reload schema';
COMMIT;
