<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RotationTest extends TestCase
{
    /** @return array<int,array{UserId:int}> */
    private function members(int ...$ids): array
    {
        return array_map(static fn(int $id): array => ['UserId' => $id], $ids);
    }

    public function testFirstAssignmentStartsAtZero(): void
    {
        self::assertSame(0, PluginRoundRobinRotation::pickNextIndex(null, $this->members(10, 20, 30), [10, 20, 30]));
    }

    public function testAdvancesAndWraps(): void
    {
        $m = $this->members(10, 20, 30);
        self::assertSame(1, PluginRoundRobinRotation::pickNextIndex(0, $m, [10, 20, 30]));
        self::assertSame(2, PluginRoundRobinRotation::pickNextIndex(1, $m, [10, 20, 30]));
        self::assertSame(0, PluginRoundRobinRotation::pickNextIndex(2, $m, [10, 20, 30]));
    }

    public function testSkipsIneligibleMembers(): void
    {
        $m = $this->members(10, 20, 30);
        self::assertSame(2, PluginRoundRobinRotation::pickNextIndex(0, $m, [10, 30]));
        self::assertSame(0, PluginRoundRobinRotation::pickNextIndex(2, $m, [10, 30]));
    }

    public function testReturnsNullWhenNobodyEligible(): void
    {
        self::assertNull(PluginRoundRobinRotation::pickNextIndex(0, $this->members(10, 20), []));
    }

    public function testSingleMemberAlwaysSelected(): void
    {
        self::assertSame(0, PluginRoundRobinRotation::pickNextIndex(0, $this->members(10), [10]));
        self::assertSame(0, PluginRoundRobinRotation::pickNextIndex(null, $this->members(10), [10]));
    }

    public function testLastIndexOutOfRangeIsNormalised(): void
    {
        self::assertSame(1, PluginRoundRobinRotation::pickNextIndex(9, $this->members(10, 20, 30), [10, 20, 30]));
    }

    public function testEmptyMembersIsNull(): void
    {
        self::assertNull(PluginRoundRobinRotation::pickNextIndex(null, [], [1]));
    }

    public function testEligibleUserNotInMembersIsIgnored(): void
    {
        self::assertNull(PluginRoundRobinRotation::pickNextIndex(null, $this->members(10, 20), [99]));
    }

    public function testEligibleIdsMayBeStrings(): void
    {
        self::assertSame(1, PluginRoundRobinRotation::pickNextIndex(0, $this->members(10, 20), ['20']));
    }
}
