<?php
/**
 * Copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace FireGento\FastSimpleImport\Model;

use FireGento\FastSimpleImport\Helper\Config as ConfigHelper;
use FireGento\FastSimpleImport\Helper\ImportError as ImportErrorHelper;
use FireGento\FastSimpleImport\Model\Adapters\ImportAdapterFactoryInterface;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\ImportFactory;

class Importer
{
    private ImportFactory                          $importModelFactory;
    private ImportErrorHelper                      $errorHelper;
    private ImportAdapterFactoryInterface $importAdapterFactory;
    private ConfigHelper                           $configHelper;
    private ?bool                                  $validationResult;
    private ?array                                 $settings;
    private string                                 $logTrace = '';
    /**
     * @var array|string[]
     */
    private array $errorMessages = [];

    public function __construct(
        ImportFactory $importModelFactory,
        ImportErrorHelper $errorHelper,
        ImportAdapterFactoryInterface $importAdapterFactory,
        ConfigHelper $configHelper
    ) {
        $this->errorHelper = $errorHelper;
        $this->importAdapterFactory = $importAdapterFactory;
        $this->configHelper = $configHelper;
        $this->importModelFactory = $importModelFactory;
        $this->settings = [
            'entity'                           => $this->configHelper->getEntity(),
            'behavior'                         => $this->configHelper->getBehavior(),
            'ignore_duplicates'                => $this->configHelper->getIgnoreDuplicates(),
            'validation_strategy'              => $this->configHelper->getValidationStrategy(),
            'allowed_error_count'              => $this->configHelper->getAllowedErrorCount(),
            'import_images_file_dir'           => $this->configHelper->getImportFileDir(),
            '_import_multiple_value_separator' => Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
        ];
    }

    public function processImport(array $dataArray): bool
    {
        $validation = $this->validateData($dataArray);
        if ($validation) {
            $this->importData();
        }

        return $validation;
    }

    private function validateData(array $dataArray): bool
    {
        $importModel = $this->createImportModel();
        $source = $this->importAdapterFactory->create([
            'data'                   => $dataArray,
            'multipleValueSeparator' => $this->getMultipleValueSeparator(),
        ]);
        $this->validationResult = $importModel->validateSource($source);
        $this->addToLogTrace($importModel);
        return $this->validationResult;
    }

    public function createImportModel(): Import
    {
        $importModel = $this->importModelFactory->create();
        $importModel->setData($this->settings);
        return $importModel;
    }

    public function addToLogTrace($importModel)
    {
        $this->logTrace = $this->logTrace . $importModel->getFormatedLogTrace();
    }

    private function importData()
    {
        $importModel = $this->createImportModel();
        $importModel->importSource();
        $this->handleImportResult($importModel);
    }

    private function handleImportResult($importModel)
    {
        $errorAggregator = $importModel->getErrorAggregator();
        $this->errorMessages = $this->errorHelper->getImportErrorMessages($errorAggregator);
        $this->addToLogTrace($importModel);
        if (!$importModel->getErrorAggregator()->hasToBeTerminated()) {
            $importModel->invalidateIndex();
        }
    }

    public function setEntityCode(string $entityCode): self
    {
        $this->settings['entity'] = $entityCode;
        return $this;
    }

    public function setBehavior(string $behavior): self
    {
        $this->settings['behavior'] = $behavior;
        return $this;
    }

    public function setIgnoreDuplicates(bool $value): self
    {
        $this->settings['ignore_duplicates'] = $value;
        return $this;
    }

    public function setValidationStrategy(string $strategy): self
    {
        $this->settings['validation_strategy'] = $strategy;
        return $this;
    }

    public function setAllowedErrorCount(int $count): self
    {
        $this->settings['allowed_error_count'] = $count;
        return $this;
    }

    public function setImportImagesFileDir(string $dir): self
    {
        $this->settings['import_images_file_dir'] = $dir;
        return $this;
    }

    public function setMultipleValueSeparator(string $multipleValueSeparator): self
    {
        $this->settings['_import_multiple_value_separator'] = $multipleValueSeparator;
        return $this;
    }

    public function setImportAdapterFactory(ImportAdapterFactoryInterface $importAdapterFactory): self
    {
        $this->importAdapterFactory = $importAdapterFactory;
        return $this;
    }

    public function getValidationResult(): bool
    {
        return $this->validationResult;
    }

    public function getLogTrace(): string
    {
        return $this->logTrace;
    }

    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    public function getMultipleValueSeparator(): string
    {
        return $this->settings['_import_multiple_value_separator'];
    }

    public function getImportAdapterFactory(): ImportAdapterFactoryInterface
    {
        return $this->importAdapterFactory;
    }
}
