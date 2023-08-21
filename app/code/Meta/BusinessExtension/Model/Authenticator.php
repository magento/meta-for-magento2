<?php

namespace Meta\BusinessExtension\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;

class Authenticator
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Authenticator constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Authenticate a given token against the stored API key
     *
     * @param string $token
     * @return bool
     * @throws LocalizedException
     */
    public function authenticate($token)
    {
        $storedToken = $this->scopeConfig->getValue('meta_extension/general/api_key');
        if ($storedToken === $token) {
            return true;
        }
        throw new LocalizedException(__('Token is invalid'));
    }
}
