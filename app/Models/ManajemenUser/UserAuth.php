<?php

namespace App\Models\ManajemenUser;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'id_user', 'alias_group'])]
class UserAuth extends Model
{
    protected $table = 'uxui_auth_users';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * @return BelongsTo<GrupAuth, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(GrupAuth::class, 'alias_group', 'alias');
    }
}
