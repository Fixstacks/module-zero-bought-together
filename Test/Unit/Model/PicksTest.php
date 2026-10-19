<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\BoughtTogether\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Zero\BoughtTogether\Model\Picks;

class PicksTest extends TestCase
{
    public function testPairedFirstThenRelatedWithoutRepeatsOrTheProductItself(): void
    {
        self::assertSame([5, 7, 9, 11], Picks::order([5, 7, 3], [7, 9, 3, 11, 0], 3));
    }

    public function testNothingToShow(): void
    {
        self::assertSame([], Picks::order([], [], 3));
        self::assertSame([], Picks::order([3], [3], 3));
    }
}
