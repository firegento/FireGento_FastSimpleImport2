FireGento_FastSimpleImport2
===================
Wrapper for Magento 2 ImportExport functionality, which imports products and customers from arrays

Usage
-----
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

You can find more examples in our [demo module](https://github.com/magento-hackathon/FireGento_FastSimpleImport2_Demo). 
We have implemented a few command line commands which demonstrate the usage of FastSimpleImport2 in a custom module.  
        

Requirements
------------
- Magento >= 2.0.0
- PHP >= 5.5.0

Support
-------
If you encounter any problems or bugs, please create an issue on [GitHub](https://github.com/magento-hackathon/FireGento_FastSimpleImport2/issues).

Contribution
------------
Any contribution to the development of FastSimpleImport2 is highly welcome. The best possibility to provide any code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Developer
---------
FireGento Team
* Website: [http://firegento.com](http://firegento.com)
* Twitter: [@firegento](https://twitter.com/firegento)

Installation Instructions with Composer
---------------------------------------------

    composer config repositories.firegento_fastsimpleimport vcs https://github.com/firegento/FireGento_FastSimpleImport2
    composer require firegento/fastsimpleimport dev-master
    bin/magento module:enable FireGento_FastSimpleImport
    bin/magento setup:upgrade
    
    
Licence
-------
[GNU General Public License, version 3 (GPLv3)](http://opensource.org/licenses/gpl-3.0)

Copyright
---------
(c) 2016 FireGento Team
