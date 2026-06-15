<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class UserSetting extends Model
{
    public const FeedbackModeKey = 'feedback_mode';

    public const DefaultFeedbackMode = 'alert';

    public const PemeriksaanNavigationModeKey = 'pemeriksaan_navigation_mode';

    public const DefaultPemeriksaanNavigationMode = 'sidebar-tree';

    protected $table = 'users_settings';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'key',
        'value',
        'type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'string',
        ];
    }

    public static function feedbackMode(string|int|null $userId): string
    {
        return static::normalizeFeedbackMode(static::value($userId, self::FeedbackModeKey));
    }

    public static function setFeedbackMode(string|int|null $userId, string $mode): self
    {
        return static::setValue($userId, self::FeedbackModeKey, static::normalizeFeedbackMode($mode));
    }

    public static function pemeriksaanNavigationMode(string|int|null $userId): string
    {
        return static::normalizePemeriksaanNavigationMode(static::value($userId, self::PemeriksaanNavigationModeKey));
    }

    public static function setPemeriksaanNavigationMode(string|int|null $userId, string $mode): self
    {
        return static::setValue($userId, self::PemeriksaanNavigationModeKey, static::normalizePemeriksaanNavigationMode($mode));
    }

    public static function value(string|int|null $userId, string $key, ?string $default = null): ?string
    {
        if ($userId === null || $key === '') {
            return $default;
        }

        return static::query()
            ->where('user_id', (string) $userId)
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    public static function setValue(string|int|null $userId, string $key, string|int|bool|null $value, string $type = 'string'): self
    {
        if ($userId === null || $key === '') {
            throw new InvalidArgumentException('User ID dan key wajib diisi untuk user setting.');
        }

        return static::query()->updateOrCreate(
            [
                'user_id' => (string) $userId,
                'key' => $key,
            ],
            [
                'value' => static::stringValue($value),
                'type' => $type,
            ],
        );
    }

    public static function boolean(string|int|null $userId, string $key, bool $default = false): bool
    {
        return static::normalizeBoolean(static::value($userId, $key), $default);
    }

    private static function normalizeFeedbackMode(?string $mode): string
    {
        return in_array($mode, ['toast', 'alert'], true)
            ? $mode
            : self::DefaultFeedbackMode;
    }

    private static function normalizePemeriksaanNavigationMode(?string $mode): string
    {
        return in_array($mode, ['sidebar-tree', 'top-tab'], true)
            ? $mode
            : self::DefaultPemeriksaanNavigationMode;
    }

    private static function normalizeBoolean(?string $value, bool $default): bool
    {
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
