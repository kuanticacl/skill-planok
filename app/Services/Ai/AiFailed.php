<?php

namespace App\Services\Ai;

use RuntimeException;

/** Falla de una llamada a IA con un mensaje apto para mostrar al usuario. */
class AiFailed extends RuntimeException {}
