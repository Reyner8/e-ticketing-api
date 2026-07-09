<?php

namespace App\Enums;

enum TicketCategory: string
{
    case GeneralReport = 'general_report';
    case SoftwareBug = 'software_bug';
    case FeatureRequest = 'feature_request';
    case NetworkIssue = 'network_issue';
    case HardwareProblem = 'hardware_problem';
    case SystemError = 'system_error';
    case PerformanceIssue = 'performance_issue';

    public function label() : string
    {
        return match ($this) {
            self::GeneralReport => 'General Report',
            self::SoftwareBug => 'Software Bug',
            self::FeatureRequest => 'Feature Request',
            self::NetworkIssue => 'Network Issue',
            self::HardwareProblem => 'Hardware Problem',
            self::SystemError => 'System Error',
            self::PerformanceIssue => 'Performance Issue',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    } 
}
