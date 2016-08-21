<?php
/**
 * Copyright © 2016 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace FireGento\FastSimpleImport\Model\Adapters;
interface ImportAdapterFactoryInterface{
    /**
     * @return \Magento\ImportExport\Model\Import\AbstractSource
     */
    public function create(array $data = []);
}