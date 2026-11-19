<?php

namespace App\Services\Sso;

final class SsoUserResponse
{
    /** @param 'ok'|'unauthorized'|'denied'|'error' $status */
    public function __construct(
        public readonly string $status,
        public readonly array $profile = [],
        public readonly ?string $message = null,
    ) {}
}
