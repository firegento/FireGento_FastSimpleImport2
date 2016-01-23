<?php
/**
 * Copyright © 2016 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */
namespace FireGento\FastSimpleImport2\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_BEHAVIOR         = 'fastsimpleimport2/default/behavior';
    const XML_PATH_ENTITY           = 'fastsimpleimport2/default/entity';

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getBehavior()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BEHAVIOR,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENTITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}