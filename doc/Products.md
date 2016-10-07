Importing Products
==========================

Simple Product
---------------------------------------------

You can call the import from your own Magento 2 code. Example:

    /** @var \FireGento\FastSimpleImport\Model\Importer $importerModel */
    $importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');

    $productsArray = [
        [
            'sku' => 'firegento-test',
            'attribute_set_code' => 'Default',
            'product_type' => 'simple',
            'product_websites' => 'base',
            'name' => 'FireGento Test Product',
            'price' => '14.0000',
        ],
    ];

    try {
        $importerModel->processImport($productsArray);
    } catch (\Exception $e) {
        $output->writeln($e->getMessage());
    }
    
    print_r($importerModel->getLogTrace());
    print_r($importerModel->getErrorMessages());

You can find more examples in our [demo module](https://github.com/firegento/FireGento_FastSimpleImport2_Demo).
We have implemented a few command line commands which demonstrate the usage of FastSimpleImport in a custom module.
        