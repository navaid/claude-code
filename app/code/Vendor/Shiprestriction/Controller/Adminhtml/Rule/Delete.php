<?php

namespace Amasty\Shiprestriction\Controller\Adminhtml\Rule;

/**
 * Delete action
 */
class Delete extends \Amasty\CommonRules\Controller\Adminhtml\Rule\AbstractDelete
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Amasty_Shiprestriction::rule';
}
