<?php
namespace FireGento\FastSimpleImport2\Model;
class Importer
{
    protected $importModel;
    protected $errorHelper;
    protected $errorMessages;

    public function __construct(\Magento\ImportExport\Model\Import $importModel, \FireGento\FastSimpleImport2\Helper\ImportError $errorHelper)
    {
        $this->importModel = $importModel;
        $this->errorHelper = $errorHelper;
    }
    public function importData()
    {


        $sourceData = array(
            'entity' => 'catalog_product',
            'behavior' => "append",
            'validation_strategy' => 'validation-stop-on-errors',
            'allowed_error_count' => 10,
            '_import_field_separator' => ',',
            '_import_multiple_value_separator' => ',',
            'import_file'=> file_get_contents('testcsv.csv'),
            'import_images_file_dir' => ""
        );
        print_r($sourceData);

        $this->importModel->setData($sourceData);
        $this->importModel->importSource();



        $errorAggregator = $this->importModel->getErrorAggregator();
        if ($this->importModel->getErrorAggregator()->hasToBeTerminated()) {
            $this->errorMessages = $this->errorHelper->getImportErrorMessages($errorAggregator);

        } else {
            $this->importModel->invalidateIndex();
            $this->errorMessages = $this->errorHelper->getImportErrorMessages($errorAggregator);

        }

    }
    public function getErrorMessages(){
        return $this->errorMessages;
    }
}