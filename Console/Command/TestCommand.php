<?php

namespace FireGento\FastSimpleImport2\Console\Command;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\ObjectManager\ConfigLoader;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\App\State;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\ImportExport\Model\Import;
/**
 * Class TestCommand
 * @package FireGento\FastSimpleImport2\Console\Command
 *
 */
class TestCommand extends Command
{
    /**
     * Key for languages parameter
     */
    const BEHAVIOUR_OPTION = 'behaviour';


    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Object manager factory
     *
     * @var ObjectManagerFactory
     */
    private $objectManagerFactory;

    /**
     * Constructor
     *
     * @param ObjectManagerFactory $objectManagerFactory
     */
    public function __construct(ObjectManagerFactory $objectManagerFactory)
    {
        $this->objectManagerFactory = $objectManagerFactory;
        parent::__construct();
    }


    protected function configure()
    {
        $this->setName('firegento:fastsimpleimport2:test')
            ->setDescription('Test the import functianlity ');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omParams = $_SERVER;
        $omParams[StoreManager::PARAM_RUN_CODE] = 'admin';
        $omParams[Store::CUSTOM_ENTRY_POINT_PARAM] = true;
        $this->objectManager = $this->objectManagerFactory->create($omParams);

        $area = FrontNameResolver::AREA_CODE;

        /** @var \Magento\Framework\App\State $appState */
        $appState = $this->objectManager->get('Magento\Framework\App\State');
        $appState->setAreaCode($area);
        $configLoader = $this->objectManager->get('Magento\Framework\ObjectManager\ConfigLoaderInterface');
        $this->objectManager->configure($configLoader->load($area));
        
        $output->writeln('Import started');

        $time = microtime(true);
        
        /** @var \FireGento\FastSimpleImport2\Model\Importer $importerModel */
        $importerModel = $this->objectManager->create('FireGento\FastSimpleImport2\Model\Importer');

        $productsArray = $this->generateSimpleTestProducts();
        $importerModel->setBehavior(Import::BEHAVIOR_APPEND);
        $importerModel->setEntityCode('catalog_product');
        try {
            $importerModel->processImport($productsArray);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }

        $output->write($importerModel->getLogTrace());
        $output->write($importerModel->getErrorMessages());

        $output->writeln('Import finished. Elapsed time: ' . round(microtime(true) - $time, 2) . 's' . "\n");
    }

    /**
     *
     */
    protected function generateSimpleTestProducts()
    {
        $data = array();
        for ($i = 1; $i <= 10; $i++) {
            $data [] = array(
                'sku' => 'FIREGENTO-' . 99999+$i,
                'attribute_set_code' => 'Default',
                //'product_type' => 'simple',
                'product_websites' => 'base',
                'name' => 'FireGento Test Product ' . $i,
                'price' => '14.0000',
                //'visibility' => 'Catalog, Search',
                //'tax_class_name' => 'Taxable Goods',
                //'product_online' => '1',
                //'weight' => '1.0000',
                //'short_description' => NULL,
                //'description' => '',
            );
        }
        return $data;
    }
}