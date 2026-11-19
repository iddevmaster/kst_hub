<?php

namespace App\Services\Sso;

/** Login refused; getMessage() is safe to show to the user. */
class SsoLoginRejected extends \RuntimeException
{
}
