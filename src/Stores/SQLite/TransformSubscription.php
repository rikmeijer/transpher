<?php

namespace nostriphant\Transpher\Stores\SQLite;

use nostriphant\Transpher\Nostr\Subscription;

class TransformSubscription {

    static function transformToSQL3StatementFactory(Subscription $subscription, string ...$fields): Statement {
        $query_prototype = $subscription([
            'where' => [],
            'limit' => null
        ]);

        $query = "SELECT " . implode(',', $fields) . " FROM event "
                . "LEFT JOIN tag ON tag.event_id = event.id "
                . "LEFT JOIN tag_value ON tag.id = tag_value.tag_id ";

        $parameters = [];
        if (isset($query_prototype['where'])) {
            list($where, $parameters) = array_reduce($query_prototype['where'], function (array $return, array $condition) {
                $return[0][] = array_shift($condition);
                $return[1] = array_merge($return[1], $condition);
                return $return;
            }, [[], []]);
            $query .= "WHERE (" . implode(') AND (', $where) . ") ";
        }

        $query .= 'GROUP BY event.id '
                . (isset($query_prototype['limit']) ? "LIMIT " . $query_prototype['limit'] : "");
        return new Statement($query, $parameters);
    }
}
