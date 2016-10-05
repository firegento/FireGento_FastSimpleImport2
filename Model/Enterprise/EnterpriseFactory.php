<?php
namespace FireGento\FastSimpleImport\Model\Enterprise;
/**
 * Factory which creates Classes from Enterprise
 * Class ClassFactory
 * @package FireGento\FastSimpleImport\Model\Enterprise
 */
class EnterpriseFactory{
    protected $_objectManager;

    public function __construct(\Magento\Framework\ObjectManager\ObjectManager $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    public function create($objName)
    {
        return $this->_objectManager->create($objName, array());
    }
}