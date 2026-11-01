<?php

namespace App\Http\Parametrics;

enum FieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Select = 'select';
    case Checkbox = 'checkbox';

    public function is(string $case): bool
    {
        return $this === self::tryFrom($case);
    }
}
