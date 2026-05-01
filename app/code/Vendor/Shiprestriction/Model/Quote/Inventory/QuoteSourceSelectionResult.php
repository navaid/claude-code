<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Model\Quote\Inventory;

use Magento\Framework\Api\AbstractSimpleObject;

class QuoteSourceSelectionResult extends AbstractSimpleObject implements QuoteSourceSelectionResultInterface
{
    const SOURCE_CODES = 'source_codes';

    /**
     * @param array $sourceCodes
     * @return QuoteSourceSelectionResultInterface
     */
    public function setSourceCodes(array $sourceCodes): QuoteSourceSelectionResultInterface
    {
        return $this->setData(self::SOURCE_CODES, $sourceCodes);
    }

    /**
     * @return array
     */
    public function getSourceCodes(): array
    {
        return $this->_get(self::SOURCE_CODES);
    }
}
