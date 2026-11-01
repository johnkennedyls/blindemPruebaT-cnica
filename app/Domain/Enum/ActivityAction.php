<?php

namespace App\Domain\Enum;

enum ActivityAction: string
{
    case Crear = 'crear';
    case Actualizar = 'actualizar';
    case Eliminar = 'eliminar';
    case Liquidar = 'liquidar';
    case Anular = 'anular';
    case Aprobar = 'aprobar';
    case Exportar = 'exportar';
    case IniciarSesion = 'iniciar_sesion';

    public function label(): string
    {
        return match ($this) {
            self::Crear => 'Creó',
            self::Actualizar => 'Actualizó',
            self::Eliminar => 'Eliminó',
            self::Liquidar => 'Liquidió',
            self::Anular => 'Anuló',
            self::Aprobar => 'Aprobó',
            self::Exportar => 'Exportó',
            self::IniciarSesion => 'Inició sesión',
        };
    }
}
