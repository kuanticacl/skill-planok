<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['feature', 'provider', 'model', 'status', 'input_tokens', 'output_tokens', 'duration_ms', 'user_id', 'lead_id', 'error'])]
class AiRun extends Model
{
    //
}
