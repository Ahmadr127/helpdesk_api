# Firebase FCM - Dokumentasi

## 1. Credential

File credential berada di:

```
storage/app/firebase-auth.json
```

**JANGAN** dipindahkan ke `public/`, `storage/app/public/`, atau `resources/`. File ini tidak dapat diakses via URL karena `storage/app` tidak di-link ke `public/storage` (yang hanya link ke `storage/app/public`).

**JANGAN** menambahkan `.env` seperti `FIREBASE_PROJECT_ID` atau `FCM_DEFAULT_CHANNEL_ID`. Project ID dibaca langsung dari JSON:

```php
config('firebase.credentials') // storage_path('app/firebase-auth.json')
$client->getProjectId() // baca project_id dari JSON
```

`.gitignore` sudah berisi:

```gitignore
/storage/app/firebase-auth.json
/storage/app/private/firebase-auth.json
```

Pastikan tidak commit credential.

## 2. Struktur Architecture

```
HTTP Controller (FirebaseNotificationController)
      ↓
Application / Use Case (App\Application\Notifications\SendFirebaseNotification)
      ↓
Notification Service (App\Services\Notifications\FirebaseNotificationService)
      ↓
Contract / Interface (App\Contracts\Notifications\FirebaseNotificationInterface)
      ↓
Firebase Repository / Gateway (App\Infrastructure\Firebase\FirebaseNotificationRepository)
      ↓
Firebase Client (App\Infrastructure\Firebase\FirebaseClient)
      ↓
Firebase SDK (kreait/firebase-php ^8.4)
```

Semua detail Firebase SDK terisolasi di Repository. Controller tidak mengenal Factory, Messaging, dsb.

## 3. Installation

```bash
composer require kreait/firebase-php:^8.4
composer dump-autoload
```

PHP ^8.3, Laravel ^12.0 compatible.

## 4. Configuration

`config/firebase.php`:

```php
return [
    'credentials' => storage_path('app/firebase-auth.json'),
];
```

Provider terdaftar di `bootstrap/providers.php`:

```php
App\Providers\FirebaseServiceProvider::class,
```

Logging channel `firebase` di `config/logging.php`:

```php
'firebase' => [
    'driver' => 'daily',
    'path' => storage_path('logs/firebase.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 14,
],
```

## 5. DTO

```php
use App\DTO\Notifications\FirebaseNotificationData;

$notification = FirebaseNotificationData::make(
    title: 'Ticket Baru',
    body: 'Ada ticket baru yang harus diproses',
    data: [
        'type' => 'ticket',
        'ticket_id' => (string) $ticket->id,
    ],
    image: 'https://example.com/image.jpg', // optional
    sound: 'default', // optional
    channelId: 'high_importance', // optional, alias channel_id juga didukung
);
```

- `title`, `body` required, max 200/1000 char.
- `data` semua value di-cast ke string (array di-json_encode, bool jadi 1/0, null jadi '') agar aman untuk FCM.
- Validasi image URL, channel_id optional.

## 6. Facade One-Line

```php
use App\Facades\FirebaseNotification;
use App\DTO\Notifications\FirebaseNotificationData;

// Atau global alias tanpa import setelah provider boot: \FirebaseNotification::...

// Synchronous
FirebaseNotification::sendToToken(
    $user->fcm_token,
    FirebaseNotificationData::make(
        title: 'Ticket Baru',
        body: 'Ticket baru telah dibuat',
        data: ['type' => 'ticket', 'ticket_id' => (string) $ticket->id],
    )
);

// Multiple
FirebaseNotification::sendToTokens([$token1, $token2], $notification);

// Topic
FirebaseNotification::sendToTopic('news', $notification);

// Subscribe
FirebaseNotification::subscribeToTopic('news', [$token]);
FirebaseNotification::unsubscribeFromTopic('news', [$token]);
```

Tidak perlu `new Factory()` atau `getMessaging()` di Controller.

## 7. Multiple Token & Topic

Repository mendukung multicast via `sendAll()` dan report `success_count`, `failure_count`, `invalid_tokens`.

Topic validation: regex `^[a-zA-Z0-9\-_\.~%]+$`.

## 8. Error Handling

`App\Exceptions\FirebaseNotificationException` menangani:

- credential missing/invalid
- connection error
- invalid/expired token
- invalid payload
- API/network/topic error

Jangan expose `private_key` di log atau API response. Token di-mask saat log: `abc123****xyz9`.

Contoh catch:

```php
try {
    FirebaseNotification::sendToToken($token, $data);
} catch (FirebaseNotificationException $e) {
    if ($e->getCode() === FirebaseNotificationException::CODE_INVALID_TOKEN) {
        // tandai token invalid, jangan retry
    }
}
```

## 9. Token Invalid

Jika Firebase return `not-registered` / `invalid-registration-token` / `requested entity was not found`:

- log warning dengan masked token
- Job tidak retry (fail fast)
- Jika project memiliki tabel `device_tokens`, tandai/hapus token di `Job::failed()` (lihat komentar di `SendFirebaseNotificationJob::failed()`). Jangan auto-hapus tanpa validasi error memang token invalid.

Titik integrasi documented di `FirebaseNotificationRepository::handleSendException()` dan `SendFirebaseNotificationJob::failed()`.

## 10. Troubleshooting

| Masalah | Solusi |
|---|---|
| `Firebase credential file not found` | Pastikan `storage/app/firebase-auth.json` ada, bukan di `storage/app/public` |
| `Invalid service account JSON` | Cek JSON valid, ada `project_id`, `private_key`, `client_email` |
| `Invalid token` | Cek token length >=10, token dari client FCM terbaru |
| `Topic invalid` | Hanya boleh alfanumerik `-_.~%` |
| `Log channel firebase not found` fallback ke `stack` | Cek `config/logging.php` ada channel `firebase` |
| `Class App\Http\Kernel` psr-4 | Abaikan warning dari `app/Http/babat.php`, tidak terkait |

## 11. Security Checklist

- [ ] firebase-auth.json di storage/app
- [ ] tidak public (bukan di public/storage)
- [ ] ada di .gitignore
- [ ] tidak hardcoded, baca via config
- [ ] tidak ada di .env
- [ ] tidak masuk log
- [ ] tidak masuk API response
- [ ] SDK tidak di Controller

## 12. Tinker Test

```bash
php artisan tinker
```

```php
use App\Facades\FirebaseNotification;
use App\DTO\Notifications\FirebaseNotificationData;

FirebaseNotification::sendToToken(
    'REAL_FCM_TOKEN',
    FirebaseNotificationData::make(title: 'Test', body: 'Hello', data: ['type'=>'test'])
);
```
