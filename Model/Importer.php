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
     * Importer constructor.
     * @param \Magento\ImportExport\Model\Import $importModel
     * @param \FireGento\FastSimpleImport2\Helper\ImportError $errorHelper
     * @param ArrayAdapterFactory $arrayAdapterFactory
     */
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
            'entity' => $this->getEntityCode(),
            'behavior' => $this->getBehavior(),
            'validation_strategy' => 'validation-stop-on-errors',
            'allowed_error_count' => 10,
            'import_images_file_dir' => '',
        );

        $this->importModel->setData($sourceData);
    }

    /**
     * @return string
     */
    public function getEntityCode()
    {
        return $this->importModel->getData('entity');
    }

    /**
     * @param string $entityCode
     */
    public function setEntityCode($entityCode)
    {
        $this->importModel->setData('entity', $entityCode);
    }

    /**
     * @return string
     */
    public function getBehavior()
    {
        return $this->importModel->getData('behavior');
    }

    /**
     * @param string $behavior
     */
    public function setBehavior($behavior)
    {
        $this->importModel->setData('behavior', $behavior);
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