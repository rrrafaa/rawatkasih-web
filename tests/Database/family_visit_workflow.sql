-- Run only against an authorized test project. Every mutation below is rolled back.
BEGIN;
SELECT set_config('rk_test.family', (SELECT id::text FROM public.profiles WHERE role='family' AND is_active LIMIT 1), true);
SELECT set_config('rk_test.admin', (SELECT id::text FROM public.profiles WHERE role='admin' AND is_active LIMIT 1), true);
SELECT set_config('rk_test.caregiver', (SELECT id::text FROM public.profiles WHERE role='caregiver' AND is_active LIMIT 1), true);
SELECT set_config('rk_test.resident', (SELECT resident_id::text FROM public.family_links WHERE user_id=current_setting('rk_test.family')::uuid AND status='verified' LIMIT 1), true);
SELECT set_config('rk_test.key', gen_random_uuid()::text, true);
SELECT set_config('rk_test.cancel_key', gen_random_uuid()::text, true);
SELECT set_config('rk_test.start', ((((now() AT TIME ZONE 'Asia/Jakarta')::date + 2) + time '09:00')::timestamp AT TIME ZONE 'Asia/Jakarta')::text, true);
SELECT set_config('request.jwt.claim.sub', current_setting('rk_test.family'), true);
SET LOCAL ROLE authenticated;
DO $$
DECLARE a public.visit_requests; b public.visit_requests; starts timestamptz := current_setting('rk_test.start')::timestamptz;
BEGIN
  a := public.submit_family_visit(current_setting('rk_test.key')::uuid,current_setting('rk_test.resident')::uuid,starts,starts+interval '90 minutes',2,'QA rollback',NULL);
  b := public.submit_family_visit(current_setting('rk_test.key')::uuid,current_setting('rk_test.resident')::uuid,starts,starts+interval '90 minutes',2,'QA rollback',NULL);
  IF a.id <> b.id OR (SELECT count(*) FROM public.visit_requests WHERE submission_key=current_setting('rk_test.key')::uuid) <> 1 THEN RAISE EXCEPTION 'Idempotency failed'; END IF;
  PERFORM set_config('rk_test.visit',a.id::text,true);
  IF a.status <> 'pending' OR a.requester_id <> auth.uid() OR a.reviewed_by IS NOT NULL THEN RAISE EXCEPTION 'Actor/status failed'; END IF;
  BEGIN
    PERFORM public.submit_family_visit(current_setting('rk_test.key')::uuid,current_setting('rk_test.resident')::uuid,starts,starts+interval '90 minutes',3,'QA rollback',NULL);
    RAISE EXCEPTION 'Changed idempotency payload accepted';
  EXCEPTION WHEN SQLSTATE 'PT409' THEN NULL; END;
  BEGIN
    PERFORM public.submit_family_visit(gen_random_uuid(),current_setting('rk_test.resident')::uuid,now()-interval '1 day',now()-interval '23 hours',2,'QA rollback',NULL);
    RAISE EXCEPTION 'Past date accepted';
  EXCEPTION WHEN SQLSTATE 'PT422' THEN NULL; END;
  BEGIN
    PERFORM public.submit_family_visit(gen_random_uuid(),current_setting('rk_test.resident')::uuid,starts,starts+interval '90 minutes',0,'QA rollback',NULL);
    RAISE EXCEPTION 'Zero visitors accepted';
  EXCEPTION WHEN check_violation THEN NULL; END;
  BEGIN
    PERFORM public.review_family_visit(a.id,'approved',NULL);
    RAISE EXCEPTION 'Family reviewed its own visit';
  EXCEPTION WHEN insufficient_privilege THEN NULL; END;
  b := public.submit_family_visit(current_setting('rk_test.cancel_key')::uuid,current_setting('rk_test.resident')::uuid,starts,starts+interval '90 minutes',1,'QA rollback',NULL);
  b := public.cancel_family_visit(b.id);
  IF b.status <> 'cancelled' THEN RAISE EXCEPTION 'Cancellation failed'; END IF;
END $$;
SELECT set_config('request.jwt.claim.sub', current_setting('rk_test.admin'), true);
DO $$
DECLARE a public.visit_requests;
BEGIN
  BEGIN
    PERFORM public.review_family_visit(current_setting('rk_test.visit')::uuid,'rejected','   ');
    RAISE EXCEPTION 'Blank rejection accepted';
  EXCEPTION WHEN SQLSTATE 'PT422' THEN NULL; END;
  a := public.review_family_visit(current_setting('rk_test.visit')::uuid,'approved','QA rollback');
  IF a.status <> 'approved' OR a.reviewed_by <> auth.uid() OR a.reviewed_at IS NULL THEN RAISE EXCEPTION 'Review metadata failed'; END IF;
  BEGIN
    PERFORM public.review_family_visit(a.id,'rejected','second decision');
    RAISE EXCEPTION 'Second decision accepted';
  EXCEPTION WHEN SQLSTATE 'PT409' THEN NULL; END;
  BEGIN
    UPDATE public.visit_requests SET status='pending' WHERE id=a.id;
    RAISE EXCEPTION 'Direct API equivalent bypassed state guard';
  EXCEPTION WHEN SQLSTATE 'PT409' THEN NULL; END;
  IF NOT EXISTS (SELECT 1 FROM public.audit_logs WHERE target_id=a.id AND actor_id=auth.uid() AND action='UPDATE') THEN RAISE EXCEPTION 'Audit missing'; END IF;
END $$;
SELECT set_config('request.jwt.claim.sub', current_setting('rk_test.caregiver'), true);
DO $$ BEGIN
  IF EXISTS (SELECT 1 FROM public.visit_requests WHERE id=current_setting('rk_test.visit')::uuid) THEN RAISE EXCEPTION 'Caregiver read family visit'; END IF;
END $$;
SELECT 'PASS: submission, idempotency, validation, ownership, cancellation, one-time review, direct-update protection, actor/time and audit checks' AS result;
ROLLBACK;
