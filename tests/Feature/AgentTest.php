<?php

use rikmeijer\Transpher\Agent;
use rikmeijer\Transpher\Nostr\Key;
use rikmeijer\Transpher\Nostr\Message\Factory;
use rikmeijer\Transpher\Nostr\Subscription\Filter;
use rikmeijer\TranspherTests\Feature\Functions;

describe('agent', function () : void {
    it('starts relay and sends private direct messsage to relay owner', function () : void {
        $relay = Functions::bootRelay('127.0.0.1:8085', []);

        $agent_key = Key::generate();
        $alice_key = Key::generate();
        $agent = Functions::bootAgent(8084, [
            'RELAY_OWNER_NPUB' => $alice_key(Key::public(\rikmeijer\Transpher\Nostr\Key\Format::BECH32)), 
            'AGENT_NSEC' => $agent_key(Key::private(\rikmeijer\Transpher\Nostr\Key\Format::BECH32)),
            'RELAY_URL' => 'ws://127.0.0.1:8085'
        ]);
        
        $alice = \rikmeijer\TranspherTests\Client::client(8085);
        $subscription = Factory::subscribe(
                new Filter(tags: ['#p' => [$alice_key(Key::public())]])
        );
        $alice->expectNostrEose($subscription()[1]);
        $alice->expectNostrPrivateDirectMessage($subscription()[1], $alice_key, 'Hello, I am your agent! The URL of your relay is ws://127.0.0.1:8085');
        $request = $subscription();
        $alice->json($request);
        expect($request[2])->toBeArray();
        expect($request[2]['#p'])->toContain($alice_key(Key::public()));

        $alice->start();

        $agent();
        $relay();
    });
});
