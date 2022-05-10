<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use SamIT\abac\interfaces\AccessChecker;
use SamIT\Yii2\abac\FinderInterface;
use SamIT\Yii2\abac\PermissionValidator;
use function iter\map;
use function iter\toArray;

/**
 * @covers \SamIT\Yii2\abac\PermissionValidator
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PermissionValidatorTest extends TestCase
{
    public function testValidatorArrayNotAllowed(): void
    {
        $finder = $this->getMockBuilder(FinderInterface::class)->getMock();
        $finder->expects(self::never())->method('find');
        $source = new \stdClass();
        $accessChecker = $this->getMockBuilder(AccessChecker::class)->getMock();

        $subject = new PermissionValidator($finder, $accessChecker, $source);

        self::assertFalse($subject->validate([12]));
    }

    public function testValidatorSingleValue(): void
    {
        $finder = $this->getMockBuilder(FinderInterface::class)->getMock();
        $models = [
            new \stdClass(),
            new \stdClass(),
            new \stdClass(),
        ];
        $finder->expects(self::once())->method('find')
            ->with([12])
            ->willReturn($models);
        $source = new \stdClass();
        $accessChecker = $this->getMockBuilder(AccessChecker::class)->getMock();
        /** @psalm-suppress MixedArgument */
        $accessChecker
            ->expects(self::exactly(count($models)))
            ->method('check')
            ->withConsecutive(...toArray(map(fn (object $model): array => [$model], $models)))
            ->willReturn(true);

        $subject = new PermissionValidator($finder, $accessChecker, $source);

        self::assertTrue($subject->validate(12));
    }

    public function testValidatorSingleValueFailure(): void
    {
        $finder = $this->getMockBuilder(FinderInterface::class)->getMock();
        $models = [
            new \stdClass(),

        ];
        $finder->expects(self::once())->method('find')
            ->with([12])
            ->willReturn($models);
        $source = new \stdClass();
        $accessChecker = $this->getMockBuilder(AccessChecker::class)->getMock();
        $accessChecker
            ->expects(self::exactly(count($models)))->method('check')->willReturn(false);

        $subject = new PermissionValidator($finder, $accessChecker, $source);

        self::assertFalse($subject->validate(12));
    }
}
