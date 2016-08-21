<?php
/**
 * Copyright © 2016 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */
namespace FireGento\FastSimpleImport\Model;
class Importer
{
    /**
     * @var \FireGento\FastSimpleImport\Helper\ImportError
     */
    protected $errorHelper;
    /**
     * @var
     */
    protected $errorMessages;
    /**
     * @var \FireGento\FastSimpleImport\Model\Adapters\ImportAdapterFactoryInterface
     */
    protected $importAdapterFactory;
    /**
     * @var
     */
    protected $validationResult;
    /**
     * @var \FireGento\FastSimpleImport\Helper\Config
     */
    protected $configHelper;
    /**
     * @var array
     */
    protected $settings;
    /**
     * @var string
     */
    protected $logTrace = "";
    /**
     * @var \Magento\ImportExport\Model\ImportFactory
     */
    private $importModelFactory;

    /**
     * Importer constructor.
     * @param \Magento\ImportExport\Model\Import $importModel
     * @param \FireGento\FastSimpleImport\Helper\ImportError $errorHelper
     * @param \FireGento\FastSimpleImport\Model\Adapters\ImportAdapterFactoryInterface $importAdapterFactory
     * @param \FireGento\FastSimpleImport\Helper\Config $configHelper
     */
    public function __construct(
        \Magento\ImportExport\Model\ImportFactory $importModelFactory,
        \FireGento\FastSimpleImport\Helper\ImportError $errorHelper,
        \FireGento\FastSimpleImport\Model\Adapters\ImportAdapterFactoryInterface $importAdapterFactory,
        \FireGento\FastSimpleImport\Helper\Config $configHelper
    )
    {

        $this->errorHelper = $errorHelper;
        $this->importAdapterFactory = $importAdapterFactory;
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
     * @return Adapters\ImportAdapterFactoryInterface
     */
    public function getImportAdapterFactory()
    {
        return $this->importAdapterFactory;
    }

    /**
     * @param Adapters\ImportAdapterFactoryInterface $importAdapterFactory
     */
    public function setImportAdapterFactory($importAdapterFactory)
    {
        $this->importAdapterFactory = $importAdapterFactory;
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
        $source = $this->importAdapterFactory->create(array('data' => $dataArray));
        $this->validationResult = $importModel->validateSource($source);
        $this->addToLogTrace($importModel);
        return $this->validationResult;
    }

    /**
     * @return \Magento\ImportExport\Model\Import
     */
    public function createImportModel()
    {
        $importModel = $this->importModelFactory->create();
        $importModel->setData($this->settings);
        return $importModel;
    }

    public function addToLogTrace($importModel)
    {
        $this->logTrace = $this->logTrace . $importModel->getFormatedLogTrace();
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

    public function getLogTrace()
    {
        return $this->logTrace;
    }

    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

}