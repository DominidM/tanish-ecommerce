<?php

namespace App\Exceptions;

use RuntimeException;

class InventoryException extends RuntimeException
{
    public static function invalidType(string $type): self
    {
        return new self("Tipo de movimiento no válido: {$type}. Usa entry, exit o adjustment.");
    }

    public static function invalidQuantity(string $message): self
    {
        return new self($message);
    }

    public static function insufficientStock(float $available, float $requested): self
    {
        return new self(
            sprintf('Stock insuficiente: disponible %s, solicitado %s.', $available, $requested)
        );
    }

    public static function missingReason(): self
    {
        return new self('El motivo del movimiento es obligatorio.');
    }
}
