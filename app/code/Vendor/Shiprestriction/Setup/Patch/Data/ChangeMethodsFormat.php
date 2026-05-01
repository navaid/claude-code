<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Setup\Patch\Data;

use Amasty\Shiprestriction\Setup\ChangeMethodsFormat as ChangeFormatHandler;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ChangeMethodsFormat implements DataPatchInterface
{
    /**
     * @var ChangeFormatHandler
     */
    private $changeFormatHandler;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        ChangeFormatHandler $changeFormatHandler,
        State $appState
    ) {
        $this->changeFormatHandler = $changeFormatHandler;
        $this->appState = $appState;
    }

    public function apply()
    {
        $this->appState->emulateAreaCode(
            Area::AREA_ADMINHTML,
            [$this->changeFormatHandler, 'execute']
        );

        return $this;
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
