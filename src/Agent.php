<?php

namespace nostriphant\Transpher;
use nostriphant\Transpher\Client;
use Psr\Log\LoggerInterface;
use nostriphant\NIP01\Key;
use nostriphant\NIP19\Bech32;
use function \Amp\trapSignal;

readonly class Agent {

    public function __construct(private Client $client, #[\SensitiveParameter] private Key $key, private Bech32 $relay_owner_npub) {
        
    }
    
    public function __invoke(LoggerInterface $log): callable {
        $log->info('Client connecting to ' . $this->client->url);

        $log->info('Running agent with public key ' . Bech32::npub(($this->key)(Key::public())));
        $log->info('Sending Private Direct Message event');
        $this->client->privateDirectMessage($this->key, call_user_func($this->relay_owner_npub), 'Hello, I am your agent! The URL of your relay is {relay_url}');

        $log->info('Listening to relay...');
        $this->client->start(0);

        $signal = trapSignal([SIGINT, SIGTERM]);

        $log->info(sprintf("Received signal %d, stopping Relay server", $signal));

        $this->client->stop();
        $log->info('Done');
    }
}
