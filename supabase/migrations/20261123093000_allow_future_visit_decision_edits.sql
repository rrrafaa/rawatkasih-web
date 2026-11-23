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
    IF (NEW.id,NEW.resident_id,NEW.requester_id,NEW.requested_at,NEW.requested_end_at,
        NEW.visitor_count,NEW.primary_visitor_name,NEW.note,NEW.submission_key,NEW.created_at)
       IS DISTINCT FROM
       (OLD.id,OLD.resident_id,OLD.requester_id,OLD.requested_at,OLD.requested_end_at,
        OLD.visitor_count,OLD.primary_visitor_name,OLD.note,OLD.submission_key,OLD.created_at) THEN
      RAISE SQLSTATE 'PT422' USING MESSAGE = 'Detail pengajuan tidak boleh diubah';
    END IF;
    IF NEW.status = 'cancelled' THEN
      IF OLD.status <> 'pending' THEN
        RAISE SQLSTATE 'PT409' USING MESSAGE = 'Hanya pengajuan menunggu yang dapat dibatalkan';
      END IF;
      IF auth.uid() IS NULL OR OLD.requester_id <> auth.uid() OR NOT app_private.is_family_for(OLD.resident_id) THEN
        RAISE insufficient_privilege USING MESSAGE = 'Hanya keluarga pemilik dapat membatalkan';
      END IF;
      NEW.reviewed_by := NULL; NEW.reviewed_at := NULL; NEW.review_note := NULL;
    ELSIF NEW.status IN ('approved','rejected') THEN
      IF NOT app_private.is_admin() THEN
        RAISE insufficient_privilege USING MESSAGE = 'Hanya admin dapat memutuskan';
      END IF;
      IF OLD.status NOT IN ('pending','approved','rejected') OR OLD.requested_at <= clock_timestamp() THEN
        RAISE SQLSTATE 'PT409' USING MESSAGE = 'Status kunjungan terkunci';
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
  IF v.status NOT IN ('pending','approved','rejected') OR v.requested_at <= clock_timestamp() THEN
    RAISE SQLSTATE 'PT409' USING MESSAGE='Status kunjungan terkunci';
  END IF;
  UPDATE public.visit_requests SET status=p_decision,review_note=p_review_note WHERE id=p_visit_id RETURNING * INTO v;
  RETURN v;
END;
$$;

