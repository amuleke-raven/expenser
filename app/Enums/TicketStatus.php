<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::OnHold => 'On Hold',
            self::Escalated => 'Escalated',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Open => 'info',
            self::InProgress => 'primary',
            self::OnHold => 'warning',
            self::Escalated => 'danger',
            self::Resolved => 'success',
            self::Closed => 'gray',
            self::Cancelled => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Open => 'heroicon-o-envelope-open',
            self::InProgress => 'heroicon-o-arrow-path',
            self::OnHold => 'heroicon-o-pause-circle',
            self::Escalated => 'heroicon-o-arrow-up-circle',
            self::Resolved => 'heroicon-o-check-circle',
            self::Closed => 'heroicon-o-x-circle',
            self::Cancelled => 'heroicon-o-minus-circle',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitionsFor(string $role): array
    {
        return match ($role) {
            'staff' => match ($this) {
                self::Draft => [self::Open],
                self::Resolved => [self::Open],
                self::Open => [self::Cancelled],
                default => [],
            },
            'it_staff' => match ($this) {
                self::Open => [self::InProgress, self::OnHold, self::Escalated, self::Resolved],
                self::InProgress => [self::OnHold, self::Escalated, self::Resolved],
                self::OnHold => [self::InProgress, self::Escalated, self::Resolved],
                self::Escalated => [self::InProgress, self::OnHold, self::Resolved],
                default => [],
            },
            'admin', 'super_admin' => array_values(array_filter(
                self::cases(),
                fn (self $case) => $case !== $this
            )),
            default => [],
        };
    }
}
