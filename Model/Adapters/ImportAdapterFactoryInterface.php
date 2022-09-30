<?php
/**
 * @copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3
 */

namespace FireGento\FastSimpleImport\Model\Adapters;

use Magento\ImportExport\Model\Import\AbstractSource;

interface ImportAdapterFactoryInterface
{
    public function create(array $data = []): AbstractSource;
}
