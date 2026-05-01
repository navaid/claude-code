<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Test\Unit\Model;

use Amasty\Shiprestriction\Model\CanShowMessageOnce;
use Amasty\Shiprestriction\Model\Rule;
use Amasty\Shiprestriction\Model\SortRulesByPriority;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see SortRulesByPriority
 * @covers SortRulesByPriority::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class SortRulesByPriorityTest extends \PHPUnit\Framework\TestCase
{
    const CARRIER_CODE = 'test_carrier';

    /**
     * @var SortRulesByPriority
     */
    private $subject;

    /**
     * @var CanShowMessageOnce|MockObject
     */
    private $canShowMessageOnceMock;

    protected function setUp(): void
    {
        $this->canShowMessageOnceMock = $this->createMock(CanShowMessageOnce::class);
        $this->subject = new SortRulesByPriority($this->canShowMessageOnceMock);
    }

    public function testExecuteNoRules(): void
    {
        $this->assertEquals([], $this->subject->execute([], self::CARRIER_CODE));
    }

    public function testExecuteRulesWithoutShowOnce(): void
    {
        $ruleAMock = $this->createConfiguredMock(Rule::class, ['getId' => 1]);
        $ruleBMock = $this->createConfiguredMock(Rule::class, ['getId' => 2]);

        $this->canShowMessageOnceMock
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(false);

        $this->assertSame(
            [$ruleAMock, $ruleBMock],
            $this->subject->execute([$ruleBMock, $ruleAMock], self::CARRIER_CODE)
        );
    }

    public function testExecuteRulesWithShowOnceOnly(): void
    {
        $ruleAMock = $this->createConfiguredMock(Rule::class, ['getId' => 1]);
        $ruleBMock = $this->createConfiguredMock(Rule::class, ['getId' => 2]);

        $this->canShowMessageOnceMock
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->assertSame(
            [$ruleAMock, $ruleBMock],
            $this->subject->execute([$ruleBMock, $ruleAMock], self::CARRIER_CODE)
        );
    }

    public function testExecuteRulesWithMixedValuesForShowOnce(): void
    {
        $ruleAMock = $this->createConfiguredMock(Rule::class, ['getId' => 1]);
        $ruleBMock = $this->createConfiguredMock(Rule::class, ['getId' => 2]);
        $ruleCMock = $this->createConfiguredMock(Rule::class, ['getId' => 3]);
        $ruleDMock = $this->createConfiguredMock(Rule::class, ['getId' => 4]);

        $this->canShowMessageOnceMock
            ->expects($this->exactly(4))
            ->method('execute')
            ->willReturnMap([
                [$ruleAMock, self::CARRIER_CODE, false],
                [$ruleBMock, self::CARRIER_CODE, true],
                [$ruleCMock, self::CARRIER_CODE, false],
                [$ruleDMock, self::CARRIER_CODE, true]
            ]);

        $this->assertSame(
            [$ruleBMock, $ruleDMock, $ruleAMock, $ruleCMock],
            $this->subject->execute([$ruleAMock, $ruleBMock, $ruleCMock, $ruleDMock], self::CARRIER_CODE)
        );
    }
}
