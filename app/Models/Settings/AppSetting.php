<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    public const BpjsAntrolTaskIdEnabledKey = 'bpjs_antrol_task_id_enabled';

    protected $table = 'app_settings';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function bpjsAntrolTaskIdEnabled(): bool
    {
        return static::boolean(self::BpjsAntrolTaskIdEnabledKey, true);
    }

    public static function setBpjsAntrolTaskIdEnabled(bool $enabled): self
    {
        return static::setValue(self::BpjsAntrolTaskIdEnabledKey, $enabled, 'integrasi_bpjs');
    }

    public static function value(string $key, ?string $default = null): ?string
    {
        if ($key === '') {
            return $default;
        }

        return static::query()
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public static function setValue(string $key, string|int|bool|null $value, ?string $group = null): self
    {
        return static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => static::stringValue($value),
                'group' => $group,
            ],
        );
    }

    public static function boolean(string $key, bool $default = false): bool
    {
        $value = static::value($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    private static function stringValue(string|int|bool|null $value): ?string
    {
        return match (true) {
            is_bool($value) => $value ? '1' : '0',
            $value === null => null,
            default => (string) $value,
        };
    }
}
