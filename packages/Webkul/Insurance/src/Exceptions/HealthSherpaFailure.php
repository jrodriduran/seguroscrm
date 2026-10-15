<?php

namespace Webkul\Insurance\Exceptions;

use RuntimeException;

class HealthSherpaFailure extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        public readonly int $httpStatus = 503,
        public readonly ?int $retryAfter = null,
    ) {
        // Never retain provider response bodies, credentials or applicant data.
        parent::__construct($reason);
    }
}
