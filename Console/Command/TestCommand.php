<?php

namespace FireGento\FastSimpleImport2\Console\Command;

use Magento\Framework\App\ObjectManager\ConfigLoader;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\App\State;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\Store\Model\StoreManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Store\Model\Store;
use Magento\Backend\App\Area\FrontNameResolver;
/**
 * Class TestCommand
 * @package FireGento\FastSimpleImport2\Console\Command
 *
 */
class TestCommand extends Command
{
    /**
     * @var \Magento\ImportExport\Model\Import
     */
    protected $importModel;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \FireGento\FastSimpleImport2\Helper\ImportError
     */
    protected $errorHelper;

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




        $output->writeln('Start Import');

        $time = microtime(true);
        $importerModel = $this->objectManager->create('FireGento\FastSimpleImport2\Model\Importer');
        $importerModel->importData();

        $output->writeln('Elapsed time: ' . round(microtime(true) - $time, 2) . 's' . "\n");


        return 0;
    }



    /**
     *
     */
    protected
    function generateSimpleTestProducts()
    {
        $data = array();
        for ($i = 1; $i <= 10; $i++) {
            $randomString = $this->getUniqueCode(20);
            $data[] = array(
                'sku' => $i,
                '_type' => 'simple',
                '_attribute_set' => 'Default',
                '_product_websites' => 'base',
                '_category' => array(1, 3),
                'name' => $randomString,
                'price' => 0.99,
                'special_price' => 0.90,
                'cost' => 0.50,
                'description' => 'Default',
                'short_description' => 'Default',
                'meta_title' => 'Default',
                'meta_description' => 'Default',
                'meta_keyword' => 'Default',
                'weight' => 11,
                'status' => 1,
                'visibility' => 4,
                'tax_class_id' => 2,
                'qty' => 0,
                'is_in_stock' => 0,
                'enable_googlecheckout' => '1',
                'gift_message_available' => '0',
                'url_key' => strtolower($randomString),
            );
        }
        return $data;
    }

    protected
    function getUniqueCode($length = "")
    {
        $code = md5(uniqid(rand(), true));
        if ($length != "") return substr($code, 0, $length);
        else return $code;
    }
}