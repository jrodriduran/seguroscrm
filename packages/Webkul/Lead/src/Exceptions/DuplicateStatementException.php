<?php

namespace Webkul\Lead\Exceptions;

use Exception;
use Webkul\Lead\Models\CarrierStatement;

class DuplicateStatementException extends Exception
{
    public function __construct(
        public readonly CarrierStatement $existingStatement,
        public readonly string $fileHash,
        string $message = '',
        int $code = 409
    ) {
        $msg = $message ?: "El archivo de liquidación ya fue procesado previamente el {$existingStatement->created_at?->format('d/m/Y H:i')} bajo el Statement #{$existingStatement->id} ({$existingStatement->carrier_name} - {$existingStatement->period_month}).";
        parent::__construct($msg, $code);
    }
}
