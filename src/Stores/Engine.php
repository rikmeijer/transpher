<?php

namespace nostriphant\Transpher\Stores;

use nostriphant\NIP01\Event;
use nostriphant\Transpher\Stores\Results;

interface Engine extends \ArrayAccess, \Countable, \IteratorAggregate {

    public function __invoke(array ...$filter_prototypes): Results;

    #[\ReturnTypeWillChange]
    #[\Override]
    public function offsetGet(mixed $offset): ?Event;
}