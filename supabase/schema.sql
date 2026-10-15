-- Jalankan sekali di Supabase: SQL Editor -> New query -> paste -> Run.
-- Buat user dulu di Authentication > Users, lalu jadikan admin:
--   update profiles set role='admin' where id='UUID_USER';

create table if not exists profiles (
  id uuid primary key references auth.users on delete cascade,
  role text not null default 'user' check (role in ('user','admin'))
);
create or replace function handle_new_user() returns trigger language plpgsql security definer as $$
begin insert into profiles(id) values (new.id); return new; end $$;
drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created after insert on auth.users for each row execute function handle_new_user();
create or replace function is_admin() returns boolean language sql security definer stable as $$
  select exists(select 1 from profiles where id = auth.uid() and role = 'admin') $$;

-- Registrasi alat (CRUD oleh admin). "last" = pembacaan terakhir (jsonb).
create table if not exists devices (
  id text primary key,            -- ID alat, mis. AIR-01
  name text not null,
  loc_code text not null,         -- kode lokasi
  last jsonb,
  last_seen timestamptz,
  created_at timestamptz not null default now()
);
-- Credential alat: admin hanya boleh INSERT, tidak bisa dibaca dari web.
create table if not exists device_keys (
  device_id text primary key references devices(id) on delete cascade,
  credential text not null
);

create table if not exists water_logs (
  id bigint generated always as identity primary key,
  created_at timestamptz not null default now(),   -- timestamp dari alat (RTC)
  device_id text not null references devices(id) on delete cascade,
  loc_code text,
  ph real, ec real, tds real, "do" real, temp real, turb real
);
create index if not exists water_logs_dev_idx on water_logs (device_id, created_at desc);

alter table profiles enable row level security;
alter table devices enable row level security;
alter table device_keys enable row level security;
alter table water_logs enable row level security;

create policy "baca profil sendiri" on profiles for select to authenticated using (id = auth.uid());
create policy "login baca alat" on devices for select to authenticated using (true);
create policy "admin tambah alat" on devices for insert to authenticated with check (is_admin());
create policy "admin ubah alat" on devices for update to authenticated using (is_admin());
create policy "admin hapus alat" on devices for delete to authenticated using (is_admin());
create policy "admin tambah credential" on device_keys for insert to authenticated with check (is_admin());
create policy "login baca log" on water_logs for select to authenticated using (true);
-- Tulis log/last_seen hanya dari server (service_role, melewati RLS).
