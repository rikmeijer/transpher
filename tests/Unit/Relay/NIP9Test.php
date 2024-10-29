<?php

use rikmeijer\Transpher\Relay;
use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\Message;
use function Pest\context;

$references = [
    'e' => fn(Message $message) => $message()[1]['id'],
    'k' => fn(Message $message) => $message()[1]['kind']
];

foreach ($references as $tag => $value_callback) {
    it('SHOULD delete or stop publishing any referenced (' . $tag . ') events that have an identical pubkey as the deletion request.', function () use ($tag, $value_callback) {
        $context = context();

        $sender_key = Key::generate();
        $message = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
        $referenced_value = $value_callback($message);

        Relay::handle($message, $context);
        expect($context->reply)->toHaveReceived(
                ['OK']
        );

        Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
                ['EVENT', $subscription_id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $subscription_id]
        );

        $delete_event = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 5, 'sent by accident', [$tag, $referenced_value]);
        Relay::handle($delete_event, $context);
        expect($context->reply)->toHaveReceived(
                ['OK', $delete_event()[1]['id'], true]
        );

        expect(isset($context->events[$delete_event()[1]['id']]))->toBeTrue();
        expect(isset($context->events[$message()[1]['id']]))->toBeFalse();

        Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
                ['EVENT', $subscription_id, function (array $event) {
                        expect($event['content'])->toBe('sent by accident');
                        expect($event['kind'])->toBe(5);
                    }],
                ['EOSE', $subscription_id]
        );
    });

    it('SHOULD NOT delete or stop publishing any referenced (' . $tag . ') events that have an different pubkey as the deletion request.', function () use ($tag, $value_callback) {
        $context = context();

        $sender_key = Key::generate();
        $message = \rikmeijer\Transpher\Nostr\Message\Factory::event($sender_key, 1, 'Hello World');
        $referenced_value = $value_callback($message);

        Relay::handle($message, $context);
        expect($context->reply)->toHaveReceived(
                ['OK']
        );

        Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
                ['EVENT', $subscription_id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $subscription_id]
        );

        $delete_event = \rikmeijer\Transpher\Nostr\Message\Factory::event(Key::generate(), 5, 'sent by accident', [$tag, $referenced_value]);
        Relay::handle($delete_event, $context);
        expect($context->reply)->toHaveReceived(
                ['OK', $delete_event()[1]['id'], true]
        );

        expect(isset($context->events[$delete_event()[1]['id']]))->toBeTrue();
        expect(isset($context->events[$message()[1]['id']]))->toBeTrue();

        Relay::handle(json_encode(['REQ', $subscription_id = uniqid(), ['authors' => [$sender_key(Key::public())]]]), $context);
        expect($context->reply)->toHaveReceived(
                ['EVENT', $subscription_id, function (array $event) {
                        expect($event['content'])->toBe('Hello World');
                    }],
                ['EOSE', $subscription_id]
        );
    });
}