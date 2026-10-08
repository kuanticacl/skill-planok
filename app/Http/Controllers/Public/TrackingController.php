<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use App\Services\Email\LinkTracker;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackingController extends Controller
{
    /** GIF transparente de 1x1 que registra la apertura. */
    public function open(string $uuid): Response
    {
        $message = EmailMessage::where('uuid', $uuid)->first();

        if ($message && $message->track_opens) {
            $message->forceFill([
                'first_opened_at' => $message->first_opened_at ?? now(),
                'open_count' => $message->open_count + 1,
            ])->save();
            $message->record('opened');
        }

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /** Redirección firmada que registra el clic y envía al destino original. */
    public function click(Request $request, string $uuid): Response
    {
        $url = LinkTracker::resolve($uuid, (string) $request->query('u'), (string) $request->query('s'));

        if (! $url) {
            abort(404);
        }

        $message = EmailMessage::where('uuid', $uuid)->first();
        if ($message) {
            $message->forceFill([
                'first_clicked_at' => $message->first_clicked_at ?? now(),
                'click_count' => $message->click_count + 1,
                'first_opened_at' => $message->first_opened_at ?? now(), // un clic implica apertura
            ])->save();
            $message->record('clicked', ['url' => $url]);
        }

        return redirect()->away($url);
    }
}
