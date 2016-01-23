<?php
namespace FireGento\FastSimpleImport2\Model;
class Importer
{
    protected $importModel;
    protected $errorHelper;
    protected $errorMessages;
    protected $arrayAdapterFactory;

    public function __construct(
        \Magento\ImportExport\Model\Import $importModel,
        \FireGento\FastSimpleImport2\Helper\ImportError $errorHelper,
        \FireGento\FastSimpleImport2\Model\ArrayAdapterFactory $arrayAdapterFactory
    )
    {
        $this->importModel = $importModel;
        $this->errorHelper = $errorHelper;
        $this->arrayAdapterFactory = $arrayAdapterFactory;

        $sourceData = array(
            'entity' => 'catalog_product',
            'behavior' => "append",
            'validation_strategy' => 'validation-stop-on-errors',
            'allowed_error_count' => 10,
            '_import_field_separator' => ',',
            '_import_multiple_value_separator' => ',',
            //'import_file'=> file_get_contents('testcsv.csv'),
            'import_images_file_dir' => ""
        );
        //print_r($sourceData);

        $this->importModel->setData($sourceData);

    }

    protected function _importData(){


        $this->importModel->importSource();
        $this->printErrors();
    }
    protected function _validateData($dataArray){

        $source = $this->arrayAdapterFactory->create(array('data'=>$dataArray));
        $validationResult = $this->importModel->validateSource($source);
        $this->printErrors();

    }
    public function importData($dataArray)
    {
        $this->_validateData($dataArray);
        $this->_importData();

    }
    public function printErrors(){
        $errorAggregator = $this->importModel->getErrorAggregator();
        if ($this->importModel->getErrorAggregator()->hasToBeTerminated()) {
            $this->errorMessages = $this->errorHelper->getImportErrorMessages($errorAggregator);

        } else {
            //$this->importModel->invalidateIndex();
            $this->errorMessages = $this->errorHelper->getImportErrorMessages($errorAggregator);
        }
        var_dump($this->importModel->getFormatedLogTrace());
        var_dump($this->errorMessages);
    }

}