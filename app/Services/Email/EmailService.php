<?php

namespace App\Services\Email;

use App\Jobs\SendEmailMessage;
use App\Models\EmailMessage;
use App\Models\EmailTemplate;
use Illuminate\Support\Carbon;

class EmailService
{
    /**
     * Encola el envío de una plantilla a un destinatario (API, automatizaciones y pruebas).
     *
     * @param  array<string, mixed>  $vars
     * @param  array{subject?: ?string, from_email?: ?string, lead_id?: ?int, client_id?: ?int, api_key_id?: ?int, automation_id?: ?int, kind?: string, track_opens?: bool, track_clicks?: bool, delay_minutes?: int, scheduled_at?: ?Carbon}  $opts
     */
    public function queueTemplate(EmailTemplate $template, string $email, ?string $name, array $vars, array $opts = []): EmailMessage
    {
        $when = $opts['scheduled_at'] ?? (($opts['delay_minutes'] ?? 0) > 0 ? now()->addMinutes($opts['delay_minutes']) : null);

        $message = EmailMessage::create([
            'template_id' => $template->id,
            'kind' => $opts['kind'] ?? 'transactional',
            'to_email' => $email,
            'to_name' => $name,
            'from_email' => $opts['from_email'] ?? null,
            'subject' => $opts['subject'] ?? null,
            'lead_id' => $opts['lead_id'] ?? null,
            'client_id' => $opts['client_id'] ?? null,
            'api_key_id' => $opts['api_key_id'] ?? null,
            'automation_id' => $opts['automation_id'] ?? null,
            'variables' => $vars,
            'status' => 'queued',
            'track_opens' => $opts['track_opens'] ?? false,
            'track_clicks' => $opts['track_clicks'] ?? false,
            'scheduled_at' => $when,
        ]);

        $job = SendEmailMessage::dispatch($message->id);
        if ($when) {
            $job->delay($when);
        }

        return $message;
    }
}
