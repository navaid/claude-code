<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Model\Quote\Inventory;

use Amasty\Shiprestriction\Model\ConfigProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\Quote\Model\Quote;

class GetSourceSelectionResultFromQuote
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var SourceSelectionServiceInterface
     */
    private $sourceSelectionService;

    /**
     * @var InventoryRequestFromQuoteFactory
     */
    private $inventoryRequestFromQuoteFactory;

    /**
     * @var QuoteSourceSelectionResultInterfaceFactory
     */
    private $quoteSourceSelectionResultFactory;

    /**
     * @var array<int, QuoteSourceSelectionResultInterface>
     */
    private $cachedResults = [];

    public function __construct(
        ConfigProvider $configProvider,
        SourceSelectionServiceInterface $sourceSelectionService,
        InventoryRequestFromQuoteFactory $inventoryRequestFromQuoteFactory,
        QuoteSourceSelectionResultInterfaceFactory $quoteSourceSelectionResultFactory
    ) {
        $this->configProvider = $configProvider;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->inventoryRequestFromQuoteFactory = $inventoryRequestFromQuoteFactory;
        $this->quoteSourceSelectionResultFactory = $quoteSourceSelectionResultFactory;
    }

    /**
     * @param Quote $quote
     * @param bool $useCache
     * @return QuoteSourceSelectionResultInterface
     * @throws NoSuchEntityException
     */
    public function execute(Quote $quote, bool $useCache = true): QuoteSourceSelectionResultInterface
    {
        if ($useCache && $cachedResult = $this->cachedResults[(int) $quote->getId()] ?? null) {
            return $cachedResult;
        }

        $inventoryRequest = $this->inventoryRequestFromQuoteFactory->create($quote);
        $selectionAlgorithmCode = $this->configProvider->getMsiAlgorithm();
        $sourceSelectionResult = $this->sourceSelectionService->execute($inventoryRequest, $selectionAlgorithmCode);
        $quoteSourceSelectionResult = $this->convertResult($sourceSelectionResult);

        if ($useCache) {
            $this->cachedResults[(int) $quote->getId()] = $quoteSourceSelectionResult;
        }

        return $quoteSourceSelectionResult;
    }

    /**
     * @param SourceSelectionResultInterface $sourceSelectionResult
     * @return QuoteSourceSelectionResultInterface
     */
    private function convertResult(
        SourceSelectionResultInterface $sourceSelectionResult
    ): QuoteSourceSelectionResultInterface {
        $sourceCodes = [];

        foreach ($sourceSelectionResult->getSourceSelectionItems() as $sourceSelectionItem) {
            if ($sourceSelectionItem->getQtyToDeduct()) {
                $sourceCodes[] = $sourceSelectionItem->getSourceCode();
            }
        }

        $sourceCodes = array_unique($sourceCodes);

        return $this->quoteSourceSelectionResultFactory->create()
            ->setSourceCodes($sourceCodes);
    }
}
