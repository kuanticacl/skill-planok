<?php

namespace App\Services\Ai;

use RuntimeException;

class AiNotConfigured extends RuntimeException
{
    public function __construct(string $message = 'No hay un proveedor de IA activo. Configúralo en Inteligencia artificial → Proveedores.')
    {
        parent::__construct($message);
    }
}
