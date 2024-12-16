<?php

namespace nostriphant\Transpher\Relay;
use nostriphant\Transpher\Nostr\Subscription;
use function \Functional\if_else;
use \nostriphant\Transpher\Relay\Sender;
use nostriphant\NIP01\Message;
use nostriphant\NIP01\Event;

class Subscriptions {

    private array $subscriptions = [];

    public function __construct(private Sender $relay) {
        
    }

    public function __invoke(mixed ...$args): mixed {
        return match (true) {
            func_num_args() === 0 => count($this->subscriptions),
            $args[0] instanceof Event => self::apply($this->subscriptions, $args[0]),
            is_string($args[0]) && count($args) === 1 => self::unsubscribe($this->subscriptions, $args[0]),
            is_string($args[0]) => self::subscribe($this->subscriptions, $this->relay, $args[0], $args[1]),
        };
    }

    static function apply(array &$subscriptions, Event $event): mixed {
        array_find($subscriptions, function (callable $subscription, string $subscriptionId) use ($event) {
            $to = $subscription($event);
            if ($to === false) {
                return false;
            }
            $to(Message::event($subscriptionId, get_object_vars($event)));
            $to(Message::eose($subscriptionId));
            return true;
        });
        yield Message::ok($event->id, true, '');
    }

    static function subscribe(array &$subscriptions, Sender $relay, string $subscription_id, array $filter_prototypes): void {
        $subscriptions[$subscription_id] = if_else(new Subscription($filter_prototypes, \nostriphant\Transpher\Relay\Condition::class), fn() => $relay, fn() => false);
    }

    static function unsubscribe(array &$subscriptions, string $subscription_id): void {
        unset($subscriptions[$subscription_id]);
    }
}
