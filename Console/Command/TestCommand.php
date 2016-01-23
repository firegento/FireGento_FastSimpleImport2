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


        $productsArray = $this->generateSimpleTestProducts();
        $importerModel->importData($productsArray);

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
        for ($i = 1; $i <= 1; $i++) {
            $randomString = $this->getUniqueCode(20);
            $data [] = array(
                'sku' => 'FIREGENTO-1',
                'store_view_code' => NULL,
                'attribute_set_code' => 'Default',
                'product_type' => 'simple',
                'categories' => 'Default Category/Gear,Default Category/Gear/Fitness Equipment',
                'product_websites' => 'base',
                'name' => 'Sprite Yoga Strap 6 foot',
                'description' => '',
                'short_description' => NULL,
                'weight' => '1.0000',
                'product_online' => '1',
                'tax_class_name' => 'Taxable Goods',
                'visibility' => 'Catalog, Search',
                'price' => '14.0000',
                'special_price' => NULL,
                'special_price_from_date' => NULL,
                'special_price_to_date' => NULL,
                'url_key' => 'sprite-yoga-strap-6-foot222',
                'meta_title' => 'Meta Title',
                'meta_keywords' => 'meta1, meta2, meta3',
                'meta_description' => 'meta description',
                'thumbnail_image_label' => 'Image Label',
                'created_at' => '2015-10-25 03:34:19',
                'updated_at' => '2015-10-25 03:34:20',
                'new_from_date' => NULL,
                'new_to_date' => NULL,
                'display_product_options_in' => 'Block after Info Column',
                'map_price' => NULL,
                'msrp_price' => NULL,
                'map_enabled' => NULL,
                'gift_message_available' => NULL,
                'custom_design' => NULL,
                'custom_design_from' => NULL,
                'custom_design_to' => NULL,
                'custom_layout_update' => NULL,
                'page_layout' => NULL,
                'product_options_container' => NULL,
                'msrp_display_actual_price_type' => 'Use config',
                'country_of_manufacture' => NULL,
                'additional_attributes' => 'has_options=1,is_returnable=Use config,quantity_and_stock_status=In Stock,required_options=0',
                'qty' => '100.0000',
                'out_of_stock_qty' => '0.0000',
                'use_config_min_qty' => 0,
                'is_qty_decimal' => '0',
                'allow_backorders' => '0',
                'use_config_backorders' => '1',
                'min_cart_qty' => '1.0000',
                'use_config_min_sale_qty' => 0,
                'max_cart_qty' => '0.0000',
                'use_config_max_sale_qty' => 0,
                'is_in_stock' => '1',
                'notify_on_stock_below' => NULL,
                'use_config_notify_stock_qty' => '1',
                'manage_stock' => '0',
                'use_config_manage_stock' => '1',
                'use_config_qty_increments' => 0,
                'qty_increments' => '0.0000',
                'use_config_enable_qty_inc' => '1',
                'enable_qty_increments' => '0',
                'is_decimal_divided' => '0',
                'website_id' => '1',
                'deferred_stock_update' => '0',
                'use_config_deferred_stock_update' => '1',
                'related_skus' => '24-WG087,24-WG086',
                'crosssell_skus' => '24-WG087,24-WG086',
                'upsell_skus' => '24-WG087,24-WG086',

                'hide_from_product_page' => NULL,
                'custom_options' => 'name=Custom Yoga Option,type=drop_down,required=0,price=10.0000,price_type=fixed,sku=,option_title=Gold|name=Custom Yoga Option,type=drop_down,required=0,price=10.0000,price_type=fixed,sku=,option_title=Silver|name=Custom Yoga Option,type=drop_down,required=0,price=10.0000,price_type=fixed,sku=yoga3sku,option_title=Platinum',
                'bundle_price_type' => NULL,
                'bundle_sku_type' => NULL,
                'bundle_price_view' => NULL,
                'bundle_weight_type' => NULL,
                'bundle_values' => NULL,
                'associated_skus' => NULL,
                'image_label' => 'Image Label',
                'thumbnail_label' => 'Image Label',
                '_store' => NULL,
                '_attribute_set' => 'Default',
                '_product_websites' => 'base',
                'status' => '1',
                'news_from_date' => NULL,
                'news_to_date' => NULL,
                'options_container' => 'Block after Info Column',
                'minimal_price' => NULL,
                'msrp' => NULL,
                'msrp_enabled' => NULL,
                'special_from_date' => NULL,
                'special_to_date' => NULL,
                'min_qty' => '0.0000',
                'backorders' => '0',
                'min_sale_qty' => '1.0000',
                'max_sale_qty' => '0.0000',
                'notify_stock_qty' => NULL,
                '_related_sku' => '24-WG087,24-WG086',
                '_crosssell_sku' => '24-WG087,24-WG086',
                '_upsell_sku' => '24-WG087,24-WG086',
                'meta_keyword' => 'meta1, meta2, meta3',
                'price_type' => NULL,
                'price_view' => NULL,
                'weight_type' => NULL,
                'sku_type' => NULL,
                'has_options' => '1',
                'is_returnable' => 'Use config',
                'quantity_and_stock_status' => 'In Stock',
                'required_options' => '0',
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