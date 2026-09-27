<?php

namespace Webkul\Lead\Exceptions;

use Exception;

class TcpaConsentRequiredException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'Violación de Cumplimiento TCPA: No se puede enviar mensajes salientes sin consentimiento expreso previo (47 U.S.C. § 227).',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
