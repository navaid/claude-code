<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Model;

use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Shipping\Model\Rate\CarrierResult;

class RestrictRatesPerCarrier
{
    /**
     * @var ErrorFactory
     */
    private $rateErrorFactory;

    /**
     * @var CanShowMessageOnce
     */
    private $canShowMessageOnce;

    public function __construct(
        ErrorFactory $rateErrorFactory,
        CanShowMessageOnce $canShowMessageOnce
    ) {
        $this->rateErrorFactory = $rateErrorFactory;
        $this->canShowMessageOnce = $canShowMessageOnce;
    }

    /**
     * @param CarrierResult $result
     * @param string $carrierCode
     * @param Method[] $carrierRates
     * @param Rule[] $rules
     * @return void
     */
    public function execute(
        CarrierResult $result,
        string $carrierCode,
        array $carrierRates,
        array $rules
    ): void {
        foreach ($carrierRates as $rate) {
            $restrict = false;

            foreach ($rules as $rule) {
                if ($rule->match($rate)) {
                    $restrict = true;

                    $message = $this->getRestrictionMessage($rule);
                    if ($message) {
                        $this->appendError($result, $rate, $message);

                        if ($this->canShowMessageOnce->execute($rule, $carrierCode)) {
                            return;
                        }

                        break;
                    }
                }
            }

            if (!$restrict) {
                $result->append($rate);
            }
        }
    }

    private function getRestrictionMessage(Rule $rule): ?string
    {
        return $rule->getShowRestrictionMessage() ? $rule->getCustomRestrictionMessage() : null;
    }

    private function appendError(CarrierResult $result, Method $rate, string $errorMessage): void
    {
        /** @var Error $error */
        $error = $this->rateErrorFactory->create();
        $error->setData('carrier', $rate->getData('carrier'));
        $error->setData('carrier_title', $rate->getData('carrier_title'));
        $error->setData('error_message', $errorMessage);

        $result->append($error);
    }
}
