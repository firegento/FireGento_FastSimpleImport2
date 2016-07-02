<?php
/**
 * Copyright © 2016 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */
namespace FireGento\FastSimpleImport2\Model;
class Importer
{
    /**
     * @var \FireGento\FastSimpleImport2\Helper\ImportError
     */
    protected $errorHelper;
    /**
     * @var
     */
    protected $errorMessages;
    /**
     * @var ArrayAdapterFactory
     */
    protected $arrayAdapterFactory;
    /**
     * @var
     */
    protected $validationResult;
    /**
     * @var \FireGento\FastSimpleImport2\Helper\Config
     */
    protected $configHelper;
    /**
     * @var \Magento\ImportExport\Model\ImportFactory
     */
    private $importModelFactory;
    /**
     * @var array
     */
    protected $settings;
    /**
     * @var string
     */
    protected $logTrace = "";

    /**
     * Importer constructor.
     * @param \Magento\ImportExport\Model\Import $importModel
     * @param \FireGento\FastSimpleImport2\Helper\ImportError $errorHelper
     * @param ArrayAdapterFactory $arrayAdapterFactory
     * @param \FireGento\FastSimpleImport2\Helper\Config $configHelper
     */
    public function __construct(
        \Magento\ImportExport\Model\ImportFactory $importModelFactory,
        \FireGento\FastSimpleImport2\Helper\ImportError $errorHelper,
        \FireGento\FastSimpleImport2\Model\ArrayAdapterFactory $arrayAdapterFactory,
        \FireGento\FastSimpleImport2\Helper\Config $configHelper
    )
    {

        $this->errorHelper = $errorHelper;
        $this->arrayAdapterFactory = $arrayAdapterFactory;
        $this->configHelper = $configHelper;
        $this->importModelFactory = $importModelFactory;
        $this->settings = [
            'entity' => $this->configHelper->getEntity(),
            'behavior' => $this->configHelper->getBehavior(),
            'validation_strategy' => $this->configHelper->getValidationStrategy(),
            'allowed_error_count' => $this->configHelper->getAllowedErrorCount(),
            'import_images_file_dir' => $this->configHelper->getImportFileDir(),
        ];
    }

    /**
     * @return \Magento\ImportExport\Model\Import
     */
    public function createImportModel(){
        $importModel = $this->importModelFactory->create();
        $importModel->setData($this->settings);
        return $importModel;
    }

    public function processImport($dataArray)
    {
        if ($this->_validateData($dataArray)) {
            $this->_importData();
        }
    }

    protected function _validateData($dataArray)
    {
        $importModel = $this->createImportModel();
        $source = $this->arrayAdapterFactory->create(array('data' => $dataArray));
        $this->validationResult = $importModel->validateSource($source);
        $this->addToLogTrace($importModel);
        return $this->validationResult;
    }

    protected function _importData()
    {
        $importModel = $this->createImportModel();
        $importModel->importSource();
        $this->_handleImportResult($importModel);
    }

    protected function _handleImportResult($importModel)
    {
        $errorAggregator = $importModel->getErrorAggregator();
        $this->errorMessages = $this->errorHelper->getImportErrorMessages($errorAggregator);
        $this->addToLogTrace($importModel);
        if (!$importModel->getErrorAggregator()->hasToBeTerminated()) {
            $importModel->invalidateIndex();
        }
    }

    /**
     * @param string $entityCode
     */
    public function setEntityCode($entityCode)
    {
        $this->settings['entity'] = $entityCode;

    }

    /**
     * @param string $behavior
     */
    public function setBehavior($behavior)
    {
        $this->settings['behavior'] = $behavior;
    }

    /**
     * @param string $strategy
     */
    public function setValidationStrategy($strategy)
    {
        $this->settings['validation_strategy'] = $strategy;
    }

    /**
     * @param int $count
     */
    public function setAllowedErrorCount($count)
    {
        $this->settings['allowed_error_count'] = $count;
    }

    /**
     * @param string $dir
     */
    public function setImportImagesFileDir($dir)
    {
        $this->settings['import_images_file_dir'] = $dir;
    }

    public function getValidationResult()
    {
        return $this->validationResult;
    }

    public function addToLogTrace($importModel){
        $this->logTrace = $this->logTrace.$importModel->getFormatedLogTrace();
    }
    public function getLogTrace()
    {
        return $this->logTrace;
    }

    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

}