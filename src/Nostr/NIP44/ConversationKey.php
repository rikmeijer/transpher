<?php

namespace rikmeijer\Transpher\Nostr\NIP44;
use rikmeijer\Transpher\Nostr\NIP44\MessageKeys;
use rikmeijer\Transpher\Key;
use \rikmeijer\Transpher\Nostr\NIP44;


/**
 * Description of ConversationKey
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
readonly class ConversationKey {
    private string $conversation_key;
    public function __construct(#[\SensitiveParameter] Key $recipient_key, string $sender_pubkey) {
        if (false === ($secret = $recipient_key(Key::sharedSecret('02' . bin2hex($sender_pubkey))))) {
            return false;
        }
        $this->conversation_key = NIP44::hash('nip44-v2')(hex2bin($secret));
    }
    public function __invoke(): MessageKeys {
        return new MessageKeys($this->conversation_key);
    }
    public function __toString(): string {
        return $this->conversation_key;
    }
}
