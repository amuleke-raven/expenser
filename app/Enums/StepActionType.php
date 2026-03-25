<?php

namespace App\Enums;

enum StepActionType: string
{
    case Approval = 'approval';
    case DocumentAttachment = 'document_attachment';
    case Signature = 'signature';

    public function label(): string
    {
        return match ($this) {
            self::Approval => 'Approval',
            self::DocumentAttachment => 'Document Attachment',
            self::Signature => 'Signature',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Approval => 'info',
            self::DocumentAttachment => 'warning',
            self::Signature => 'success',
        };
    }
}
