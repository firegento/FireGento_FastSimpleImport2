<?php

namespace FireGento\FastSimpleImport\ResourceModel;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Overriden resource model for import data. Since we do not upload and validate CSV files, then process the uploaded
 * data in a second step, we can use a temporary table.
 *
 * This not only improves performance, it also allows for parallel import execution, because each process uses its own
 * temporary table.
 *
 * @package FireGento\FastSimpleImport\ResourceModel
 */
class ImportData extends \Magento\ImportExport\Model\ResourceModel\Import\Data
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        ScopeConfigInterface $scopeConfig,
        $connectionName = null
    )
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $jsonHelper, $connectionName);
    }

    protected function _construct()
    {
        if ($this->scopeConfig->isSetFlag('fastsimpleimport/database/import_temp_table')) {
            $this->getConnection()->createTemporaryTableLike(
                'importexport_importdata_tmp',
                'importexport_importdata',
                true
            );
            $this->_init('importexport_importdata_tmp', 'id');
        } else {
            parent::_construct();
        }
    }

}