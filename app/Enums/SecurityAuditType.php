<?php

namespace App\Enums;

enum SecurityAuditType: string
{
    case AccessRights = 'hak_akses';
    case ActivityLog = 'log_aktivitas';
    case DataSecurity = 'keamanan_data';
    case NetworkInfrastructure = 'jaringan_infrastruktur';
    case SecurityIncident = 'insiden_keamanan';
    case Compliance = 'kepatuhan_sop';

    public function label(): string
    {
        return match ($this) {
            self::AccessRights => 'Audit Hak Akses',
            self::ActivityLog => 'Audit Log Aktivitas',
            self::DataSecurity => 'Audit Keamanan Data',
            self::NetworkInfrastructure => 'Audit Jaringan & Infrastruktur',
            self::SecurityIncident => 'Audit Insiden Keamanan',
            self::Compliance => 'Audit Kepatuhan SOP TI',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
