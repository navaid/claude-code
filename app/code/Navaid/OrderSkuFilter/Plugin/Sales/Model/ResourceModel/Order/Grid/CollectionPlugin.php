<?php
declare(strict_types=1);

namespace Navaid\OrderSkuFilter\Plugin\Sales\Model\ResourceModel\Order\Grid;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;
use Zend_Db_Select;

class CollectionPlugin
{
    /**
     * Intercept addFieldToFilter so that filtering by 'sku' joins sales_order_item
     * instead of querying the flat sales_order_grid table (which has no SKU column).
     */
    public function aroundAddFieldToFilter(
        Collection $subject,
        callable $proceed,
        $field,
        $condition = null
    ) {
        if ($field !== 'sku') {
            return $proceed($field, $condition);
        }

        $this->applySkuFilter($subject, $condition);

        return $subject;
    }

    private function applySkuFilter(Collection $collection, $condition): void
    {
        $select = $collection->getSelect();

        // Guard against adding the join more than once (e.g. multi-filter requests).
        $fromParts = $select->getPart(Zend_Db_Select::FROM);
        if (!isset($fromParts['order_items_sku'])) {
            $select->joinInner(
                ['order_items_sku' => $collection->getTable('sales_order_item')],
                'order_items_sku.order_id = main_table.entity_id'
                . ' AND order_items_sku.parent_item_id IS NULL',
                []
            );
        }

        $select->where(
            $collection->getConnection()->prepareSqlCondition('order_items_sku.sku', $condition)
        );

        // Prevent duplicate order rows when multiple items share the same SKU pattern.
        $select->distinct(true);
    }
}
