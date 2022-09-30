<?php
/**
 * @copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3
 */

namespace FireGento\FastSimpleImport\Model\Adapters;

use Magento\Framework\ObjectManagerInterface;

class ArrayAdapterFactory implements ImportAdapterFactoryInterface
{
    private ObjectManagerInterface $objectManager;
    private string                 $instanceName;

    public function __construct(
        ObjectManagerInterface $objectManager,
        $instanceName = ArrayAdapter::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    public function create(array $data = []): ArrayAdapter
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
