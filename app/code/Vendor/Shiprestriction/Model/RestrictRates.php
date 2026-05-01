<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Model;

use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Shipping\Model\Rate\CarrierResult;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class RestrictRates
{
    /**
     * @var SortRulesByPriority
     */
    private $sortRulesByPriority;

    /**
     * @var RestrictRatesPerCarrier
     */
    private $restrictRatesPerCarrier;

    public function __construct(
        SortRulesByPriority $sortRulesByPriority,
        RestrictRatesPerCarrier $restrictRatesPerCarrier
    ) {
        $this->sortRulesByPriority = $sortRulesByPriority;
        $this->restrictRatesPerCarrier = $restrictRatesPerCarrier;
    }

    /**
     * @param CarrierResult $result
     * @param Rule[] $rules
     * @return void
     */
    public function execute(CarrierResult $result, array $rules): void
    {
        $rates = $result->getAllRates();
        if (empty($rates) || empty($rules)) {
            return;
        }

        $ratesByCarrier = $this->getRatesByCarrier($rates);
        $result->reset();

        foreach ($ratesByCarrier as $carrierCode => $carrierRates) {
            $this->restrictRatesPerCarrier->execute(
                $result,
                $carrierCode,
                $carrierRates,
                $this->sortRulesByPriority->execute($rules, $carrierCode)
            );
        }
    }

    /**
     * @param Method[] $rates
     * @return array<string, Method[]>
     */
    private function getRatesByCarrier(array $rates): array
    {
        $ratesByCarrier = [];
        foreach ($rates as $rate) {
            $carrierCode = $rate->getData('carrier');
            if (!isset($ratesByCarrier[$carrierCode])) {
                $ratesByCarrier[$carrierCode] = [];
            }

            $ratesByCarrier[$carrierCode][] = $rate;
        }

        return $ratesByCarrier;
    }
}
