Installation
==========================

Installation Instructions for latest stable Version
---------------------------------------------

    composer require firegento/fastsimpleimport 
    bin/magento module:enable FireGento_FastSimpleImport
    bin/magento setup:upgrade
    
    

Installation Instructions for the latest development/contribution Version
---------------------------------------------

    composer config repositories.firegento_fastsimpleimport vcs https://github.com/firegento/FireGento_FastSimpleImport2
    composer require firegento/fastsimpleimport dev-master
    bin/magento module:enable FireGento_FastSimpleImport
    bin/magento setup:upgrade