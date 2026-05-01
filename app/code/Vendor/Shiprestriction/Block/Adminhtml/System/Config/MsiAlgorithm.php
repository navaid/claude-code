<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Block\Adminhtml\System\Config;

use Amasty\Shiprestriction\Model\Quote\Inventory\MsiModuleStatusInspector;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class MsiAlgorithm extends Field
{
    /**
     * @var MsiModuleStatusInspector
     */
    private $msiModuleStatusInspector;

    public function __construct(
        Context $context,
        MsiModuleStatusInspector $msiModuleStatusInspector,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->msiModuleStatusInspector = $msiModuleStatusInspector;
    }

    /**
     * @param AbstractElement $element
     * @param string $html
     * @return string
     */
    public function _decorateRowHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element, $html): string
    {
        if (!$this->msiModuleStatusInspector->isEnabled()) {
            return '';
        }

        return parent::_decorateRowHtml($element, $html);
    }
}
