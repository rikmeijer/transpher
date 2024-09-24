<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace Transpher;
use Elliptic\EC;

/**
 * Description of Key
 *
 * @author Rik Meijer <hello@rikmeijer.nl>
 */
class Key {
    
    static function generate(): callable
    {
        $ec = new EC('secp256k1');
        $key = $ec->genKeyPair();
        return self::private($key->priv->toString('hex'));
    }
    
    static function private(string $private_key) : callable {
        return fn(callable $input) => $input($private_key);
    }
    
    static function signer(string $message) : callable {
        return fn(string $private_key) => (new \Mdanter\Ecc\Crypto\Signature\SchnorrSignature())->sign($private_key, $message)['signature'];
    }
    
    static function sharedSecret(string $recipient_pubkey) {
        return function(string $private_key) use ($recipient_pubkey) : string {
            $ec = new EC('secp256k1');
            $key1 = $ec->keyFromPrivate($private_key, 'hex');
            $pub2 = $ec->keyFromPublic($recipient_pubkey, 'hex')->pub;
            return hex2bin($key1->derive($pub2)->toString('hex'));
        };
        
    }
    
    static function public() : callable {
        return function(string $private_key): string {
            $ec = new EC('secp256k1');
            $private_key = $ec->keyFromPrivate($private_key);
            $public_hex = $private_key->getPublic(true, 'hex');
            return $public_hex;
        };
    }
    
}
