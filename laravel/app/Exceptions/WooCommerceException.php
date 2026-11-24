<?php

namespace App\Exceptions;

use RuntimeException;

class WooCommerceException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self(
            'La integración con WooCommerce no está configurada. Define WOOCOMMERCE_URL, WOOCOMMERCE_KEY y WOOCOMMERCE_SECRET.'
        );
    }

    public static function requestFailed(string $method, string $endpoint, int $status, string $body): self
    {
        $body = mb_substr($body, 0, 500);

        return new self("WooCommerce respondió {$status} a {$method} {$endpoint}: {$body}", $status);
    }
}
