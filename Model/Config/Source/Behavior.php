<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace FireGento\FastSimpleImport\Model\Config\Source;

class Behavior implements \Magento\Framework\Option\ArrayInterface
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
                ['value' => \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND, 'label' => __('Add/Update')], 
                ['value' => \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE, 'label' => __('Replace')], 
                ['value' => \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE, 'label' => __('Delete')], 
            ];
        }

        return $this->_options;
    }
}
