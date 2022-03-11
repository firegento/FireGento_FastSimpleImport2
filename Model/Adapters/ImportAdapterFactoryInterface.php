<?php
/**
 * Copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace FireGento\FastSimpleImport\Model\Adapters;

use Magento\ImportExport\Model\Import\AbstractSource;

interface ImportAdapterFactoryInterface
{
    public function create(array $data = []): AbstractSource;
}
