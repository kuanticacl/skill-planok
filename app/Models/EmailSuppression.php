<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'reason', 'note', 'email_message_id'])]
class EmailSuppression extends Model
{
    public const REASONS = [
        'unsubscribed' => 'Se dio de baja',
        'bounced' => 'Rebote',
        'complained' => 'Marcó como spam',
        'manual' => 'Manual',
    ];

    public static function isSuppressed(string $email): bool
    {
        return static::where('email', strtolower(trim($email)))->exists();
    }

    public static function add(string $email, string $reason, ?string $note = null, ?int $messageId = null): self
    {
        return static::firstOrCreate(
            ['email' => strtolower(trim($email))],
            ['reason' => $reason, 'note' => $note, 'email_message_id' => $messageId],
        );
    }
}
