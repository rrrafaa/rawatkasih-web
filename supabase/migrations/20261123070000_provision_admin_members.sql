create or replace function app_private.handle_auth_user()
returns trigger
language plpgsql
security definer
set search_path = ''
as $function$
declare
    member_role text := new.raw_app_meta_data->>'rk_member_role';
    resident uuid;
    relation text;
begin
    if member_role is not null and member_role not in ('family', 'caregiver') then
        raise exception 'Invalid member role';
    end if;

    insert into public.profiles(id, full_name, email, role, is_active)
    values (
        new.id,
        coalesce(nullif(btrim(new.raw_user_meta_data->>'full_name'), ''), split_part(new.email, '@', 1)),
        new.email,
        coalesce(member_role, 'family'),
        member_role is not null
    )
    on conflict (id) do nothing;

    -- Metadata aplikasi hanya dapat ditulis melalui API admin Auth.
    if member_role = 'family' then
        resident := (new.raw_app_meta_data->>'rk_resident_id')::uuid;
        relation := nullif(btrim(new.raw_app_meta_data->>'rk_relationship'), '');
        if resident is null or relation is null then
            raise exception 'Resident and relationship are required';
        end if;

        insert into public.family_links(user_id, resident_id, relationship, status)
        values (new.id, resident, relation, 'pending');
    end if;

    return new;
end;
$function$;
