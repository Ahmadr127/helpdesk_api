# Firebase Horizon - Dokumentasi

## Requirement

- Redis server running (default `127.0.0.1:6379`)
- PHP extensions `ext-pcntl`, `ext-posix` (Linux, tidak tersedia di Windows)
- Laravel Horizon kompatibel dengan Laravel 12: `laravel/horizon ^5.48`

Cek:

```bash
php -v # 8.3.15
php artisan --version # 12.10.2
composer show laravel/framework
```

Project saat ini: `QUEUE_CONNECTION=database` dan Redis belum running (`connection refused` di Windows dev). Horizon **opsional** — tetap bisa pakai database queue tanpa Horizon. Untuk monitoring visual, aktifkan Redis + Horizon.

## Installation

Jika Redis sudah tersedia:

```bash
composer require laravel/horizon
php artisan horizon:install
```

Ini akan:
- publish `config/horizon.php`
- publish assets `public/vendor/horizon`

Jika di Windows dev dan error `ext-pcntl not present`, install dengan:

```bash
composer require laravel/horizon --ignore-platform-reqs
```

Tetapi Horizon hanya bisa run di Linux/produksi.

## Configuration

`config/horizon.php` sudah disediakan (template untuk Redis). Bagian penting:

```php
'environments' => [
    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications'],
            'balance' => 'auto',
            'processes' => 1,
            'tries' => 3,
        ],
    ],
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 5,
            'tries' => 3,
        ],
    ],
],
```

Sesuaikan dengan versi Horizon — jangan copy mentah jika format berbeda. Cek docs `https://laravel.com/docs/12.x/horizon`.

Untuk `QUEUE_CONNECTION`, ganti di `.env` jika ingin Horizon:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Pastikan `config/queue.php` redis connection sudah benar.

## Environment

- `local`: processes 1, auto balance
- `production`: min 1, max 5, balance auto, tries 3

Queue `notifications` dipisah agar bisa monitor notifikasi terpisah.

## Running Horizon

Development:

```bash
php artisan horizon
```

Dashboard:

```
http://localhost:8000/horizon
```

Worker untuk notifikasi saja (tanpa Horizon, via queue worker biasa):

```bash
php artisan queue:work --queue=notifications
php artisan queue:work --queue=default,notifications
```

## Dashboard Monitoring

Horizon dapat monitor:

- pending jobs
- processing jobs
- completed jobs
- failed jobs
- throughput (jobs per minute)
- runtime
- wait time

Pastikan Horizon middleware `web` + auth. Default Horizon guard di `config/horizon.php`:

```php
'middleware' => ['web'],
```

Untuk proteksi, override di `app/Providers/HorizonServiceProvider` (jika di-publish) atau `AuthServiceProvider`:

```php
Horizon::auth(function ($request) {
    return $request->user() && $request->user()->isAdmin();
});
```

Jika route dashboard dilindungi, akses dengan user admin yang sudah login via `web` guard.

## Horizon Control

```bash
php artisan horizon                # start
php artisan horizon:status         # cek status master supervisor
php artisan horizon:pause          # pause semua worker (tidak ambil job baru)
php artisan horizon:continue       # resume
php artisan horizon:terminate      # graceful terminate (akan restart via Supervisor)
php artisan horizon:pause-supervisor supervisor-1
php artisan horizon:continue-supervisor supervisor-1
php artisan horizon:terminate --wait # tunggu job selesai dulu
```

Fungsi:
- `status`: lihat apakah Horizon running
- `pause`: stop ambil job baru, job yang sedang proses tetap jalan
- `continue`: lanjutkan
- `terminate`: stop semua, butuh Supervisor untuk auto-restart (deploy)

## Production Process Manager

Horizon perlu selalu running di produksi. Gunakan Supervisor (Linux).

Buat file:

```
/etc/supervisor/conf.d/laravel-horizon.conf
```

Contoh:

```ini
[program:laravel-horizon]
process_name=%(program_name)s
command=php /path/to/project/artisan horizon
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/horizon.log
stopwaitsecs=3600
```

Sesuaikan:
- `/path/to/project` → path absolut project (mis: `/var/www/helpdesk_app`)
- `user` → user yang menjalankan PHP (www-data, nginx, server233)
- `stdout_logfile` → pastikan writable

Aktivasi:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-horizon
sudo supervisorctl status laravel-horizon
sudo supervisorctl restart laravel-horizon
```

Alternatif systemd (jika tidak pakai Supervisor):

```ini
# /etc/systemd/system/laravel-horizon.service
[Unit]
Description=Laravel Horizon
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/project/artisan horizon
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable laravel-horizon
sudo systemctl start laravel-horizon
sudo systemctl status laravel-horizon
```

## Failed Jobs (Horizon)

Horizon juga punya UI failed jobs, tapi tetap pakai tabel `failed_jobs`:

```bash
php artisan queue:failed
php artisan queue:retry all
php artisan queue:retry <id>
php artisan queue:forget <id>
php artisan queue:flush
```

Di Horizon dashboard, kamu bisa retry/delete per job via UI.

## Monitoring Flow

```
Application
      ↓
FirebaseNotification::queue()
      ↓
SendFirebaseNotificationJob (onQueue notifications)
      ↓
Redis
      ↓
Laravel Horizon (Supervisor: supervisor-1)
      ↓
Worker (balance auto, 1-5 processes)
      ↓
Firebase Cloud Messaging
      ↓
Device
```

Developer dapat melihat di Horizon:

- berapa job masuk (throughput)
- berapa diproses (processed)
- berapa berhasil (completed)
- berapa gagal (failed)
- berapa lama diproses (runtime)
- berapa menunggu (wait time)

## Jika Masih Pakai Database Queue

Jika `QUEUE_CONNECTION=database` (saat ini), Horizon **tidak** akan monitor database queue. Monitoring cukup via:

```bash
php artisan queue:failed
tail -f storage/logs/firebase.log
tail -f storage/logs/laravel.log
```

Horizon hanya untuk Redis. Jika ingin pindah ke Redis:

1. Install & jalankan Redis server
2. `.env` → `QUEUE_CONNECTION=redis`
3. `composer require laravel/horizon`
4. `php artisan horizon:install`
5. Konfigurasi `config/horizon.php` (sudah tersedia)
6. Jalankan `php artisan horizon` atau via Supervisor

## Troubleshooting Horizon

| Masalah | Solusi |
|---|---|
| `Horizon status: inactive` | Jalankan `php artisan horizon` atau Supervisor |
| `Redis connection refused` | Pastikan Redis server running `redis-cli ping` → PONG |
| `ext-pcntl not present` (Windows) | Horizon hanya Linux, di Windows pakai `queue:work` |
| Dashboard 403 | Cek `Horizon::auth` gate, login sebagai admin |
| Job stuck pending | Cek `balance`, `processes`, dan Redis memory |

## Verifikasi

```bash
composer dump-autoload
php artisan optimize:clear
php artisan route:list | findstr firebase
php artisan horizon:status # jika Redis + Horizon installed
php artisan queue:failed
php artisan test --filter=Firebase
```

Tinker queue test:

```php
use App\Facades\FirebaseNotification;
use App\DTO\Notifications\FirebaseNotificationData;

FirebaseNotification::queue(
    'REAL_TOKEN',
    FirebaseNotificationData::make(title: 'Test Queue', body: 'Via Horizon', data: ['type'=>'test'])
);
// Cek Horizon dashboard /horizon atau php artisan queue:failed
```
