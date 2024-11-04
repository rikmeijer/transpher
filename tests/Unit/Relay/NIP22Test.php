<?php

use nostriphant\Transpher\Nostr\Message\Factory;

// https://nostr-nips.com/nip-22
it('SHOULD send the client an OK result saying the non-regular event was not stored for the created_at timestamp not being within the permitted limits.', function () {
    $recipient_past = \Pest\handle($message_past = Factory::eventAt(\Pest\key_sender(), 0, 'Hello World', time() - (60 * 60 * 24) - 5));
    expect($recipient_past)->toHaveReceived(
            ['OK', $message_past()[1]['id'], false, 'invalid:the event created_at field is out of the acceptable range (-24h, +15min) for this relay']
    );

    $recipient_future = \Pest\handle($message_future = Factory::eventAt(\Pest\key_sender(), 0, 'Hello World', time() + (60 * 15) + 5));
    expect($recipient_future)->toHaveReceived(
            ['OK', $message_future()[1]['id'], false, 'invalid:the event created_at field is out of the acceptable range (-24h, +15min) for this relay']
    );
});
