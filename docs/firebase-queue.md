# Firebase Queue - Dokumentasi

## Flow

```
Controller
  ↓
FirebaseNotification::queue($token, $notification)  // atau queueToTokens / queueToTopic
  ↓
SendFirebaseNotificationJob  (ShouldQueue, onQueue 'notifications')
  ↓
Redis / Database Queue  (sesuai QUEUE_CONNECTION di .env)
  ↓
Queue Worker (php artisan queue:work --queue=notifications)
  ↓
Firebase Cloud Messaging
  ↓
Device
```

## Queue Connection

Baca `.env`:

```
QUEUE_CONNECTION=database   // project saat ini pakai database
```

**JANGAN** otomatis ubah ke `redis` tanpa alasan. Pertahankan `database` queue sesuai `.env` yang sudah berjalan.

Cek config:

```bash
php artisan config:show queue
```

Jika belum ada tabel jobs (untuk database queue):

```bash
php artisan queue:table
php artisan migrate
```

Saat ini migration `0001_01_01_000002_create_jobs_table` sudah include `jobs`, `job_batches`, `failed_jobs`.

## Facade Queue

```php
use App\Facades\FirebaseNotification;
use App\DTO\Notifications\FirebaseNotificationData;

// Single
FirebaseNotification::queue(
    $user->fcm_token,
    FirebaseNotificationData::make(
        title: 'Ticket Diperbarui',
        body: 'Status ticket Anda berubah',
        data: ['type' => 'ticket_update', 'ticket_id' => (string) $ticket->id],
    )
);

// Multiple
FirebaseNotification::queueToTokens([$token1, $token2], $notification);

// Topic
FirebaseNotification::queueToTopic('news', $notification);
```

Controller tidak menunggu Firebase API selesai.

## Job Configuration

`app/Jobs/Notifications/SendFirebaseNotificationJob.php`:

```php
public int $tries = 3;
public int $timeout = 120;

public function backoff(): array
{
    return [10, 30, 60]; // detik antar retry
}

public function failed(Throwable $exception): void
{
    Log::channel('firebase')->error('firebase job permanently failed', [...]);
}
```

- Hanya simpan `token` (string) dan `payload` (array DTO) di property Job — jangan simpan objek Firebase SDK (tidak serializable).
- `handle(FirebaseNotificationService $service)` inject service via container.
- Jika token invalid (`CODE_INVALID_TOKEN`/`CODE_EXPIRED_TOKEN`), job fail fast tanpa retry (panggil `$this->fail()`).
- Topic dispatch: token diawali `topic://` untuk membedakan (mis: `topic://news`).

## Notification Queue Name

Gunakan queue khusus `notifications`:

```php
SendFirebaseNotificationJob::dispatch($token, $notification->toQueuePayload())->onQueue('notifications');
```

Facade sudah otomatis `onQueue('notifications')`.

Keuntungan: queue `notifications` terpisah, worker bisa dedicated `queue:work --queue=notifications`.

## Menjalankan Queue

Database queue default:

```bash
php artisan queue:work
```

Hanya notifications:

```bash
php artisan queue:work --queue=notifications
```

Dengan opsi:

```bash
php artisan queue:work --queue=notifications --tries=3 --timeout=120 --sleep=3
```

Untuk testing sync (langsung proses tanpa worker):

```env
QUEUE_CONNECTION=sync
```

## Failed Jobs

Laravel sudah punya tabel `failed_jobs` (database-uuids, pgsql).

Command:

```bash
php artisan queue:failed              # list failed
php artisan queue:retry all           # retry semua
php artisan queue:retry <uuid>        # retry satu
php artisan queue:forget <uuid>       # delete satu
php artisan queue:flush               # hapus semua failed
```

Jika belum ada tabel (project baru):

```bash
php artisan queue:failed-table
php artisan migrate
```

## Logging

Channel `firebase` log:

- `notification sent` (masked token)
- `notification failed` + `is_invalid_token`
- `multicast sent` (success/failure count)
- `job failed` + `attempt`
- `job permanently failed` di `failed()`

Jangan log `private_key`, `client_email`, `service account JSON`, `access token`. Token selalu di-mask.

## Testing Queue

Gunakan `Queue::fake()` di test:

```php
Queue::fake();
FirebaseNotification::queue($token, $notification);
Queue::assertPushed(SendFirebaseNotificationJob::class);
```

Atau dispatch langsung:

```php
SendFirebaseNotificationJob::dispatch($token, $dto->toQueuePayload())->onQueue('notifications');
```

## Database Queue

- `database` queue: sederhana, tidak butuh Redis, cukup `php artisan queue:work`. Monitoring via `php artisan queue:failed` dan log `storage/logs/firebase.log`.

Project saat ini: `QUEUE_CONNECTION=database` → pakai database queue.

## Troubleshooting Queue

| Masalah | Solusi |
|---|---|
| Job tidak diproses | Pastikan worker jalan: `queue:work --queue=notifications` |
| Job failed terus | Cek `storage/logs/firebase.log` dan `php artisan queue:failed` |
| `queue:work` exit | Gunakan Supervisor (Linux) atau `queue:listen` untuk dev |
| Payload too large | Data FCM max 4KB, pastikan `data` tidak terlalu besar |

## Command Deployment

Di produksi, jangan jalankan `queue:work` manual. Gunakan Supervisor atau systemd untuk keep `queue:work --queue=notifications` tetap jalan.
