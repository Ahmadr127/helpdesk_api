<?php

namespace Tests\Unit\DTO;

use App\DTO\Notifications\FirebaseNotificationData;
use App\Exceptions\FirebaseNotificationException;
use PHPUnit\Framework\TestCase;

class FirebaseNotificationDataTest extends TestCase
{
    public function test_dto_successfully_created(): void
    {
        $dto = FirebaseNotificationData::make(
            title: 'Ticket Baru',
            body: 'Ada ticket baru',
            data: ['type' => 'ticket', 'ticket_id' => '123']
        );

        $this->assertEquals('Ticket Baru', $dto->title);
        $this->assertEquals('Ada ticket baru', $dto->body);
        $this->assertEquals('ticket', $dto->data['type']);
        $this->assertEquals('123', $dto->data['ticket_id']);
        $this->assertNull($dto->image);
        $this->assertNull($dto->sound);
        $this->assertNull($dto->channelId);
    }

    public function test_dto_with_optional_fields(): void
    {
        $dto = FirebaseNotificationData::make(
            title: 'Hello',
            body: 'World',
            data: ['screen' => 'home'],
            image: 'https://example.com/img.jpg',
            sound: 'default',
            channelId: 'high_importance'
        );

        $this->assertEquals('https://example.com/img.jpg', $dto->image);
        $this->assertEquals('default', $dto->sound);
        $this->assertEquals('high_importance', $dto->channelId);
    }

    public function test_dto_channel_id_alias(): void
    {
        $dto = FirebaseNotificationData::make(
            title: 'Hi',
            body: 'Body',
            channel_id: 'my_channel'
        );
        $this->assertEquals('my_channel', $dto->channelId);
    }

    public function test_dto_data_normalization_converts_to_string(): void
    {
        $dto = FirebaseNotificationData::make(
            title: 'Test',
            body: 'Body',
            data: [
                'int' => 123,
                'bool_true' => true,
                'bool_false' => false,
                'null_val' => null,
                'array_val' => ['a' => 1],
            ]
        );

        $this->assertEquals('123', $dto->data['int']);
        $this->assertEquals('1', $dto->data['bool_true']);
        $this->assertEquals('0', $dto->data['bool_false']);
        $this->assertEquals('', $dto->data['null_val']);
        $this->assertJson($dto->data['array_val']);
    }

    public function test_dto_validation_fails_on_empty_title(): void
    {
        $this->expectException(FirebaseNotificationException::class);
        FirebaseNotificationData::make(title: '', body: 'Body');
    }

    public function test_dto_validation_fails_on_empty_body(): void
    {
        $this->expectException(FirebaseNotificationException::class);
        FirebaseNotificationData::make(title: 'Title', body: '');
    }

    public function test_dto_validation_fails_on_invalid_image_url(): void
    {
        $this->expectException(FirebaseNotificationException::class);
        FirebaseNotificationData::make(title: 'T', body: 'B', image: 'not-a-url');
    }

    public function test_dto_to_array_and_from_array(): void
    {
        $dto = FirebaseNotificationData::make(title: 'T', body: 'B', data: ['k' => 'v'], sound: 'default');
        $array = $dto->toArray();
        $restored = FirebaseNotificationData::fromArray($array);

        $this->assertEquals($dto->title, $restored->title);
        $this->assertEquals($dto->body, $restored->body);
        $this->assertEquals($dto->data, $restored->data);
        $this->assertEquals($dto->sound, $restored->sound);
    }

    public function test_dto_to_queue_payload_is_serializable(): void
    {
        $dto = FirebaseNotificationData::make(title: 'T', body: 'B', data: ['type' => 'ticket']);
        $payload = $dto->toQueuePayload();

        $this->assertIsArray($payload);
        // Should survive json encode/decode for queue serialization
        $json = json_encode($payload);
        $decoded = json_decode($json, true);
        $restored = FirebaseNotificationData::fromArray($decoded);
        $this->assertEquals('T', $restored->title);
    }
}
