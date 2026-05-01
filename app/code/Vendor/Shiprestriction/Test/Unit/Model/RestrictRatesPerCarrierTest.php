<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Test\Unit\Model;

use Amasty\Shiprestriction\Model\CanShowMessageOnce;
use Amasty\Shiprestriction\Model\RestrictRatesPerCarrier;
use Amasty\Shiprestriction\Model\Rule;
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Shipping\Model\Rate\CarrierResult;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see RestrictRatesPerCarrier
 * @covers RestrictRatesPerCarrier::execute
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class RestrictRatesPerCarrierTest extends \PHPUnit\Framework\TestCase
{
    const CARRIER_CODE = 'test_carrier';
    const CARRIER_TITLE = 'Test';

    /**
     * @var RestrictRatesPerCarrier
     */
    private $subject;

    /**
     * @var CarrierResult|MockObject
     */
    private $resultMock;

    /**
     * @var CanShowMessageOnce|MockObject
     */
    private $canShowMessageOnceMock;

    protected function setUp(): void
    {
        $this->resultMock = $this->createMock(CarrierResult::class);

        $rateErrorFactoryMock = $this->createMock(ErrorFactory::class);
        $rateErrorFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturnCallback(function () {
                return $this->createPartialMock(Error::class, []);
            });

        $this->canShowMessageOnceMock = $this->createMock(CanShowMessageOnce::class);
        $this->subject = new RestrictRatesPerCarrier(
            $rateErrorFactoryMock,
            $this->canShowMessageOnceMock
        );
    }

    public function testExecuteWithNoRates(): void
    {
        $ruleMock = $this->createMock(Rule::class);
        $this->resultMock->expects($this->never())->method('append');
        $this->subject->execute($this->resultMock, self::CARRIER_CODE, [], [$ruleMock]);
    }

    public function testExecuteWithNoRules(): void
    {
        $rateMock = $this->createMock(Method::class);
        $this->resultMock->expects($this->once())->method('append')->with($rateMock);
        $this->subject->execute($this->resultMock, self::CARRIER_CODE, [$rateMock], []);
    }

    public function testExecuteWithNoMatches(): void
    {
        $rateMock = $this->createMock(Method::class);
        $ruleMock = $this->createConfiguredMock(Rule::class, ['match' => false]);

        $this->resultMock->expects($this->once())->method('append')->with($rateMock);
        $this->subject->execute($this->resultMock, self::CARRIER_CODE, [$rateMock], [$ruleMock]);
    }

    public function testExecuteNoErrorsIfRuleCantShowMessage(): void
    {
        $rateMock = $this->createMock(Method::class);
        $ruleMock = $this->createConfiguredMock(Rule::class, ['match' => true]);

        $this->resultMock->expects($this->never())->method('append');
        $this->subject->execute($this->resultMock, self::CARRIER_CODE, [$rateMock], [$ruleMock]);
    }

    /**
     * If called with two rules, rule A with message and rule B with no message,
     * the error should be added to result with message from the rule A.
     */
    public function testExecuteWithRuleWithShowMessage(): void
    {
        $rateMock = $this->createRateMock();

        $ruleMock = $this->createConfiguredMock(Rule::class, ['match' => true]);
        $ruleWithShowMessageMock = $this->createConfiguredMock(Rule::class, [
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'custom message',
            'match' => true
        ]);

        $this->resultMock
            ->expects($this->once())
            ->method('append')
            ->with(
                $this->callback(function (Error $error) {
                    return $error->getData('carrier') === self::CARRIER_CODE
                        && $error->getData('carrier_title') === self::CARRIER_TITLE
                        && $error->getErrorMessage() === 'custom message';
                })
            );

        $this->subject->execute(
            $this->resultMock,
            self::CARRIER_CODE,
            [$rateMock],
            [$ruleMock, $ruleWithShowMessageMock]
        );
    }

    /**
     * If called with multiple rules with message, the message will be taken from the oldest rule.
     */
    public function testExecuteWithMultipleRulesWithShowMessage(): void
    {
        $rateMock = $this->createRateMock();

        $ruleAMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 1,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'message A',
            'match' => true
        ]);

        $ruleBMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 2,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'message B',
            'match' => true
        ]);

        $this->resultMock
            ->expects($this->once())
            ->method('append')
            ->with(
                $this->callback(function (Error $error) {
                    return $error->getData('carrier') === self::CARRIER_CODE
                        && $error->getData('carrier_title') === self::CARRIER_TITLE
                        && $error->getErrorMessage() === 'message A';
                })
            );

        $this->subject->execute(
            $this->resultMock,
            self::CARRIER_CODE,
            [$rateMock],
            [$ruleAMock, $ruleBMock]
        );
    }

    /**
     * If called with rule with "Show Restriction Message Once" disabled and rates that
     * all belong to the same carrier, the error should be added for each rate.
     */
    public function testExecuteWithRuleForWholeCarrier(): void
    {
        $ruleMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 1,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'custom message',
            'match' => true
        ]);

        $this->resultMock
            ->expects($this->exactly(3))
            ->method('append')
            ->with(
                $this->callback(function (Error $error) {
                    return $error->getData('carrier') === self::CARRIER_CODE
                        && $error->getData('carrier_title') === self::CARRIER_TITLE
                        && $error->getErrorMessage() === 'custom message';
                })
            );

        $this->subject->execute(
            $this->resultMock,
            self::CARRIER_CODE,
            [
                $this->createRateMock(),
                $this->createRateMock(),
                $this->createRateMock()
            ],
            [$ruleMock]
        );
    }

    /**
     * If called with multiple rates of the same carrier and the rule that
     * only restricts some rates of that carrier, all allowed rates should be appended
     * while each restricted rate should append an error.
     */
    public function testExecuteWithRuleWithMultipleRates(): void
    {
        $restrictedRates = ['A', 'D'];
        $rateMocks = [
            $this->createRateMock('A'),
            $this->createRateMock('B'),
            $this->createRateMock('C'),
            $this->createRateMock('D')
        ];

        $ruleMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 1,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'custom message'
        ]);

        $ruleMock
            ->expects($this->any())
            ->method('match')
            ->willReturnCallback(function (Method $rate) use ($restrictedRates) {
                return in_array($rate->getData('method'), $restrictedRates);
            });

        $errorCounter = 0;
        $this->resultMock
            ->expects($this->exactly(4))
            ->method('append')
            ->with(
                $this->callback(function ($result) use (&$errorCounter) {
                    if ($result->getData('error_message')) {
                        $errorCounter++;
                    }

                    return $result->getData('carrier') === self::CARRIER_CODE
                        && $result->getData('carrier_title') === self::CARRIER_TITLE;
                })
            );

        $this->subject->execute($this->resultMock, self::CARRIER_CODE, $rateMocks, [$ruleMock]);
        $this->assertEquals(2, $errorCounter);
    }

    /**
     * If called with rates of the same carrier and two rules where older one restricts only one method (e.g. B)
     * and the newer one restricts the whole carrier, the error for method B should be taken from older rule
     * and errors for other methods should be taken from newer rule.
     */
    public function testExecuteWithOlderRuleForOneRateAndNewerOneForWholeCarrier(): void
    {
        $rateMocks = [
            $this->createRateMock('A'),
            $this->createRateMock('B'),
            $this->createRateMock('C')
        ];

        $ruleForMethodMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 1,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'restriction for the method'
        ]);

        $ruleForMethodMock
            ->expects($this->any())
            ->method('match')
            ->willReturnCallback(function (Method $rate) {
                return $rate->getData('method') === 'B';
            });

        $ruleForCarrierMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 2,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'restriction for the carrier',
            'match' => true
        ]);

        $errorMessages = [];
        $this->resultMock
            ->expects($this->exactly(3))
            ->method('append')
            ->with(
                $this->callback(function (Error $error) use (&$errorMessages) {
                    $errorMessages[] = $error->getErrorMessage();
                    return true;
                })
            );

        $this->subject->execute(
            $this->resultMock,
            self::CARRIER_CODE,
            $rateMocks,
            [$ruleForMethodMock, $ruleForCarrierMock]
        );

        $this->assertEquals([
            'restriction for the carrier',
            'restriction for the method',
            'restriction for the carrier'
        ], $errorMessages);
    }

    /**
     * If called with rates of the same carrier and two rules where the older one restricts the whole carrier
     * and newer one restricts only one method (e.g. B), errors for all those rates should be appended
     * with message from older rule.
     *
     * @see testExecuteWithOlderRuleForOneRateAndNewerOneForWholeCarrier
     */
    public function testExecuteAppendsErrorsWithOlderRuleForCarrierAndNewerOneForOneRate(): void
    {
        $rateMocks = [
            $this->createRateMock('A'),
            $this->createRateMock('B'),
            $this->createRateMock('C')
        ];

        $ruleForCarrierMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 1,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'restriction for the carrier',
            'match' => true
        ]);

        $ruleForMethodMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 2,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'restriction for the method'
        ]);

        $ruleForMethodMock
            ->expects($this->any())
            ->method('match')
            ->willReturnCallback(function (Method $rate) {
                return $rate->getData('method') === 'B';
            });

        $errorMessages = [];
        $this->resultMock
            ->expects($this->exactly(3))
            ->method('append')
            ->with(
                $this->callback(function (Error $error) use (&$errorMessages) {
                    $errorMessages[] = $error->getErrorMessage();
                    return true;
                })
            );

        $this->subject->execute(
            $this->resultMock,
            self::CARRIER_CODE,
            $rateMocks,
            [$ruleForCarrierMock, $ruleForMethodMock]
        );

        $this->assertEquals([
            'restriction for the carrier',
            'restriction for the carrier',
            'restriction for the carrier'
        ], $errorMessages);
    }

    /**
     * "Show Restriction Message Once" works only if rule restricts the whole carrier.
     * Otherwise, the rule is treated as a regular one and adds error per each matched method of the carrier.
     */
    public function testExecuteWithRuleWithShowMessageOnceForSeparateRates(): void
    {
        $rateMocks = [
            $this->createRateMock('A'),
            $this->createRateMock('B'),
            $this->createRateMock('C')
        ];

        $ruleMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 1,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'custom message',
            'getShowRestrictionMessageOnce' => true,
            'match' => true
        ]);

        $this->canShowMessageOnceMock
            ->expects($this->exactly(3))
            ->method('execute')
            ->with($ruleMock, self::CARRIER_CODE)
            ->willReturn(false);

        $this->resultMock
            ->expects($this->exactly(3))
            ->method('append')
            ->with(
                $this->callback(function (Error $error) {
                    return $error->getData('carrier') === self::CARRIER_CODE
                        && $error->getData('carrier_title') === self::CARRIER_TITLE
                        && $error->getErrorMessage() === 'custom message';
                })
            );

        $this->subject->execute($this->resultMock, self::CARRIER_CODE, $rateMocks, [$ruleMock]);
    }

    public function testExecuteWithRuleWithShowMessageOnceForWholeCarrier(): void
    {
        $rateMocks = [
            $this->createRateMock(),
            $this->createRateMock(),
            $this->createRateMock()
        ];

        $ruleMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 1,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'custom message',
            'getShowRestrictionMessageOnce' => true,
            'match' => true
        ]);

        $this->canShowMessageOnceMock
            ->expects($this->once())
            ->method('execute')
            ->with($ruleMock, self::CARRIER_CODE)
            ->willReturn(true);

        $this->resultMock
            ->expects($this->once())
            ->method('append')
            ->with(
                $this->callback(function (Error $error) {
                    return $error->getData('carrier') === self::CARRIER_CODE
                        && $error->getData('carrier_title') === self::CARRIER_TITLE
                        && $error->getErrorMessage() === 'custom message';
                })
            );

        $this->subject->execute($this->resultMock, self::CARRIER_CODE, $rateMocks, [$ruleMock]);
    }

    /**
     * If called with rates of the same carrier and multiple rules with "Show Restriction Message Once" enabled,
     * the message should be taken from the oldest rule.
     */
    public function testExecuteWithMultipleRulesWithShowMessageOnce(): void
    {
        $rateMocks = [$this->createRateMock(), $this->createRateMock()];

        $ruleAMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 1,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'message A',
            'getShowRestrictionMessageOnce' => true,
            'match' => true
        ]);

        $ruleBMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 2,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'message B',
            'getShowRestrictionMessageOnce' => true,
            'match' => true
        ]);

        $this->canShowMessageOnceMock
            ->expects($this->once())
            ->method('execute')
            ->with($ruleAMock, self::CARRIER_CODE)
            ->willReturn(true);

        $this->resultMock
            ->expects($this->once())
            ->method('append')
            ->with(
                $this->callback(function (Error $error) {
                    return $error->getData('carrier') === self::CARRIER_CODE
                        && $error->getData('carrier_title') === self::CARRIER_TITLE
                        && $error->getErrorMessage() === 'message A';
                })
            );

        $this->subject->execute($this->resultMock, self::CARRIER_CODE, $rateMocks, [$ruleAMock, $ruleBMock]);
    }

    /**
     * If called with two rules where the older rule (A) has a message and the newer one (B) has a message
     * with "Show Restriction Message Once" enabled, the message should be taken
     * from the rule with "Show Restriction Message Once" (B).
     */
    public function testExecuteWithRuleWithShowMessageOnceAndRegularRuleWithMessage(): void
    {
        $rateMock = $this->createRateMock();

        $ruleWithShowOnceMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 1,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'message A',
            'getShowRestrictionMessageOnce' => true,
            'match' => true
        ]);

        $ruleWithMessageMock = $this->createConfiguredMock(Rule::class, [
            'getId' => 2,
            'getShowRestrictionMessage' => true,
            'getCustomRestrictionMessage' => 'message B',
            'match' => true
        ]);

        $this->canShowMessageOnceMock
            ->expects($this->once())
            ->method('execute')
            ->with($ruleWithShowOnceMock, self::CARRIER_CODE)
            ->willReturn(true);

        $this->resultMock
            ->expects($this->once())
            ->method('append')
            ->with(
                $this->callback(function (Error $error) {
                    return $error->getData('carrier') === self::CARRIER_CODE
                        && $error->getData('carrier_title') === self::CARRIER_TITLE
                        && $error->getErrorMessage() === 'message A';
                })
            );

        $this->subject->execute(
            $this->resultMock,
            self::CARRIER_CODE,
            [$rateMock],
            [$ruleWithShowOnceMock, $ruleWithMessageMock]
        );
    }

    /**
     * @param string|null $method
     * @return Method|MockObject
     */
    private function createRateMock(?string $method = null)
    {
        $rateMock = $this->createMock(Method::class);
        $rateMock
            ->expects($this->any())
            ->method('getData')
            ->willReturnMap([
                ['carrier', null, self::CARRIER_CODE],
                ['carrier_title', null, self::CARRIER_TITLE],
                ['method', null, $method]
            ]);

        return $rateMock;
    }
}
