<?php

namespace nostriphant\Transpher;

use nostriphant\Transpher\Nostr\Subscription;

readonly class SQLite implements Relay\Store {

    public function __construct(private \SQLite3 $database) {
        $this->database->exec("CREATE TABLE IF NOT EXISTS event ("
                . "id TEXT PRIMARY KEY ASC,"
                . "pubkey TEXT,"
                . "created_at INTEGER,"
                . "kind INTEGER,"
                . "content TEXT,"
                . "sig TEXT"
                . ")");

        $this->database->exec("CREATE TABLE IF NOT EXISTS tag ("
                . "id INTEGER PRIMARY KEY AUTOINCREMENT,"
                . "event_id TEXT REFERENCES event (id) ON DELETE CASCADE ON UPDATE CASCADE,"
                . "name TEXT"
                . ")");

        $this->database->exec("CREATE TABLE IF NOT EXISTS tag_value ("
                . "position INTEGER,"
                . "tag_id INTEGER REFERENCES tag (id) ON DELETE CASCADE ON UPDATE CASCADE,"
                . "value TEXT,"
                . "UNIQUE (tag_id, position) ON CONFLICT FAIL"
                . ")");
    }

    public function __invoke(Subscription $subscription): array {
        return select($this->events, $subscription);
    }

    public function offsetExists(mixed $offset): bool {
        $query = $this->database->prepare("SELECT id FROM event WHERE id=:event_id LIMIT 1");
        $query->bindValue('event_id', $offset);
        $event = $query->execute()->fetchArray();
        return $event['id'] === $offset;
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->events[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        if (isset($offset)) {
            $this->events[$offset] = $value;
        } else {
            $this->events[] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->events[$offset]);
    }

    public function count(): int {
        return count($this->events);
    }
}
