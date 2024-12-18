<?php

describe('CLOSE', function () {
    it('responds with a NOTICE on missing subscription-id', function () {
        $recipient = \Pest\handle(new \nostriphant\NIP01\Message('CLOSE'));

        expect($recipient)->toHaveReceived(
                ['NOTICE', 'Missing subscription ID']
        );
    });
});
