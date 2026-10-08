<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use App\Models\EmailSuppression;
use App\Services\Email\MailSettings;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function show(string $uuid)
    {
        $message = EmailMessage::where('uuid', $uuid)->firstOrFail();

        return view('emails.unsubscribe', [
            'email' => $this->mask($message->to_email),
            'company' => MailSettings::companyName(),
            'done' => EmailSuppression::isSuppressed($message->to_email),
            'uuid' => $uuid,
        ]);
    }

    /** Confirma la baja. También responde al "One-Click" de los clientes de correo (RFC 8058). */
    public function store(Request $request, string $uuid)
    {
        $message = EmailMessage::where('uuid', $uuid)->firstOrFail();

        EmailSuppression::add($message->to_email, 'unsubscribed', 'Baja desde el enlace del correo', $message->id);

        if (! $message->unsubscribed_at) {
            $message->update(['unsubscribed_at' => now()]);
            $message->record('unsubscribed');
        }

        if ($request->input('List-Unsubscribe') === 'One-Click') {
            return response('', 200);
        }

        return view('emails.unsubscribe', [
            'email' => $this->mask($message->to_email),
            'company' => MailSettings::companyName(),
            'done' => true,
            'uuid' => $uuid,
        ]);
    }

    private function mask(string $email): string
    {
        [$user, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return substr($user, 0, 2).str_repeat('•', max(1, strlen($user) - 2)).'@'.$domain;
    }
}
