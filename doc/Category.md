Importing Categories
==========================

Category Import
---------------------------------------------

You can call the import from your own Magento 2 code. Example:

    /** @var \FireGento\FastSimpleImport\Model\Importer $importerModel */
    $importerModel = $this->objectManager->create('FireGento\FastSimpleImport\Model\Importer');

    $categoryArray = [
        [
            '_root' => 'Default Category',
            '_category' => 'FireGento TestCategory',
            'description' => 'Test',
            'is_active' => '1',
            'include_in_menu' => '1',
            'meta_description' => 'Meta Test',
            'available_sort_by' => 'position',
            'default_sort_by' => 'position',
        ],
    ];
    
    $importerModel->setEntityCode('catalog_category');
    

    try {
        $importerModel->processImport($categoryArray);
    } catch (\Exception $e) {
        print_r($e->getMessage());
    }
    
    print_r($importerModel->getLogTrace());
    print_r($importerModel->getErrorMessages());

