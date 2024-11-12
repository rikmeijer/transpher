<?php

use nostriphant\Transpher\Relay\Incoming\Req\Limits;
use nostriphant\Transpher\Relay\Incoming\Constraint\Result;

it('has a maximum number of subscriptions per connected client.', function () {
    $subscriptions = \Pest\subscriptions();

    $limits = Limits::construct(max_per_client: 1);

    $subscription = nostriphant\Transpher\Relay\Condition::makeFiltersFromPrototypes(['ids' => ['a']]);

    $limit = $limits($subscriptions, ['ids' => ['a']]);
    expect($limit->result)->toBe(Result::ACCEPTED, $limit->reason ?? '');

    $subscriptions('sub-id', $subscription);

    $limit = $limits($subscriptions, ['ids' => ['a']]);
    expect($limit->result)->toBe(Result::REJECTED);
    expect($limit->reason)->toBe('max number of subscriptions per client (1) reached');
});

it('has a maximum number of subscriptions per connected client. Defaults to 10.', function () {
    $subscriptions = \Pest\subscriptions();

    $limits = Limits::construct();

    $subscription = nostriphant\Transpher\Relay\Condition::makeFiltersFromPrototypes(['ids' => ['a']]);

    $limit = $limits($subscriptions, ['ids' => ['a']]);
    expect($limit->result)->toBe(Result::ACCEPTED, $limit->reason ?? '');

    $subscriptions('sub-id0', $subscription);
    $subscriptions('sub-id1', $subscription);
    $subscriptions('sub-id2', $subscription);
    $subscriptions('sub-id3', $subscription);
    $subscriptions('sub-id4', $subscription);
    $subscriptions('sub-id5', $subscription);
    $subscriptions('sub-id6', $subscription);
    $subscriptions('sub-id7', $subscription);
    $subscriptions('sub-id8', $subscription);
    $subscriptions('sub-id9', $subscription);

    $limit = $limits($subscriptions, ['ids' => ['a']]);
    expect($limit->result)->toBe(Result::REJECTED);
    expect($limit->reason)->toBe('max number of subscriptions per client (10) reached');
});


it('has a maximum number of subscriptions per connected client. Disabled when set to zero.', function () {
    $subscriptions = \Pest\subscriptions();

    $limits = Limits::construct(max_per_client: 0);

    $subscription = nostriphant\Transpher\Relay\Condition::makeFiltersFromPrototypes(['ids' => ['a']]);

    $limit = $limits($subscriptions, ['ids' => ['a']]);
    expect($limit->result)->toBe(Result::ACCEPTED, $limit->reason ?? '');

    $subscriptions('sub-id0', $subscription);
    $subscriptions('sub-id1', $subscription);
    $subscriptions('sub-id2', $subscription);
    $subscriptions('sub-id3', $subscription);
    $subscriptions('sub-id4', $subscription);
    $subscriptions('sub-id5', $subscription);
    $subscriptions('sub-id6', $subscription);
    $subscriptions('sub-id7', $subscription);
    $subscriptions('sub-id8', $subscription);
    $subscriptions('sub-id9', $subscription);
    $subscriptions('sub-id0', $subscription);
    $subscriptions('sub-id1', $subscription);
    $subscriptions('sub-id2', $subscription);
    $subscriptions('sub-id3', $subscription);
    $subscriptions('sub-id4', $subscription);
    $subscriptions('sub-id5', $subscription);
    $subscriptions('sub-id6', $subscription);
    $subscriptions('sub-id7', $subscription);
    $subscriptions('sub-id8', $subscription);
    $subscriptions('sub-id9', $subscription);

    $limit = $limits($subscriptions, ['ids' => ['a']]);
    expect($limit->result)->toBe(Result::ACCEPTED);
});

it('has a maximum number of subscriptions per connected client, configurable through env-vars. Defaults to 10. Disabled when set to zero.', function () {
    $subscriptions = \Pest\subscriptions();

    putenv('LIMIT_REQ_MAX_PER_CLIENT=1');
    $limits = Limits::fromEnv();

    $subscription = nostriphant\Transpher\Relay\Condition::makeFiltersFromPrototypes(['ids' => ['a']]);

    $limit = $limits($subscriptions, ['ids' => ['a']]);
    expect($limit->result)->toBe(Result::ACCEPTED, $limit->reason ?? '');

    $subscriptions('sub-id', $subscription);

    $limit = $limits($subscriptions, ['ids' => ['a']]);
    expect($limit->result)->toBe(Result::REJECTED);
    expect($limit->reason)->toBe('max number of subscriptions per client (1) reached');
    putenv('LIMIT_REQ_MAX_PER_CLIENT');
});