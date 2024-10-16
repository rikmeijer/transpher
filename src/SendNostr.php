<?php

namespace rikmeijer\Transpher;

use Amp\Websocket\WebsocketClient;
use Psr\Log\LoggerInterface;
use rikmeijer\Transpher\Relay\Sender;

/**
 * Description of Reply
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class SendNostr implements Sender {
    
    private function __construct(private string $action, private WebsocketClient $client, private LoggerInterface $log) {}
    
    #[\Override]
    public function __invoke(mixed $json) : bool {
        $text = \rikmeijer\Transpher\Nostr::encode($json);
        $this->log->info($this->action . ' message ' . $text);
        $this->client->sendText($text);
        return true;
    }
    
    static function __callStatic(string $name, array $arguments): mixed {
        return new self(ucwords($name), ...$arguments);
    }
    
}