<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Test\Unit\Model;

use Amasty\Shiprestriction\Model\CanShowMessageOnce;
use Amasty\Shiprestriction\Model\Rule;

/**
 * @see CanShowMessageOnce
 * @covers CanShowMessageOnce::execute
 */
class CanShowMessageOnceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CanShowMessageOnce
     */
    private $subject;

    protected function setUp(): void
    {
        $this->subject = new CanShowMessageOnce();
    }

    public function testExecuteWithOptionDisabled(): void
    {
        $ruleMock = $this->createMock(Rule::class);
        $this->assertEquals(false, $this->subject->execute($ruleMock, 'test_carrier'));
    }

    public function testExecuteWithOptionEnabledAndNoCarriersRestricted(): void
    {
        $ruleMock = $this->createConfiguredMock(Rule::class, [
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'custom message',
            'getShowRestrictionMessageOnce' => true
        ]);

        $ruleMock
            ->expects($this->any())
            ->method('getData')
            ->with('carriers', null)
            ->willReturn(null);

        $this->assertEquals(false, $this->subject->execute($ruleMock, 'test_carrier'));
    }

    public function testExecuteWithOptionEnabledAndDifferentCarrierRestricted(): void
    {
        $ruleMock = $this->createConfiguredMock(Rule::class, [
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'custom message',
            'getShowRestrictionMessageOnce' => true
        ]);

        $ruleMock
            ->expects($this->any())
            ->method('getData')
            ->with('carriers', null)
            ->willReturn('A');

        $this->assertEquals(false, $this->subject->execute($ruleMock, 'B'));
    }

    public function testExecuteWithOptionEnabledAndNeededCarrierRestricted(): void
    {
        $ruleMock = $this->createConfiguredMock(Rule::class, [
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'custom message',
            'getShowRestrictionMessageOnce' => true
        ]);

        $ruleMock
            ->expects($this->any())
            ->method('getData')
            ->with('carriers', null)
            ->willReturn('A');

        $this->assertEquals(true, $this->subject->execute($ruleMock, 'A'));
    }
}
