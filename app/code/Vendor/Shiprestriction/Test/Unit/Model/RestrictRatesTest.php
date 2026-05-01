<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Test\Unit\Model;

use Amasty\Shiprestriction\Model\RestrictRatesPerCarrier;
use Amasty\Shiprestriction\Model\SortRulesByPriority;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Shipping\Model\Rate\CarrierResult;
use Amasty\Shiprestriction\Model\RestrictRates;
use Amasty\Shiprestriction\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see RestrictRates
 * @covers RestrictRates::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class RestrictRatesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RestrictRates
     */
    private $subject;

    /**
     * @var CarrierResult|MockObject
     */
    private $resultMock;

    /**
     * @var SortRulesByPriority|MockObject
     */
    private $sortRulesByPriorityMock;

    /**
     * @var RestrictRatesPerCarrier|MockObject
     */
    private $restrictRatesPerCarrierMock;

    protected function setUp(): void
    {
        $this->resultMock = $this->createMock(CarrierResult::class);
        $this->sortRulesByPriorityMock = $this->createMock(SortRulesByPriority::class);
        $this->restrictRatesPerCarrierMock = $this->createMock(RestrictRatesPerCarrier::class);

        $this->subject = new RestrictRates(
            $this->sortRulesByPriorityMock,
            $this->restrictRatesPerCarrierMock
        );
    }

    public function testExecuteWithNoRates(): void
    {
        $this->resultMock
            ->expects($this->once())
            ->method('getAllRates')
            ->willReturn([]);

        $this->resultMock->expects($this->never())->method('reset');
        $this->sortRulesByPriorityMock->expects($this->never())->method('execute');
        $this->restrictRatesPerCarrierMock->expects($this->never())->method('execute');
        $this->subject->execute($this->resultMock, [$this->createMock(Rule::class)]);
    }

    public function testExecuteWithNoRules(): void
    {
        $this->resultMock
            ->expects($this->once())
            ->method('getAllRates')
            ->willReturn([$this->createMock(Method::class)]);

        $this->resultMock->expects($this->never())->method('reset');
        $this->sortRulesByPriorityMock->expects($this->never())->method('execute');
        $this->restrictRatesPerCarrierMock->expects($this->never())->method('execute');
        $this->subject->execute($this->resultMock, []);
    }

    public function testExecuteWithOneCarrier(): void
    {
        $rateAMock = $this->createMock(Method::class);
        $rateAMock
            ->expects($this->once())
            ->method('getData')
            ->with('carrier', null)
            ->willReturn('A');

        $rateBMock = $this->createMock(Method::class);
        $rateBMock
            ->expects($this->once())
            ->method('getData')
            ->with('carrier', null)
            ->willReturn('A');

        $rateMocks = [$rateAMock, $rateBMock];

        $this->resultMock
            ->expects($this->once())
            ->method('getAllRates')
            ->willReturn($rateMocks);

        $ruleMock = $this->createMock(Rule::class);
        $this->sortRulesByPriorityMock
            ->expects($this->once())
            ->method('execute')
            ->with([$ruleMock], 'A')
            ->willReturn([$ruleMock]);

        $this->restrictRatesPerCarrierMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->resultMock, 'A', $rateMocks, [$ruleMock]);

        $this->resultMock->expects($this->once())->method('reset');
        $this->subject->execute($this->resultMock, [$ruleMock]);
    }

    public function testExecuteWithMultipleCarriers(): void
    {
        $rateAMock = $this->createMock(Method::class);
        $rateAMock
            ->expects($this->once())
            ->method('getData')
            ->with('carrier', null)
            ->willReturn('A');

        $rateBMock = $this->createMock(Method::class);
        $rateBMock
            ->expects($this->once())
            ->method('getData')
            ->with('carrier', null)
            ->willReturn('A');

        $rateCMock = $this->createMock(Method::class);
        $rateCMock
            ->expects($this->once())
            ->method('getData')
            ->with('carrier', null)
            ->willReturn('B');

        $this->resultMock
            ->expects($this->once())
            ->method('getAllRates')
            ->willReturn([$rateAMock, $rateBMock, $rateCMock]);

        $ruleMock = $this->createMock(Rule::class);
        $this->sortRulesByPriorityMock
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [[$ruleMock], 'A'],
                [[$ruleMock], 'B']
            )
            ->willReturn([$ruleMock]);

        $this->restrictRatesPerCarrierMock
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->resultMock, 'A', [$rateAMock, $rateBMock], [$ruleMock]],
                [$this->resultMock, 'B', [$rateCMock], [$ruleMock]]
            );

        $this->resultMock->expects($this->once())->method('reset');
        $this->subject->execute($this->resultMock, [$ruleMock]);
    }
}
