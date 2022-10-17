<?php
/**
 * @copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3
 */

namespace FireGento\FastSimpleImport\Model\Config\Source;

use Magento\ImportExport\Model\Import;

class Behavior implements \Magento\Framework\Option\ArrayInterface
{
    private ?array $options = null;

    /**
     * Return available behaviors
     */
    public function toOptionArray(): array
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
