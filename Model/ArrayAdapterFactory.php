<?php
namespace FireGento\FastSimpleImport2\Model;
class ArrayAdapterFactory
{
    protected $_objectManager = null;

    protected $_instanceName = null;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = 'FireGento\FastSimpleImport2\Model\ArrayAdapter'
    )
    {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    public function create(array $data = array())
    {
        return $this->_objectManager->create($this->_instanceName, $data);
    }
}