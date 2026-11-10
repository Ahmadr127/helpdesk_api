php artisan users:import-simutu

psql -U postgres -d db_helpdesk_prod -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;"

psql -U postgres -d db_helpdesk_prod -f E:\kerjaan\Programming\helpdesk\helpdesk_app_prod\sql\db_helpdesk.sql

php artisan queue:work --queue=default,notifications --tries=3 --timeout=120