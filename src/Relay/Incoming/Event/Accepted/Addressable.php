<?php

namespace nostriphant\Transpher\Relay\Incoming\Event\Accepted;

use nostriphant\NIP01\Event;

class Addressable {

    public function __construct(
            private \nostriphant\Transpher\Relay\Store $events,
            private \nostriphant\Transpher\Relay\Subscriptions $subscriptions
    ) {

    }

    public function __invoke(Event $event) {
        $this->events[$event->id] = $event;
        $replaceable_events = ($this->events)([
            'kinds' => [$event->kind],
            'authors' => [$event->pubkey],
            '#d' => array_map(fn(array $tag_values) => $tag_values[0], Event::extractTagValues($event, 'd'))
        ]);
        foreach ($replaceable_events as $replaceable_event) {
            if ($replaceable_event === $event) {
                continue;
            }
            $replace_id = $replaceable_event->id;
            if ($replaceable_event->created_at === $event->created_at) {
                $replace_id = max($replaceable_event->id, $event->id);
            }
            unset($this->events[$replace_id]);
        }
        yield from ($this->subscriptions)($event);
    }
}