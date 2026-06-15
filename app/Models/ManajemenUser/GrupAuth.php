<?php

namespace App\Models\ManajemenUser;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'alias', 'keterangan'])]
class GrupAuth extends Model
{
    protected $table = 'uxui_auth_group';

    public $timestamps = false;

    /**
     * @return HasMany<IzinAuth, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(IzinAuth::class, 'alias_group', 'alias');
    }

    /**
     * @return HasMany<UserAuth, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(UserAuth::class, 'alias_group', 'alias');
    }

    public static function aliasFromName(string $name): string
    {
        return 'group_'.Str::of($name)
            ->squish()
            ->lower()
            ->replace(' ', '_')
            ->toString();
    }
}
