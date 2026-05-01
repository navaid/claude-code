<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Plugin\Model;

use Amasty\Shiprestriction\Model\RestrictRates;
use Amasty\Shiprestriction\Model\ShippingRestrictionRule;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Shipping;

/**
 * @see Shipping::collectRates
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ShippingPlugin
{
    /**
     * @var ShippingRestrictionRule
     */
    private $shippingRestrictionRule;

    /**
     * @var RestrictRates
     */
    private $restrictRates;

    /**
     * @var RateRequest|null
     */
    private $request = null;

    public function __construct(
        ShippingRestrictionRule $shipRestrictionRule,
        RestrictRates $restrictRates
    ) {
        $this->shippingRestrictionRule = $shipRestrictionRule;
        $this->restrictRates = $restrictRates;
    }

    /**
     * @param Shipping $subject
     * @param RateRequest $request
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCollectRates(Shipping $subject, RateRequest $request): void
    {
        $this->request = $request;
    }

    public function afterCollectRates(Shipping $subject): Shipping
    {
        $result = $subject->getResult();

        $rules = $this->shippingRestrictionRule->getRestrictionRules($this->request);
        if ($rules) {
            $this->restrictRates->execute($result, $rules);
        }

        return $subject;
    }
}
