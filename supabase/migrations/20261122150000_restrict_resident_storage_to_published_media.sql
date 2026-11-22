BEGIN;
DROP POLICY IF EXISTS rk_storage_read ON storage.objects;
CREATE POLICY rk_storage_read ON storage.objects FOR SELECT TO authenticated
USING (
  bucket_id = 'rawatkasih-private'
  AND (
    app_private.can_manage_care(app_private.storage_resident_id(name))
    OR (
      app_private.is_family_for(app_private.storage_resident_id(name))
      AND app_private.has_consent(app_private.storage_resident_id(name), 'photo_share')
      AND (
        EXISTS (SELECT 1 FROM public.residents r
                WHERE r.id=app_private.storage_resident_id(name) AND r.profile_photo_path=name)
        OR EXISTS (SELECT 1 FROM public.family_updates u
                   WHERE u.resident_id=app_private.storage_resident_id(name)
                     AND u.photo_path=name AND u.status='published')
      )
    )
  )
);
COMMIT;
