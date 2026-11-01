<?php

namespace App\Models;

use App\Domain\Enum\ParameterUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property numeric-string|null $numeric_value
 * @property string|null $text_value
 * @property ParameterUnit $unit
 * @property Carbon $effective_from
 * @property Carbon|null $effective_to
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LegalParameter extends Model
{
    protected $fillable = [
        'key',
        'numeric_value',
        'text_value',
        'unit',
        'effective_from',
        'effective_to',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'unit' => ParameterUnit::class,
            'numeric_value' => 'decimal:6',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function value(): string
    {
        return $this->numeric_value !== null
            ? (string) $this->numeric_value
            : (string) $this->text_value;
    }
}
