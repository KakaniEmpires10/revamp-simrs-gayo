<?php

namespace App\Models\ManajemenUser;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['alias_group', 'url'])]
class IzinAuth extends Model
{
    protected $table = 'uxui_auth_permission';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;
}
