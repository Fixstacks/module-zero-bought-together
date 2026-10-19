<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\BoughtTogether\Cron;

use Zero\BoughtTogether\Model\Config;
use Zero\BoughtTogether\Model\Pairs;

/**
 * Nightly: the pairs table from the recent orders.
 */
class Build
{
    public function __construct(private readonly Pairs $pairs, private readonly Config $config)
    {
    }

    public function execute(): void
    {
        $this->pairs->build($this->config->days(), $this->config->minOrders());
    }
}
