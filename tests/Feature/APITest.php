<?php

use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\MessageFactory;
use rikmeijer\TranspherTests\Client;

$main_relay;
beforeAll(function() use (&$main_relay) {
    $main_relay = \rikmeijer\Transpher\Relay::boot('127.0.0.1:8081', []);
});
afterAll(function() use (&$main_relay) {
    $main_relay();
});

describe('relay', function () {
    
    it('sends events to all clients subscribed on event id', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $alice_key = Key::generate();
        $note1 = MessageFactory::rumor($alice_key(Key::public()), 1, 'Hello worlda!');
        $alice->sendSignedMessage($note1($alice_key));

        $key_charlie = Key::generate();
        $note2 = MessageFactory::rumor($key_charlie(Key::public()), 1, 'Hello worldi!');
        $note2_signed = $note2($key_charlie);
        $alice->sendSignedMessage($note2_signed);

        $subscription = MessageFactory::subscribe();

        $bob->expectNostrEvent($subscription()[1], 'Hello worldi!');
        $bob->expectNostrEose($subscription()[1]);
        $bob->json(MessageFactory::filter($subscription, ids: [$note2_signed[1]['id']])());
        $bob->start();
    });

    it('sends events to all clients subscribed on author (pubkey)', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();

        $alice_key = Key::generate();
        $note = MessageFactory::rumor($alice_key(Key::public()), 1, 'Hello world!');
        $alice->sendSignedMessage($note($alice_key));
        $subscription = MessageFactory::subscribe();

        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $request = MessageFactory::filter($subscription, authors: [$alice_key(Key::public())])();
        $bob->json($request);
        $bob->start();
    });
    
    
    it('sends events to Charly who uses two filters in their subscription', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();
        $charlie = Client::generic_client();

        $alice_key = Key::generate();
        $alice_note = MessageFactory::rumor($alice_key(Key::public()), 1, 'Hello world, from Alice!');
        $alice->sendSignedMessage($alice_note($alice_key));
        
        $bob_key = Key::generate();
        $bob_note = MessageFactory::rumor($bob_key(Key::public()), 1, 'Hello world, from Bob!');
        $alice->sendSignedMessage($bob_note($bob_key));
        
        $subscription = MessageFactory::subscribe();
        $charlie->expectNostrEvent($subscription()[1], 'Hello world, from Alice!');
        $charlie->expectNostrEvent($subscription()[1], 'Hello world, from Bob!');
        $charlie->expectNostrEose($subscription()[1]);

        $request_alice = MessageFactory::filter($subscription, authors: [$alice_key(Key::public())]);
        $request_bob = MessageFactory::filter($request_alice, authors: [$bob_key(Key::public())]);
        $charlie->json($request_bob());
        $charlie->start();
    });

    it('sends events to all clients subscribed on p-tag', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();
        $alice_key = Key::generate();

        $note = MessageFactory::rumor($alice_key(Key::public()), 1, 'Hello world!', ['p', 'randomPTag']);
        $alice->sendSignedMessage($note($alice_key));
        $subscription = MessageFactory::subscribe();

        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $request = MessageFactory::filter($subscription, tags: ['#p' => ['randomPTag']])();
        $bob->json($request);
        $bob->start();
    });
    
    it('closes subscription and stop sending events to subscribers', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();
        $alice_key = Key::generate();

        $note = MessageFactory::rumor($alice_key(Key::public()), 1, 'Hello world!');
        $alice->sendSignedMessage($note($alice_key));

        $subscription = MessageFactory::subscribe();
        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $request = MessageFactory::filter($subscription, authors: [$alice_key(Key::public())])();
        $bob->json($request);
        $bob->start();

        $bob->expectNostrClosed($subscription()[1], '');

        $request = MessageFactory::close($subscription);
        $bob->send($request);
        $bob->start();
    });

    it('sends events to all clients subscribed on author (pubkey), even after restarting the server', function () {
        $env = [
            'TRANSPHER_STORE' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid()
        ];
        mkdir($env['TRANSPHER_STORE']);
        
        
        $server = \rikmeijer\Transpher\Relay::boot('127.0.0.1:8082', $env);
        $alice = Client::client(8082);

        $alice_key = Key::generate();

        $note = MessageFactory::rumor($alice_key(Key::public()), 1, 'Hello wirld!');
        $alice->sendSignedMessage($note($alice_key));

        $status = $server();
        expect($status)->toBeArray();
        expect($status['running'])->toBeFalse();

        $server = \rikmeijer\Transpher\Relay::boot('127.0.0.1:8082', $env);
        
        $bob = Client::client(8082);
        $subscription = MessageFactory::subscribe();

        $bob->expectNostrEvent($subscription()[1], 'Hello wirld!');
        $bob->expectNostrEose($subscription()[1]);

        $request = MessageFactory::filter($subscription, authors: [$alice_key(Key::public())])();
        $bob->json($request);
        $bob->start();

        $status = $server();
        expect($status)->toBeArray();
        expect($status['running'])->toBeFalse();
    });

    it('sends events to all clients subscribed on kind', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();
        $alice_key = Key::generate();
        
        $note = MessageFactory::rumor($alice_key(Key::public()), 3, 'Hello world!');
        $alice->sendSignedMessage($note($alice_key));

        $subscription = MessageFactory::subscribe();

        $bob->expectNostrEvent($subscription()[1], 'Hello world!');
        $bob->expectNostrEose($subscription()[1]);

        $bob->json(MessageFactory::filter($subscription, kinds: [3])());
        $bob->start();
    });

    it('relays events to Bob, sent after they subscribed on Alices messages', function () {
        $alice = Client::generic_client();
        $bob = Client::generic_client();
        $alice_key = Key::generate();

        $subscription = MessageFactory::subscribe();

        $bob->expectNostrEose($subscription()[1]);
        $request = MessageFactory::filter($subscription, authors: [$alice_key(Key::public())])();
        $bob->json($request);
        $bob->start();

        $note1 = MessageFactory::rumor($alice_key(Key::public()), 1, 'Relayable Hello worlda!');
        $alice->sendSignedMessage($note1($alice_key));

        $key_charlie = Key::generate();
        $note2 = MessageFactory::rumor($key_charlie(Key::public()), 1, 'Hello worldi!');
        $alice->sendSignedMessage($note2($key_charlie));

        $bob->expectNostrEvent($subscription()[1], 'Relayable Hello worlda!');
        $bob->expectNostrEose($subscription()[1]);
        $bob->start();
    });
    
    
    it('sends an information document (NIP-11), when on a HTTP request', function() {
        $owner_key = Key::generate();
        $agent_key = Key::generate();
        
        $relay = \rikmeijer\Transpher\Relay::boot('127.0.0.1:8087', [
            'AGENT_NSEC' => $agent_key(Key::private(\rikmeijer\Transpher\Nostr\Key\Format::BECH32)),
            'RELAY_URL' => 'ws://127.0.0.1:8087',
            'RELAY_OWNER_NPUB' => $owner_key(Key::public(\rikmeijer\Transpher\Nostr\Key\Format::BECH32)), 
            'RELAY_NAME' => 'Really relay',
            'RELAY_DESCRIPTION' => 'This is my dev relay',
            'RELAY_CONTACT' => 'nostr@rikmeijer.nl'
        ]);


        $curl = curl_init('http://localhost:8087');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Accept: application/nostr+json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $responseText = curl_exec($curl);
        expect($responseText)->not()->toBeFalse('['. curl_errno($curl).'] ' . curl_error($curl));
        expect($responseText)->not()->toContain('<b>Warning</b>');
        $response = \rikmeijer\Transpher\Nostr::decode($responseText);

        expect($response)->not()->toBeNull($responseText);
        expect($response)->toBe([
             "name" => 'Really relay',
             "description" => 'This is my dev relay',
             "pubkey" => $owner_key(Key::public(\rikmeijer\Transpher\Nostr\Key\Format::HEXIDECIMAL)),
             "contact" => "nostr@rikmeijer.nl",
             "supported_nips" => [1, 11],
             "software" => 'Transpher',
             "version" => 'dev'
        ]);

        $relay();
    });
});
