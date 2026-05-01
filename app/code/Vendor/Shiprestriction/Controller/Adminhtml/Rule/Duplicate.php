<?php

namespace Amasty\Shiprestriction\Controller\Adminhtml\Rule;

/**
 * Duplicate Action
 */
class Duplicate extends \Amasty\CommonRules\Controller\Adminhtml\Rule\AbstractDuplicate
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Amasty_Shiprestriction::rule';
}
