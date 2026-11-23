-- Orang 3: penambahan kolom kunjungan, perubahan status atomik, dan pengajuan idempoten.
ALTER TABLE public.visit_requests
  ADD COLUMN IF NOT EXISTS requested_end_at timestamptz,
  ADD COLUMN IF NOT EXISTS primary_visitor_name text,
  ADD COLUMN IF NOT EXISTS submission_key uuid;
CREATE UNIQUE INDEX IF NOT EXISTS visit_requests_requester_submission_key
  ON public.visit_requests(requester_id, submission_key);

CREATE OR REPLACE FUNCTION app_private.check_visit_change()
RETURNS trigger LANGUAGE plpgsql SECURITY DEFINER SET search_path = '' AS $$
BEGIN
  IF TG_OP = 'INSERT' THEN
    IF auth.uid() IS NULL OR NEW.requester_id <> auth.uid()
       OR NOT app_private.is_family_for(NEW.resident_id)
       OR NOT EXISTS (SELECT 1 FROM public.residents r WHERE r.id=NEW.resident_id AND r.status='active') THEN
      RAISE insufficient_privilege USING MESSAGE = 'Relasi keluarga aktif diperlukan';
    END IF;
    IF NEW.status <> 'pending' OR NEW.reviewed_by IS NOT NULL OR NEW.reviewed_at IS NOT NULL OR NEW.review_note IS NOT NULL THEN
      RAISE SQLSTATE 'PT422' USING MESSAGE = 'Pengajuan baru harus pending tanpa keputusan';
    END IF;
    IF NEW.requested_at <= now() OR NEW.requested_end_at IS NULL OR NEW.requested_end_at <= NEW.requested_at
       OR (NEW.requested_at AT TIME ZONE 'Asia/Jakarta')::date <> (NEW.requested_end_at AT TIME ZONE 'Asia/Jakarta')::date
       OR NEW.submission_key IS NULL OR nullif(btrim(NEW.primary_visitor_name),'') IS NULL
       OR length(btrim(NEW.primary_visitor_name)) NOT BETWEEN 2 AND 120
       OR length(coalesce(NEW.note,'')) > 1000 THEN
      RAISE SQLSTATE 'PT422' USING MESSAGE = 'Tanggal, waktu, nama, atau catatan kunjungan tidak valid';
    END IF;
  ELSE
    IF OLD.status <> 'pending' THEN
      RAISE SQLSTATE 'PT409' USING MESSAGE = 'Pengajuan sudah diputuskan atau dibatalkan';
    END IF;
    IF (NEW.id,NEW.resident_id,NEW.requester_id,NEW.requested_at,NEW.requested_end_at,
        NEW.visitor_count,NEW.primary_visitor_name,NEW.note,NEW.submission_key,NEW.created_at)
       IS DISTINCT FROM
       (OLD.id,OLD.resident_id,OLD.requester_id,OLD.requested_at,OLD.requested_end_at,
        OLD.visitor_count,OLD.primary_visitor_name,OLD.note,OLD.submission_key,OLD.created_at) THEN
      RAISE SQLSTATE 'PT422' USING MESSAGE = 'Detail pengajuan tidak boleh diubah';
    END IF;
    IF NEW.status = 'cancelled' THEN
      IF auth.uid() IS NULL OR OLD.requester_id <> auth.uid() OR NOT app_private.is_family_for(OLD.resident_id) THEN
        RAISE insufficient_privilege USING MESSAGE = 'Hanya keluarga pemilik dapat membatalkan';
      END IF;
      NEW.reviewed_by := NULL; NEW.reviewed_at := NULL; NEW.review_note := NULL;
    ELSIF NEW.status IN ('approved','rejected') THEN
      IF NOT app_private.is_admin() THEN
        RAISE insufficient_privilege USING MESSAGE = 'Hanya admin dapat memutuskan';
      END IF;
      IF NEW.status='approved' AND OLD.requested_at <= now() THEN
        RAISE SQLSTATE 'PT422' USING MESSAGE = 'Jadwal kunjungan sudah lewat';
      END IF;
      NEW.review_note := nullif(btrim(NEW.review_note),'');
      IF (NEW.status='rejected' AND NEW.review_note IS NULL) OR length(coalesce(NEW.review_note,'')) > 1000 THEN
        RAISE SQLSTATE 'PT422' USING MESSAGE = 'Alasan penolakan wajib dan maksimal 1000 karakter';
      END IF;
      NEW.reviewed_by := auth.uid(); NEW.reviewed_at := now();
    ELSE
      RAISE SQLSTATE 'PT422' USING MESSAGE = 'Transisi status tidak valid';
    END IF;
  END IF;
  RETURN NEW;
END;
$$;

CREATE OR REPLACE FUNCTION public.submit_family_visit(
  p_submission_key uuid, p_resident_id uuid, p_requested_at timestamptz,
  p_requested_end_at timestamptz, p_visitor_count integer, p_primary_visitor_name text, p_note text DEFAULT NULL
) RETURNS public.visit_requests LANGUAGE plpgsql SECURITY INVOKER SET search_path = '' AS $$
DECLARE v public.visit_requests;
BEGIN
  IF auth.uid() IS NULL OR NOT app_private.is_family_for(p_resident_id) THEN
    RAISE insufficient_privilege USING MESSAGE = 'Relasi keluarga aktif diperlukan';
  END IF;
  SELECT * INTO v FROM public.visit_requests WHERE requester_id=auth.uid() AND submission_key=p_submission_key;
  IF NOT FOUND THEN
    INSERT INTO public.visit_requests(resident_id,requester_id,requested_at,requested_end_at,visitor_count,primary_visitor_name,note,submission_key)
    VALUES(p_resident_id,auth.uid(),p_requested_at,p_requested_end_at,p_visitor_count,btrim(p_primary_visitor_name),nullif(btrim(p_note),''),p_submission_key)
    ON CONFLICT (requester_id,submission_key) DO NOTHING RETURNING * INTO v;
    IF NOT FOUND THEN
      SELECT * INTO v FROM public.visit_requests WHERE requester_id=auth.uid() AND submission_key=p_submission_key;
    END IF;
  END IF;
  IF NOT FOUND OR (v.resident_id,v.requested_at,v.requested_end_at,v.visitor_count,v.primary_visitor_name,v.note)
    IS DISTINCT FROM (p_resident_id,p_requested_at,p_requested_end_at,p_visitor_count,btrim(p_primary_visitor_name),nullif(btrim(p_note),'')) THEN
    RAISE SQLSTATE 'PT409' USING MESSAGE = 'Kunci pengajuan sudah dipakai dengan detail berbeda';
  END IF;
  RETURN v;
END;
$$;

CREATE OR REPLACE FUNCTION public.cancel_family_visit(p_visit_id uuid)
RETURNS public.visit_requests LANGUAGE plpgsql SECURITY INVOKER SET search_path = '' AS $$
DECLARE v public.visit_requests;
BEGIN
  SELECT * INTO v FROM public.visit_requests WHERE id=p_visit_id FOR UPDATE;
  IF NOT FOUND THEN RAISE SQLSTATE 'PT404' USING MESSAGE='Pengajuan tidak ditemukan'; END IF;
  IF v.requester_id <> auth.uid() OR NOT app_private.is_family_for(v.resident_id) THEN
    RAISE insufficient_privilege USING MESSAGE='Pengajuan bukan milik keluarga';
  END IF;
  IF v.status <> 'pending' THEN RAISE SQLSTATE 'PT409' USING MESSAGE='Pengajuan sudah diputuskan'; END IF;
  UPDATE public.visit_requests SET status='cancelled' WHERE id=p_visit_id RETURNING * INTO v;
  RETURN v;
END;
$$;

CREATE OR REPLACE FUNCTION public.review_family_visit(p_visit_id uuid, p_decision text, p_review_note text DEFAULT NULL)
RETURNS public.visit_requests LANGUAGE plpgsql SECURITY INVOKER SET search_path = '' AS $$
DECLARE v public.visit_requests;
BEGIN
  IF NOT app_private.is_admin() THEN RAISE insufficient_privilege USING MESSAGE='Hanya admin dapat memutuskan'; END IF;
  IF p_decision NOT IN ('approved','rejected') OR p_decision IS NULL THEN
    RAISE SQLSTATE 'PT422' USING MESSAGE='Keputusan tidak valid';
  END IF;
  SELECT * INTO v FROM public.visit_requests WHERE id=p_visit_id FOR UPDATE;
  IF NOT FOUND THEN RAISE SQLSTATE 'PT404' USING MESSAGE='Pengajuan tidak ditemukan'; END IF;
  IF v.status <> 'pending' THEN RAISE SQLSTATE 'PT409' USING MESSAGE='Pengajuan sudah diputuskan'; END IF;
  UPDATE public.visit_requests SET status=p_decision,review_note=p_review_note WHERE id=p_visit_id RETURNING * INTO v;
  RETURN v;
END;
$$;

CREATE OR REPLACE FUNCTION public.family_visit_counts()
RETURNS TABLE(status text,total bigint) LANGUAGE sql STABLE SECURITY INVOKER SET search_path='' AS $$
  SELECT v.status,count(*) FROM public.visit_requests v GROUP BY v.status;
$$;

REVOKE ALL ON FUNCTION public.submit_family_visit(uuid,uuid,timestamptz,timestamptz,integer,text,text) FROM PUBLIC,anon;
REVOKE ALL ON FUNCTION public.cancel_family_visit(uuid) FROM PUBLIC,anon;
REVOKE ALL ON FUNCTION public.review_family_visit(uuid,text,text) FROM PUBLIC,anon;
REVOKE ALL ON FUNCTION public.family_visit_counts() FROM PUBLIC,anon;
GRANT EXECUTE ON FUNCTION public.submit_family_visit(uuid,uuid,timestamptz,timestamptz,integer,text,text) TO authenticated;
GRANT EXECUTE ON FUNCTION public.cancel_family_visit(uuid) TO authenticated;
GRANT EXECUTE ON FUNCTION public.review_family_visit(uuid,text,text) TO authenticated;
GRANT EXECUTE ON FUNCTION public.family_visit_counts() TO authenticated;
