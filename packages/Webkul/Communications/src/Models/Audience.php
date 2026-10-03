<?php

namespace Webkul\Communications\Models;

use Illuminate\Database\Eloquent\Model;

class Audience extends Model
{
    protected $table = 'communication_audiences';

    protected $fillable = ['agency_id', 'code', 'name', 'description', 'rules', 'created_by'];

    protected $casts = ['rules' => 'array'];
}
