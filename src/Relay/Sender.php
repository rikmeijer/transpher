<?php

namespace rikmeijer\Transpher\Relay;

/**
 * Description of Replier
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
interface Sender {
    
    public function __invoke(mixed $json) : bool;
    
}
