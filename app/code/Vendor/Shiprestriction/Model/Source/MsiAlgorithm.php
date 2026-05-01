<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Model\Source;

use Amasty\Shiprestriction\Model\Quote\Inventory\MsiModuleStatusInspector;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\ObjectManagerInterface;

class MsiAlgorithm implements OptionSourceInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var MsiModuleStatusInspector
     */
    private $msiModuleStatusInspector;

    public function __construct(
        ObjectManagerInterface $objectManager,
        MsiModuleStatusInspector $msiModuleStatusInspector
    ) {
        $this->objectManager = $objectManager;
        $this->msiModuleStatusInspector = $msiModuleStatusInspector;
    }

    public function toOptionArray(): array
    {
        if ($this->msiModuleStatusInspector->isEnabled()) {
            $getSourceSelectionAlgorithmList = $this->objectManager->create(
                \Magento\InventorySourceSelectionApi\Model\GetSourceSelectionAlgorithmList::class
            );
            if ($this->options === null) {
                $this->options = [];

                foreach ($getSourceSelectionAlgorithmList->execute() as $selectionAlgorithm) {
                    $this->options[] = [
                        'value' => $selectionAlgorithm->getCode(),
                        'label' => $selectionAlgorithm->getTitle()
                    ];
                }
            }

            return $this->options;
        }

        return [];
    }
}
