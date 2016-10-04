Installation
==========================

Installation Instructions with Composer
---------------------------------------------

    composer require firegento/fastsimpleimport 
    bin/magento module:enable FireGento_FastSimpleImport
    bin/magento setup:upgrade
    
    

Installation Instructions with Composer(Master Branch)
---------------------------------------------

    composer config repositories.firegento_fastsimpleimport vcs https://github.com/firegento/FireGento_FastSimpleImport2
    composer require firegento/fastsimpleimport dev-master
    bin/magento module:enable FireGento_FastSimpleImport
    bin/magento setup:upgrade