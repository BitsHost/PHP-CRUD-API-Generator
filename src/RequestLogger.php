<?php
declare(strict_types=1);

namespace App;

/**
 * Legacy namespace wrapper. Canonical class moved to App\Observability\RequestLogger.
 *
 * @deprecated Use \App\Observability\RequestLogger instead. This wrapper will be removed in a future major release.
 */
class RequestLogger extends \App\Observability\RequestLogger {}
