<?php

namespace Amasty\Shiprestriction\Model;

class Rule extends \Amasty\CommonRules\Model\Rule
{
    const SHOW_RESTRICTION_MESSAGE = 'show_restriction_message';
    const CUSTOM_RESTRICTION_MESSAGE = 'custom_restriction_message';
    const SHOW_RESTRICTION_MESSAGE_ONCE = 'show_restriction_message_once';

    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Rule::class);
        parent::_construct();
        $this->subtotalModifier->setSectionConfig(ConstantsInterface::SECTION_KEY);
    }

    /**
     * @return Rule
     */
    public function prepareForEdit()
    {
        foreach (ConstantsInterface::FIELDS as $field) {
            $value = $this->getData($field);

            if (!is_array($value)) {
                $this->setData($field, explode(',', $value ?? ''));
            }
        }

        $value = $this->getCarriers();

        if (is_string($value)) {
            $value = explode(',', $value ?? '');
        }

        if (is_array($value)) {
            $this->setMethods(array_merge($value, $this->getMethods()));
        }

        return $this;
    }

    /**
     * @return array|null
     */
    public function getStores()
    {
        $stores = $this->_getData('stores');
        if (is_string($stores)) {
            $stores = explode(',', $stores ?? '');
        }

        return $stores;
    }

    /**
     * @param array|string $stores
     *
     * @return $this
     */
    public function setStores($stores)
    {
        if (is_array($stores)) {
            $stores = implode(',', $stores);
        }

        return $this->setData('stores', $stores);
    }

    public function setShowRestrictionMessage(bool $flag): void
    {
        $this->setData(self::SHOW_RESTRICTION_MESSAGE, $flag);
    }

    public function getShowRestrictionMessage(): bool
    {
        return (bool) $this->_getData(self::SHOW_RESTRICTION_MESSAGE);
    }

    public function setCustomRestrictionMessage(?string $message): void
    {
        $this->setData(self::CUSTOM_RESTRICTION_MESSAGE, $message);
    }

    public function getCustomRestrictionMessage(): ?string
    {
        return $this->_getData(self::CUSTOM_RESTRICTION_MESSAGE);
    }

    public function setShowRestrictionMessageOnce(bool $flag): void
    {
        $this->setData(self::SHOW_RESTRICTION_MESSAGE_ONCE, $flag);
    }

    public function getShowRestrictionMessageOnce(): bool
    {
        return (bool) $this->_getData(self::SHOW_RESTRICTION_MESSAGE_ONCE);
    }
}
