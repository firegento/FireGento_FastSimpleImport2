<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace FireGento\FastSimpleImport\Model\Config\Source;
use \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
class ValidationStrategy implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options array
     *
     * @var array
     */
    protected $_options;

    /**
     * Return options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = [
                ['value' => ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR, 'label' => __('Stop on Error')], 
                ['value' => ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS, 'label' => __('Skip error entries')], 
            ];
        }

        return $this->_options;
    }
}
