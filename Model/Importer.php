<?php
/**
 * @copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3
 */

namespace FireGento\FastSimpleImport\Model;

use FireGento\FastSimpleImport\Exception\ImportException;
use FireGento\FastSimpleImport\Exception\RuntimeException;
use FireGento\FastSimpleImport\Exception\ValidationException;
use FireGento\FastSimpleImport\Model\Adapters\ImportAdapterFactoryInterface;
use FireGento\FastSimpleImport\Service\ImportErrorService as ImportErrorService;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ImportFactory;

class Importer
{
    private ImportFactory                 $importModelFactory;
    private ImportErrorService            $importErrorService;
    private ImportAdapterFactoryInterface $importAdapterFactory;
    private Config                        $config;
    private ?bool                         $validationResult;
    private ?array                        $settings;
    private string                        $logTrace      = '';
    private array                         $errorMessages = [];
    private ?Import                       $importModel = null;

    public function __construct(
        ImportFactory $importModelFactory,
        ImportErrorService $importErrorService,
        ImportAdapterFactoryInterface $importAdapterFactory,
        Config $config
    ) {
        $this->importErrorService = $importErrorService;
        $this->importAdapterFactory = $importAdapterFactory;
        $this->config = $config;
        $this->importModelFactory = $importModelFactory;
        $this->settings = [
            'entity'                           => $this->config->getEntity(),
            'behavior'                         => $this->config->getBehavior(),
            'ignore_duplicates'                => $this->config->getIgnoreDuplicates(),
            'validation_strategy'              => $this->config->getValidationStrategy(),
            'allowed_error_count'              => $this->config->getAllowedErrorCount(),
            '_import_multiple_value_separator' => Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
        ];
    }

    /**
     * @throws ValidationException
     * @throws ImportException
     */
    public function processImport(array $dataArray): void
    {
        $this->validateData($dataArray);
        $this->importData();
    }

    /**
     * @throws ValidationException
     */
    private function validateData(array $dataArray): void
    {
        $importModel = $this->getImportModel();
        $source = $this->importAdapterFactory->create([
            'data'                   => $dataArray,
            'multipleValueSeparator' => $this->getMultipleValueSeparator(),
        ]);
        $this->validationResult = $importModel->validateSource($source);
        $errorAggregator = $this->getErrorAggregator();
        if ($errorAggregator->hasToBeTerminated()) {
            throw new ValidationException(
                $this->importErrorService->getImportErrorMessagesAsString($errorAggregator)
            );
        }
    }

    /**
     * @throws ImportException
     */
    private function importData()
    {
        $this->resetImportModel();
        $importModel = $this->getImportModel();
        $importModel->importSource();
        $this->handleImportResult($importModel);
    }

    /**
     * @throws ImportException
     */
    private function handleImportResult(Import $importModel)
    {
        $errorAggregator = $this->getErrorAggregator();
        $this->errorMessages = $this->importErrorService->getImportErrorMessages($errorAggregator);
        if (!$errorAggregator->hasToBeTerminated()) {
            $importModel->invalidateIndex();
        } else {
            throw new ImportException(
                $this->importErrorService->getImportErrorMessagesAsString($errorAggregator)
            );
        }
    }

    public function getImportModel(): Import
    {
        if ($this->importModel === null) {
            $this->importModel = $this->importModelFactory->create();
            $this->importModel->setData($this->settings);
        }
        return $this->importModel;
    }

    public function resetImportModel(): void
    {
        $this->importModel = null;
    }

    public function getErrorAggregator(): ProcessingErrorAggregatorInterface
    {
        return $this->getImportModel()->getErrorAggregator();
    }

    /**
     * @throws RuntimeException
     */
    public function setEntityCode(string $entityCode): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['entity'] = $entityCode;
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function setBehavior(string $behavior): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['behavior'] = $behavior;
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function setIgnoreDuplicates(bool $value): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['ignore_duplicates'] = $value;
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function setValidationStrategy(string $strategy): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['validation_strategy'] = $strategy;
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function setValidationStrategySkipErrors(): self
    {
        return $this->setValidationStrategy(ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_SKIP_ERRORS);
    }

    /**
     * @throws RuntimeException
     */
    public function setValidationStrategyStopOnErrors(): self
    {
        return $this->setValidationStrategy(ProcessingErrorAggregatorInterface::VALIDATION_STRATEGY_STOP_ON_ERROR);
    }

    /**
     * @throws RuntimeException
     */
    public function setAllowedErrorCount(int $count): self
    {
        $this->assertImportModelNotInitialized();
        $this->settings['allowed_error_count'] = $count;
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function setMultipleValueSeparator(string $multipleValueSeparator): self
    {
        $this->assertImportModelNotInitialized();
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

    /**
     * @throws RuntimeException
     */
    private function assertImportModelNotInitialized(): void
    {
        if ($this->importModel !== null) {
            throw new RuntimeException(
                __(
                    'Method %1 cannot be called after initializing the import model.',
                    __METHOD__
                )
            );
        }
    }
}
