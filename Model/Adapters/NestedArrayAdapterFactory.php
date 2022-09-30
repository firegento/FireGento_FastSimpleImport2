<?php
/**
 * @copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3
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
