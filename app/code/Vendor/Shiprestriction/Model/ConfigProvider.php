<?php
declare(strict_types=1);

namespace Amasty\Shiprestriction\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider
{
    const CONFIG_PATH_GENERAL_MSI_ALGORITHM = 'amshiprestriction/general/msi_algorithm';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int|null $websiteId
     * @return string
     */
    public function getMsiAlgorithm(?int $websiteId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_PATH_GENERAL_MSI_ALGORITHM,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
    }
}
