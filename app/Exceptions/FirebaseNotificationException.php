<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class FirebaseNotificationException extends Exception
{
    public const CODE_CREDENTIAL_MISSING = 1001;
    public const CODE_CREDENTIAL_INVALID = 1002;
    public const CODE_CONNECTION_ERROR   = 1003;
    public const CODE_INVALID_TOKEN      = 1004;
    public const CODE_EXPIRED_TOKEN      = 1005;
    public const CODE_INVALID_PAYLOAD    = 1006;
    public const CODE_API_ERROR          = 1007;
    public const CODE_NETWORK_ERROR      = 1008;
    public const CODE_TOPIC_ERROR        = 1009;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return $this->context;
    }

    public static function credentialMissing(string $path, ?Throwable $previous = null): self
    {
        return new self(
            message: "Firebase credential file not found at: {$path}",
            code: self::CODE_CREDENTIAL_MISSING,
            previous: $previous,
            context: ['path' => $path]
        );
    }

    public static function credentialInvalid(string $reason, ?Throwable $previous = null): self
    {
        return new self(
            message: "Firebase credential invalid: {$reason}",
            code: self::CODE_CREDENTIAL_INVALID,
            previous: $previous,
            context: ['reason' => $reason]
        );
    }

    public static function connectionError(string $reason, ?Throwable $previous = null): self
    {
        return new self(
            message: "Firebase connection error: {$reason}",
            code: self::CODE_CONNECTION_ERROR,
            previous: $previous,
            context: ['reason' => $reason]
        );
    }

    public static function invalidToken(string $maskedToken, ?Throwable $previous = null): self
    {
        return new self(
            message: "Invalid FCM token: {$maskedToken}",
            code: self::CODE_INVALID_TOKEN,
            previous: $previous,
            context: ['token' => $maskedToken]
        );
    }

    public static function expiredToken(string $maskedToken, ?Throwable $previous = null): self
    {
        return new self(
            message: "Expired / not registered FCM token: {$maskedToken}",
            code: self::CODE_EXPIRED_TOKEN,
            previous: $previous,
            context: ['token' => $maskedToken]
        );
    }

    public static function invalidPayload(string $reason, ?Throwable $previous = null): self
    {
        return new self(
            message: "Invalid FCM payload: {$reason}",
            code: self::CODE_INVALID_PAYLOAD,
            previous: $previous,
            context: ['reason' => $reason]
        );
    }

    public static function apiError(string $reason, ?Throwable $previous = null): self
    {
        return new self(
            message: "Firebase API error: {$reason}",
            code: self::CODE_API_ERROR,
            previous: $previous,
            context: ['reason' => $reason]
        );
    }

    public static function networkError(string $reason, ?Throwable $previous = null): self
    {
        return new self(
            message: "Firebase network error: {$reason}",
            code: self::CODE_NETWORK_ERROR,
            previous: $previous,
            context: ['reason' => $reason]
        );
    }

    public static function topicError(string $reason, ?Throwable $previous = null): self
    {
        return new self(
            message: "Firebase topic error: {$reason}",
            code: self::CODE_TOPIC_ERROR,
            previous: $previous,
            context: ['reason' => $reason]
        );
    }

    /**
     * Detect if error is due to invalid / not registered token.
     */
    public static function isInvalidTokenError(Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        $indicators = [
            'not-registered',
            'not registered',
            'invalid-registration-token',
            'invalid registration token',
            'invalid argument',
            'registration-token-not-registered',
            'requested entity was not found',
        ];

        foreach ($indicators as $needle) {
            if (str_contains($msg, $needle)) {
                return true;
            }
        }

        // Kreait specific exception class names
        $class = get_class($e);
        if (str_contains($class, 'NotFound') || str_contains($class, 'InvalidArgument')) {
            // Check message still, but treat as token error when message mentions token
            if (str_contains($msg, 'token')) {
                return true;
            }
        }

        return false;
    }
}
