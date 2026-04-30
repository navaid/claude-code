<?php
declare(strict_types=1);

namespace Vendor\OrderSkuFilter\Plugin\Model\ResourceModel\Order\Grid;

use Magento\Framework\DB\Select;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

class Collection
{
    /**
     * Intercept SKU filter and resolve it via a join on sales_order_item,
     * since SKU is not present in the sales_order_grid flat table.
     */
    public function aroundAddFieldToFilter(
        OrderGridCollection $subject,
        callable $proceed,
        $field,
        $condition = null
    ) {
        if ($field !== 'sku') {
            return $proceed($field, $condition);
        }

        $select = $subject->getSelect();

        // Avoid duplicate join if filter is applied more than once
        $fromPart = $select->getPart(Select::FROM);
        if (!isset($fromPart['order_sku_filter_items'])) {
            $select->joinLeft(
                ['order_sku_filter_items' => $subject->getTable('sales_order_item')],
                'order_sku_filter_items.order_id = main_table.entity_id'
                    . ' AND order_sku_filter_items.parent_item_id IS NULL',
                []
            );
        }

        $select
            ->where($subject->getConnection()->prepareSqlCondition('order_sku_filter_items.sku', $condition))
            ->group('main_table.entity_id');

        return $subject;
    }
}
