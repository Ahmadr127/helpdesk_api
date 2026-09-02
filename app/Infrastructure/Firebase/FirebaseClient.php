<?php

namespace App\Infrastructure\Firebase;

use App\Exceptions\FirebaseNotificationException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;

class FirebaseClient
{
    private mixed $messagingInstance = null;
    private ?Factory $factoryInstance = null;

    public function __construct(
        private readonly ?string $credentialsPath = null
    ) {
    }

    /**
     * Get Firebase Messaging instance.
     *
     * @throws FirebaseNotificationException
     */
    public function messaging(): mixed
    {
        if ($this->messagingInstance !== null) {
            return $this->messagingInstance;
        }

        $path = $this->credentialsPath ?? config('firebase.credentials');

        if (empty($path)) {
            throw FirebaseNotificationException::credentialMissing('(empty config firebase.credentials)');
        }

        // Resolve storage_path if not absolute
        if (!file_exists($path)) {
            throw FirebaseNotificationException::credentialMissing($path);
        }

        try {
            $json = file_get_contents($path);
            if ($json === false) {
                throw new \RuntimeException('Unable to read credential file');
            }
            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($decoded['project_id'], $decoded['private_key'], $decoded['client_email'])) {
                throw new \RuntimeException('Invalid service account JSON structure');
            }
        } catch (FirebaseNotificationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw FirebaseNotificationException::credentialInvalid($e->getMessage(), $e);
        }

        try {
            $factory = (new Factory())->withServiceAccount($path);
            $this->factoryInstance = $factory;
            $this->messagingInstance = $factory->createMessaging();
        } catch (\Throwable $e) {
            throw FirebaseNotificationException::connectionError($e->getMessage(), $e);
        }

        return $this->messagingInstance;
    }

    /**
     * Get Factory instance (for testing or advanced usage).
     */
    public function factory(): Factory
    {
        $this->messaging(); // ensure initialized
        return $this->factoryInstance;
    }

    /**
     * Get project ID from credential file without exposing full file.
     */
    public function getProjectId(): ?string
    {
        $path = $this->credentialsPath ?? config('firebase.credentials');
        if (!file_exists($path)) {
            return null;
        }
        $json = @file_get_contents($path);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        return $data['project_id'] ?? null;
    }

    /**
     * For testing: allow injecting mock messaging.
     */
    public function setMessagingForTesting(mixed $messaging): void
    {
        $this->messagingInstance = $messaging;
    }
}
