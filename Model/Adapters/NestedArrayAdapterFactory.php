<?php
/**
 * Copyright © 2016 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace FireGento\FastSimpleImport\Model\Adapters;

use Magento\Framework\ObjectManagerInterface;

class NestedArrayAdapterFactory implements ImportAdapterFactoryInterface
{
    private ObjectManagerInterface $objectManager;
    private string                 $instanceName;

    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = NestedArrayAdapter::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    public function create(array $data = []): NestedArrayAdapter
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
