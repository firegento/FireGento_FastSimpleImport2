<?php
/**
 * Copyright © 2016 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */
namespace FireGento\FastSimpleImport2\Model;
class Importer
{
    /**
     * @var \Magento\ImportExport\Model\Import
     */
    protected $importModel;
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
     * Importer constructor.
     * @param \Magento\ImportExport\Model\Import $importModel
     * @param \FireGento\FastSimpleImport2\Helper\ImportError $errorHelper
     * @param ArrayAdapterFactory $arrayAdapterFactory
     * @param \FireGento\FastSimpleImport2\Helper\Config $configHelper
     */
    public function __construct(
        \Magento\ImportExport\Model\Import $importModel,
        \FireGento\FastSimpleImport2\Helper\ImportError $errorHelper,
        \FireGento\FastSimpleImport2\Model\ArrayAdapterFactory $arrayAdapterFactory,
        \FireGento\FastSimpleImport2\Helper\Config $configHelper
    )
    {
        $this->importModel = $importModel;
        $this->errorHelper = $errorHelper;
        $this->arrayAdapterFactory = $arrayAdapterFactory;
        $this->configHelper = $configHelper;

        $sourceData = [
            'entity' => $this->configHelper->getEntity(),
            'behavior' => $this->configHelper->getBehavior(),
            'validation_strategy' => $this->configHelper->getValidationStrategy(),
            'allowed_error_count' => $this->configHelper->getAllowedErrorCount(),
            'import_images_file_dir' => $this->configHelper->getImportFileDir(),
        ];

        $this->importModel->setData($sourceData);
    }

    public function processImport($dataArray)
    {
        if ($this->_validateData($dataArray)) {
            $this->_importData();
        }
    }

    protected function _validateData($dataArray)
    {
        $source = $this->arrayAdapterFactory->create(array('data' => $dataArray));
        $this->validationResult = $this->importModel->validateSource($source);
        return $this->validationResult;
    }

    protected function _importData()
    {
        $this->importModel->importSource();
        $this->_handleImportResult();
    }

    protected function _handleImportResult()
    {
        $errorAggregator = $this->importModel->getErrorAggregator();
        $this->errorMessages = $this->errorHelper->getImportErrorMessages($errorAggregator);
        if (!$this->importModel->getErrorAggregator()->hasToBeTerminated()) {
            $this->importModel->invalidateIndex();
        }
    }

    /**
     * @param string $entityCode
     */
    public function setEntityCode($entityCode)
    {
        $this->importModel->setData('entity', $entityCode);
    }

    /**
     * @param string $behavior
     */
    public function setBehavior($behavior)
    {
        $this->importModel->setData('behavior', $behavior);
    }

    /**
     * @param string $strategy
     */
    public function setValidationStrategy($strategy)
    {
        $this->importModel->setData('validation_strategy', $strategy);
    }

    /**
     * @param int $count
     */
    public function setAllowedErrorCount($count)
    {
        $this->importModel->setData('allowed_error_count', $count);
    }

    /**
     * @param string $dir
     */
    public function setImportImagesFileDir($dir)
    {
        $this->importModel->setData('import_images_file_dir', $dir);
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

}