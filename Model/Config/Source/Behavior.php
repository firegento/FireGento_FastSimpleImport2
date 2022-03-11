<?php
/**
 * Copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace FireGento\FastSimpleImport\Model\Config\Source;

use Magento\ImportExport\Model\Import;

class Behavior implements \Magento\Framework\Option\ArrayInterface
{
    private ?array $options;

    /**
     * Return available behaviors
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [
                ['value' => Import::BEHAVIOR_APPEND, 'label' => __('Append')],
                ['value' => Import::BEHAVIOR_ADD_UPDATE, 'label' => __('Add/Update')],
                ['value' => Import::BEHAVIOR_REPLACE, 'label' => __('Replace')],
                ['value' => Import::BEHAVIOR_DELETE, 'label' => __('Delete')],
            ];
        }

        return $this->options;
    }
}
