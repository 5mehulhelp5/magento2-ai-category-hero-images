<?php

declare(strict_types=1);

namespace Elgentos\AiCategoryHeroImages\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'elgentos_aicategoryhero/general/enabled';
    private const XML_PATH_API_KEY = 'elgentos_aicategoryhero/general/api_key';
    private const XML_PATH_ORGANIZATION_ID = 'elgentos_aicategoryhero/general/organization_id';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?string $scopeCode = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getApiKey(?string $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function getOrganizationId(?string $scopeCode = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ORGANIZATION_ID,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }
}
