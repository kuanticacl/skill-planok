<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use App\Services\Email\EmailComposer;

class EmailViewController extends Controller
{
    /** "Ver en el navegador": vuelve a renderizar el correo (sin tracking) con las variables del mensaje. */
    public function show(string $uuid, EmailComposer $composer)
    {
        $message = EmailMessage::where('uuid', $uuid)->firstOrFail();

        if ($message->html && $message->kind !== 'campaign') {
            return response($message->html)->header('Content-Type', 'text/html; charset=utf-8');
        }

        $src = $composer->source($message);
        $vars = [...($message->variables ?? []), ...$composer->systemVariables($message, $src['name'])];
        $content = $composer->renderContent($src['subject'], $src['html'], $vars, $src['preheader']);

        return response($content['html'])->header('Content-Type', 'text/html; charset=utf-8');
    }
}
