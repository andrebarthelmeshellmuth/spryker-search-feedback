<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

/*
 * Minimal config for standalone transfer generation — just the handful of keys the transfer generator's
 * own class-resolver reads. This package never touches Elasticsearch/OpenSearch at all, so unlike its
 * sibling packages, no search-client config keys are needed here.
 */

declare(strict_types = 1);

use Spryker\Shared\Kernel\KernelConstants;

$config[KernelConstants::PROJECT_NAMESPACES] = [];
$config[KernelConstants::CORE_NAMESPACES] = ['SprykerCommunity', 'Spryker'];
