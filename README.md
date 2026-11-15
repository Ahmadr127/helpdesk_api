php artisan users:import-simutu

psql -U postgres -d helpdesk -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;"

psql -U postgres -d helpdesk -f C:\programming\helpdesk_api\sql\db_helpdesk.sql

php artisan queue:work --queue=default,notifications --tries=3 --timeout=120

php artisan pengajuan:status 56 in_progress --at="2026-11-14 17:56:00" --actor=1

php artisan pengajuan:status 52 ditutup --at="2026-11-14 17:58:00" --actor=1