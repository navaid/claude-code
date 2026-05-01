<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Model\Rule\Condition;

use Amasty\Shiprestriction\Model\Quote\Inventory\GetSourceSelectionResultFromQuote;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;

class Source extends AbstractCondition
{
    /**
     * @var GetSourceSelectionResultFromQuote
     */
    private $getSourceSelectionResultFromQuote;

    /**
     * @return string
     */
    public function asHtml(): string
    {
        return $this->getTypeElementHtml()
            . __(
                'Source %1 %2 ',
                $this->getOperatorElementHtml(),
                $this->getValueElementHtml()
            )
            . $this->getChooserContainerHtml()
            . $this->getRemoveLinkHtml();
    }

    /**
     * Returns true to show apply button and to avoid hide input after click on chooser element
     *
     * @return bool
     */
    public function getExplicitApply(): bool
    {
        return true;
    }

    /**
     * Add chooser element into value element to initialize native triggers by native rules.js
     *
     * @return string
     */
    public function getValueAfterElementHtml(): string
    {
        $image = $this->_assetRepo->getUrl('images/rule_chooser_trigger.gif');

        if (!empty($image)) {
            $script = '<script type="text/javascript">'
                . 'require(["jquery"], function($j) {$j("body").trigger("contentUpdated")});</script>';

            return '<a href="javascript:void(0)" class="am-shiprestrict-sources-chooser rule-chooser-trigger"'
                . ' data-mage-init=\'' . $this->getAdapterComponentConfig() . '\'>'
                . '<img src="' . $image . '" alt="" class="v-middle rule-chooser-trigger" title="'
                . __('Open Chooser')
                . '" /></a>' . $script;
        }

        return '';
    }

    /**
     * Declare operator types for condition
     *
     * @return array
     */
    public function getDefaultOperatorInputByType(): array
    {
        if (null === $this->_defaultOperatorInputByType) {
            $this->_defaultOperatorInputByType = ['string' => ['{}', '!{}']];
            $this->_arrayInputTypes = ['string'];
        }

        return $this->_defaultOperatorInputByType;
    }

    /**
     * @param AbstractModel $model
     * @return bool
     */
    public function validate(AbstractModel $model): bool
    {
        $quoteSourceSelection = $this->getSourceSelectionResultFromQuoteObj()->execute($model->getQuote());
        $cartSources = $quoteSourceSelection->getSourceCodes();
        $ruleSources = $this->getValueParsed();
        $operator = $this->getOperatorForValidate();

        switch ($operator) {
            case '{}':
                $result = !empty(array_intersect($ruleSources, $cartSources));
                break;
            case '!{}':
                $result = !empty(array_diff($cartSources, $ruleSources));
                break;
            default:
                $model->setData($this->getAttribute(), $cartSources);
                $result = parent::validate($model);
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getAttribute(): string
    {
        return 'source_codes';
    }

    /**
     * @return GetSourceSelectionResultFromQuote
     */
    private function getSourceSelectionResultFromQuoteObj(): GetSourceSelectionResultFromQuote
    {
        if ($this->getSourceSelectionResultFromQuote === null) {
            $this->getSourceSelectionResultFromQuote = ObjectManager::getInstance()
                ->get(GetSourceSelectionResultFromQuote::class);
        }

        return $this->getSourceSelectionResultFromQuote;
    }

    /**
     * @return string
     */
    private function getAdapterComponentConfig(): string
    {
        $name = uniqid('amChooser', true);
        $config = [
            'Amasty_Shiprestriction/js/view/form/rule/modal-button-adapter' => [
                'name' => $name,
                'test' => 'ns = ${ $.ns },index = amasty_shiprestrict_rule_sources_form_modal',
                'title' => __('Choose'),
                'visible' => false,
                'actions' => [
                    0 => [
                        'targetName' => 'index = amasty_shiprestrict_rule_sources_form_modal',
                        'actionName' => 'toggleModal'
                    ],
                    1 => [
                        'targetName' => '${ $.name }',
                        'actionName' => 'onModalOpen'
                    ],
                    2 => [
                        'targetName' => 'index = assign_sources_grid',
                        'actionName' => 'set',
                        'params' => [
                            'currentInput',
                            $name
                        ]
                    ]
                ]
            ]
        ];

        return json_encode($config);
    }
}
