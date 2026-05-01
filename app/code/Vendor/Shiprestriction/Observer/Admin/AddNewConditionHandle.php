<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Observer\Admin;

use Amasty\Shiprestriction\Model\Quote\Inventory\MsiModuleStatusInspector;
use Amasty\Shiprestriction\Model\Rule\Condition\Source;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddNewConditionHandle implements ObserverInterface
{
    /**
     * @var MsiModuleStatusInspector
     */
    private $msiModuleStatusInspector;

    public function __construct(MsiModuleStatusInspector $msiModuleStatusInspector)
    {
        $this->msiModuleStatusInspector = $msiModuleStatusInspector;
    }

    /**
     * Add new condition by MSI source in Advanced Conditions group
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer): self
    {
        if (!$this->msiModuleStatusInspector->isEnabled()) {
            return $this;
        }

        $additional = $observer->getAdditional();
        $conditions = $additional->getConditions();

        if (!is_array($conditions)) {
            return $this;
        }

        foreach ($conditions as &$customConditions) {
            $label = $customConditions['label'] ?? null;
            $values = $customConditions['value'] ?? null;
            $labelToCompare = __('Advanced Conditions');

            if ($label && is_array($values) && $label->getText() === $labelToCompare->getText()) {
                $values[] = [
                    'value' => Source::class,
                    'label' => __('Source')
                ];
                $customConditions['value'] = $values;
                break;
            }
        }

        $additional->setConditions($conditions);

        return $this;
    }
}
