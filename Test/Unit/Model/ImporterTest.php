<?php
/**
 * @copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3
 */

namespace FireGento\FastSimpleImport\Test\Unit\Model;

use FireGento\FastSimpleImport\Exception\ImportException;
use FireGento\FastSimpleImport\Exception\ValidationException;
use FireGento\FastSimpleImport\Model\Adapters\ImportAdapterFactoryInterface;
use FireGento\FastSimpleImport\Model\Config;
use FireGento\FastSimpleImport\Model\Importer;
use FireGento\FastSimpleImport\Service\ImportErrorService;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\ImportFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImporterTest extends TestCase
{
    /** @var Import|MockObject */
    private $validationModel;

    /** @var Import|MockObject */
    private $importModel;

    /** @var ProcessingErrorAggregatorInterface|MockObject */
    private $errorAggregator;

    /** @var ImportFactory|MockObject */
    private $importModelFactory;

    /** @var Importer */
    private $importer;

    protected function setUp(): void
    {
        $this->errorAggregator = $this->createMock(ProcessingErrorAggregatorInterface::class);
        $this->validationModel = $this->createMock(Import::class);
        $this->importModel = $this->createMock(Import::class);
        foreach ([$this->validationModel, $this->importModel] as $model) {
            $model->method('getErrorAggregator')->willReturn($this->errorAggregator);
        }

        $this->importModelFactory = $this->getMockBuilder(ImportFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->importModelFactory->method('create')
            ->willReturnOnConsecutiveCalls($this->validationModel, $this->importModel);

        $importErrorService = $this->createMock(ImportErrorService::class);
        $importErrorService->method('getImportErrorMessages')->willReturn([]);
        $importErrorService->method('getImportErrorMessagesAsString')->willReturn('invalid row');

        $adapterFactory = $this->createMock(ImportAdapterFactoryInterface::class);
        $adapterFactory->method('create')->willReturn($this->createMock(AbstractSource::class));

        $this->importer = new Importer(
            $this->importModelFactory,
            $importErrorService,
            $adapterFactory,
            $this->createMock(Config::class)
        );
    }

    public function testGetLogTraceContainsValidationAndImportTrace(): void
    {
        $this->validationModel->method('validateSource')->willReturn(true);
        $this->validationModel->method('getFormatedLogTrace')->willReturn("1: Begin data validation\n");
        $this->importModel->method('getFormatedLogTrace')->willReturn("1: Begin import of \"catalog_product\"\n");
        $this->errorAggregator->method('hasToBeTerminated')->willReturn(false);

        $this->importer->processImport([['sku' => 'a']]);

        $this->assertSame(
            "1: Begin data validation\n1: Begin import of \"catalog_product\"\n",
            $this->importer->getLogTrace()
        );
    }

    public function testGetLogTraceContainsValidationTraceWhenValidationFails(): void
    {
        $this->validationModel->method('validateSource')->willReturn(false);
        $this->validationModel->method('getFormatedLogTrace')->willReturn("1: Begin data validation\n");
        $this->errorAggregator->method('hasToBeTerminated')->willReturn(true);

        try {
            $this->importer->processImport([['sku' => '']]);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame("1: Begin data validation\n", $this->importer->getLogTrace());
        }
    }

    public function testGetLogTraceContainsImportTraceWhenImportFails(): void
    {
        $this->validationModel->method('validateSource')->willReturn(true);
        $this->validationModel->method('getFormatedLogTrace')->willReturn("1: Begin data validation\n");
        $this->importModel->method('getFormatedLogTrace')->willReturn("1: Begin import\n");
        $this->errorAggregator->method('hasToBeTerminated')->willReturnOnConsecutiveCalls(false, true);

        try {
            $this->importer->processImport([['sku' => 'a']]);
            $this->fail('Expected ImportException was not thrown.');
        } catch (ImportException $e) {
            $this->assertSame("1: Begin data validation\n1: Begin import\n", $this->importer->getLogTrace());
        }
    }

    public function testGetLogTraceContainsImportTraceWhenImportSourceThrows(): void
    {
        $this->validationModel->method('validateSource')->willReturn(true);
        $this->validationModel->method('getFormatedLogTrace')->willReturn("1: Begin data validation\n");
        $this->importModel->method('importSource')->willThrowException(new \RuntimeException('boom'));
        $this->importModel->method('getFormatedLogTrace')->willReturn("1: Begin import\n");
        $this->errorAggregator->method('hasToBeTerminated')->willReturn(false);

        try {
            $this->importer->processImport([['sku' => 'a']]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame("1: Begin data validation\n1: Begin import\n", $this->importer->getLogTrace());
        }
    }

    public function testLogTraceIsResetOnEachProcessImportCall(): void
    {
        $secondValidationModel = $this->createMock(Import::class);
        $secondImportModel = $this->createMock(Import::class);
        foreach ([$secondValidationModel, $secondImportModel] as $model) {
            $model->method('getErrorAggregator')->willReturn($this->errorAggregator);
        }
        $factory = $this->getMockBuilder(ImportFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $factory->method('create')->willReturnOnConsecutiveCalls(
            $this->validationModel,
            $this->importModel,
            $secondValidationModel,
            $secondImportModel
        );
        $adapterFactory = $this->createMock(ImportAdapterFactoryInterface::class);
        $adapterFactory->method('create')->willReturn($this->createMock(AbstractSource::class));
        $importer = new Importer(
            $factory,
            $this->createMock(ImportErrorService::class),
            $adapterFactory,
            $this->createMock(Config::class)
        );
        $this->validationModel->method('validateSource')->willReturn(true);
        $this->validationModel->method('getFormatedLogTrace')->willReturn("1: first validation\n");
        $this->importModel->method('getFormatedLogTrace')->willReturn("1: first import\n");
        $secondValidationModel->method('validateSource')->willReturn(true);
        $secondValidationModel->method('getFormatedLogTrace')->willReturn("1: second validation\n");
        $secondImportModel->method('getFormatedLogTrace')->willReturn("1: second import\n");
        $this->errorAggregator->method('hasToBeTerminated')->willReturn(false);

        $importer->processImport([['sku' => 'a']]);
        $importer->processImport([['sku' => 'b']]);

        $this->assertSame("1: second validation\n1: second import\n", $importer->getLogTrace());
    }
}
