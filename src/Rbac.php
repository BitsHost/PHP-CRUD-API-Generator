<?php
declare(strict_types=1);

namespace App;

/**
 * Legacy namespace wrapper. Canonical class moved to App\\Security\\Rbac.
 *
 * @deprecated Use \App\Security\Rbac instead. This wrapper will be removed in a future major release.
 */
class Rbac extends \App\Security\Rbac {}
