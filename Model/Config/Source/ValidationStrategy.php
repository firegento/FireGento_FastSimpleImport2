<?php
/**
 * @copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3
 */

namespace FireGento\FastSimpleImport\Model\Config\Source;

use \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

class ValidationStrategy implements \Magento\Framework\Option\ArrayInterface
{
    private ?array $options = null;

    /**
     * Return available validation strategies
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [
                [
                    'value' => ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR,
                    'label' => __('Stop on Error'),
                ],
                [
                    'value' => ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS,
                    'label' => __('Skip error entries'),
                ],
            ];
        }

        return $this->options;
    }
}
