<?php

namespace nostriphant\Transpher\Stores\Engine;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Stores\Results;

trait MemoryWrapper {

    readonly private Memory $memory;

    public function __construct(array $events) {
        $this->memory = new Memory($events);
    }

    public function offsetSet(mixed $offset, mixed $event): void {
        $this->memory[$offset] = $event;
    }

    public function offsetUnset(mixed $offset): void {
        unset($this->memory[$offset]);
    }

    public function offsetExists(mixed $offset): bool {
        return isset($this->memory[$offset]);
    }

    public function offsetGet(mixed $offset): ?Event {
        return $this->memory[$offset];
    }

    public function __invoke(array ...$filter_prototypes): Results {
        return call_user_func_array($this->memory, $filter_prototypes);
    }

    public function count(): int {
        return count($this->memory);
    }

    #[\Override]
    public function getIterator(): \Traversable {
        return $this->memory;
    }
}
