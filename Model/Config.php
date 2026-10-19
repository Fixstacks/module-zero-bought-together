<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\BoughtTogether\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const PATH = 'zero_bought_together/general/';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH . 'enabled', ScopeInterface::SCOPE_STORE);
    }

    public function title(): string
    {
        return trim((string) $this->scopeConfig->getValue(self::PATH . 'title', ScopeInterface::SCOPE_STORE)) ?: 'Often bought together';
    }

    /** Products shown besides the page's one. */
    public function count(): int
    {
        return max(1, min(4, (int) $this->scopeConfig->getValue(self::PATH . 'count', ScopeInterface::SCOPE_STORE)));
    }

    public function days(): int
    {
        return max(1, (int) $this->scopeConfig->getValue(self::PATH . 'days'));
    }

    public function minOrders(): int
    {
        return max(1, (int) $this->scopeConfig->getValue(self::PATH . 'min_orders'));
    }

    public function fillWithRelated(): bool
    {
        return $this->scopeConfig->isSetFlag(self::PATH . 'fill_related', ScopeInterface::SCOPE_STORE);
    }
}
