<?php
/**
 * Import proxy category model
 *
 * @author Bogdan Lewinsky <b.lewinsky@youwe.nl>
 */
namespace FireGento\FastSimpleImport\Model\Import\Proxy\Proxy;

class Category extends \Magento\Catalog\Model\Category
{
    /**
     * DO NOT Initialize resources.
     *
     * @return void
     */
    protected function _construct()
    {
    }
}
