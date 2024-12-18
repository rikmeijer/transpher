<?php

namespace nostriphant\Transpher\Stores\Engine\SQLite;

readonly class Condition {

    public function __construct(private Condition\Test $test) {
        
    }

    public function __invoke(array $query): array {
        return call_user_func($this->test, $query);
    }

    static function pubkey(mixed $expected_value) {
        return self::scalar('pubkey', $expected_value);
    }

    static function id(mixed $expected_value) {
        return self::scalar('id', $expected_value);
    }

    static function kind(mixed $expected_value) {
        return self::scalar('kind', $expected_value);
    }

    static function scalar(string $event_field, mixed $expected_value): self {
        return new self(new Condition\Scalar($event_field, $expected_value));
    }

    static function until(mixed $expected_value): self {
        return new self(new Condition\Until('created_at', $expected_value));
    }

    static function since(mixed $expected_value): self {
        return new self(new Condition\Since('created_at', $expected_value));
    }

    static function tag(string $tag, mixed $expected_value): self {
        return new self(new Condition\Tag($tag, $expected_value));
    }

    static function limit(int $expected_value): self {
        return new self(new Condition\Limit($expected_value));
    }

    static function __callStatic(string $name, array $arguments): self {
        return self::tag(ltrim($name, '#'), ...$arguments);
    }

    static function makeConditions(array $filter_prototypes): callable {
        $mapper = new \nostriphant\Transpher\Relay\Conditions(__CLASS__);
        return $mapper($filter_prototypes);
    }

    static function wrapFilters(array $filters): callable {
        return fn(array $query): array => array_reduce($filters, fn(array $query, callable $filter) => $filter($query), $query);
    }

    static function makeFilter(self ...$conditions) {
        return fn(array $query): array => array_reduce($conditions, fn(array $query, self $condition) => $condition($query), $query);
    }
}