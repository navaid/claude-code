<?php

namespace Amasty\Shiprestriction\Plugin;

/**
 * Class ProductAttributes
 * phpcs:ignoreFile
 */
class ProductAttributes extends \Amasty\CommonRules\Plugin\ProductAttributes
{
    /**
     * ProductAttributes constructor.
     * @param \Amasty\Shiprestriction\Model\ResourceModel\Rule $resourceTable
     */
    public function __construct(\Amasty\Shiprestriction\Model\ResourceModel\Rule $resourceTable)
    {
        parent::__construct($resourceTable);
    }
}
