<?php
namespace FireGento\FastSimpleImport2\Model;
class Importer
{
    protected $importModel;
    protected $errorHelper;
    protected $errorMessages;
    protected $arrayAdapterFactory;
    protected $validationResult;

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
            'import_images_file_dir' => '',
        );

        $this->importModel->setData($sourceData);
    }

    public function processImport($dataArray)
    {
        $this->_validateData($dataArray);
        $this->_importData();
    }

    protected function _validateData($dataArray)
    {
        $source = $this->arrayAdapterFactory->create(array('data'=>$dataArray));
        $this->validationResult = $this->importModel->validateSource($source);
    }

    protected function _importData()
    {
        $this->importModel->importSource();
        $this->_handleImportResult();
    }
    
    public function getValidationResult()
    {
        return $this->validationResult;
    }
    
    public function getLogTrace()
    {
        return $this->importModel->getFormatedLogTrace();
    }
    
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    protected function _handleImportResult()
    {
        $errorAggregator = $this->importModel->getErrorAggregator();
        $this->errorMessages = $this->errorHelper->getImportErrorMessages($errorAggregator);
        if (!$this->importModel->getErrorAggregator()->hasToBeTerminated()) {
            $this->importModel->invalidateIndex();
        }
    }
}