<?php
/**
 * Copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */
namespace FireGento\FastSimpleImport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Config extends AbstractHelper
{
    private const XML_PATH_IGNORE_DUPLICATES       = 'fastsimpleimport/default/ignore_duplicates';
    private const XML_PATH_BEHAVIOR                = 'fastsimpleimport/default/behavior';
    private const XML_PATH_ENTITY                  = 'fastsimpleimport/default/entity';
    private const XML_PATH_VALIDATION_STRATEGY     = 'fastsimpleimport/default/validation_strategy';
    private const XML_PATH_ALLOWED_ERROR_COUNT     = 'fastsimpleimport/default/allowed_error_count';
    private const XML_PATH_IMPORT_IMAGES_FILE_FIR  = 'fastsimpleimport/default/import_images_file_dir';

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    public function getIgnoreDuplicates(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_IGNORE_DUPLICATES);
    }

    public function getBehavior(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_BEHAVIOR);
    }

    public function getEntity(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ENTITY);
    }

    public function getValidationStrategy(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_VALIDATION_STRATEGY);
    }

    public function getAllowedErrorCount(): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_ALLOWED_ERROR_COUNT);
    }

    public function getImportFileDir(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_IMPORT_IMAGES_FILE_FIR);
    }
}
