REVOKE EXECUTE ON FUNCTION public.rls_auto_enable() FROM PUBLIC, anon, authenticated;

CREATE OR REPLACE FUNCTION public.update_my_profile(p_full_name text, p_phone text DEFAULT NULL::text)
RETURNS void LANGUAGE plpgsql SECURITY DEFINER SET search_path TO '' AS $function$
BEGIN
  IF auth.uid() IS NULL OR nullif(btrim(p_full_name),'') IS NULL THEN
    RAISE EXCEPTION 'Login dan nama valid diperlukan';
  END IF;
  UPDATE public.profiles SET full_name=btrim(p_full_name), phone=p_phone
  WHERE id=auth.uid() AND is_active;
  IF NOT FOUND THEN RAISE EXCEPTION 'Profil aktif tidak ditemukan'; END IF;
END;
$function$;
REVOKE EXECUTE ON FUNCTION public.update_my_profile(text,text) FROM PUBLIC, anon;
GRANT EXECUTE ON FUNCTION public.update_my_profile(text,text) TO authenticated;
