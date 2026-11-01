<?php

namespace App\Domain\Enum;

enum TerminationClassification: string
{
    case VoluntadGeneral = 'voluntad_general';
    case JustaCausa = 'justa_causa';
    case SinJustaCausa = 'sin_justa_causa';
    case Vencimiento = 'vencimiento';
    case MutuoAcuerdo = 'mutuo_acuerdo';
    case Fallecimiento = 'fallecimiento';

    public function label(): string
    {
        return match ($this) {
            self::VoluntadGeneral => 'Retiro voluntario',
            self::JustaCausa => 'Despido con justa causa',
            self::SinJustaCausa => 'Despido sin justa causa',
            self::Vencimiento => 'Vencimiento del plazo fijo',
            self::MutuoAcuerdo => 'Mutuo acuerdo',
            self::Fallecimiento => 'Fallecimiento del trabajador',
        };
    }
}
