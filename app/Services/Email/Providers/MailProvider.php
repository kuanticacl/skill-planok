<?php

namespace App\Services\Email\Providers;

use App\Services\Email\OutgoingEmail;
use App\Services\Email\ProviderException;

interface MailProvider
{
    public function name(): string;

    /** @throws ProviderException */
    public function send(OutgoingEmail $email): string;

    /**
     * @param  array<int, OutgoingEmail>  $emails
     * @return array<int, string> ids del proveedor, en el mismo orden
     *
     * @throws ProviderException
     */
    public function sendBatch(array $emails): array;
}
