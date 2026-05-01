<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Setup\Patch\Data;

use Amasty\Shiprestriction\Model\ResourceModel\Rule as RuleResource;
use Amasty\Shiprestriction\Model\Rule;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EnableShowMessage implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Enables "Show Restriction Message" for rules with custom restriction message.
     *
     * @return $this
     */
    public function apply()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(RuleResource::TABLE_NAME);

        $select = $connection->select()
            ->from($tableName, ['rule_id'])
            ->where(Rule::CUSTOM_RESTRICTION_MESSAGE . ' is not null');

        $ruleIds = $connection->fetchCol($select);
        if (!empty($ruleIds)) {
            $connection->update(
                $tableName,
                [Rule::SHOW_RESTRICTION_MESSAGE => true],
                $connection->quoteInto('rule_id in (?)', $ruleIds)
            );
        }

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
