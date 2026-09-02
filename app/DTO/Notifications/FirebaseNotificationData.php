<?php

namespace App\DTO\Notifications;

use App\Exceptions\FirebaseNotificationException;
use Illuminate\Contracts\Support\Arrayable;

class FirebaseNotificationData implements Arrayable
{
    /**
     * @param string $title   Notification title (required)
     * @param string $body    Notification body (required)
     * @param array  $data    Custom data payload (all values will be cast to string for FCM)
     * @param string|null $image   Optional image URL
     * @param string|null $sound   Optional sound (e.g. "default")
     * @param string|null $channelId Optional Android channel ID
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
        public readonly ?string $image = null,
        public readonly ?string $sound = null,
        public readonly ?string $channelId = null,
    ) {
        $this->validate();
    }

    public static function make(
        string $title,
        string $body,
        array $data = [],
        ?string $image = null,
        ?string $sound = null,
        ?string $channelId = null,
        ?string $channel_id = null,
    ): self {
        // Support both channelId and channel_id naming
        $resolvedChannel = $channelId ?? $channel_id;

        return new self(
            title: $title,
            body: $body,
            data: self::normalizeData($data),
            image: $image,
            sound: $sound,
            channelId: $resolvedChannel,
        );
    }

    /**
     * Normalize data values to string, as required by FCM.
     * Nested arrays are JSON-encoded. Null becomes empty string.
     */
    public static function normalizeData(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            $stringKey = (string) $key;
            if (is_array($value) || is_object($value)) {
                $normalized[$stringKey] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (is_bool($value)) {
                $normalized[$stringKey] = $value ? '1' : '0';
            } elseif (is_null($value)) {
                $normalized[$stringKey] = '';
            } else {
                $normalized[$stringKey] = (string) $value;
            }
        }
        return $normalized;
    }

    private function validate(): void
    {
        if (trim($this->title) === '') {
            throw FirebaseNotificationException::invalidPayload('Title is required and cannot be empty');
        }
        if (trim($this->body) === '') {
            throw FirebaseNotificationException::invalidPayload('Body is required and cannot be empty');
        }
        if (mb_strlen($this->title) > 200) {
            throw FirebaseNotificationException::invalidPayload('Title must not exceed 200 characters');
        }
        if (mb_strlen($this->body) > 1000) {
            throw FirebaseNotificationException::invalidPayload('Body must not exceed 1000 characters');
        }
        foreach ($this->data as $k => $v) {
            if (!is_string($v)) {
                throw FirebaseNotificationException::invalidPayload("Data value for key '{$k}' must be string after normalization");
            }
            if (strlen($k) === 0) {
                throw FirebaseNotificationException::invalidPayload('Data key cannot be empty string');
            }
        }
        if ($this->image !== null && !filter_var($this->image, FILTER_VALIDATE_URL)) {
            throw FirebaseNotificationException::invalidPayload('Image must be a valid URL');
        }
    }

    public function toArray(): array
    {
        return [
            'title'      => $this->title,
            'body'       => $this->body,
            'data'       => $this->data,
            'image'      => $this->image,
            'sound'      => $this->sound,
            'channel_id' => $this->channelId,
        ];
    }

    /**
     * For serialization in queued jobs.
     */
    public function toQueuePayload(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            title: $payload['title'] ?? '',
            body: $payload['body'] ?? '',
            data: self::normalizeData($payload['data'] ?? []),
            image: $payload['image'] ?? null,
            sound: $payload['sound'] ?? null,
            channelId: $payload['channel_id'] ?? $payload['channelId'] ?? null,
        );
    }
}
