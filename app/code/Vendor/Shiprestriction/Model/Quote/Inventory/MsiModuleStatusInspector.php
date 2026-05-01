<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Model\Quote\Inventory;

use Magento\Framework\Module\Manager;

class MsiModuleStatusInspector
{
    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(Manager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }
}
