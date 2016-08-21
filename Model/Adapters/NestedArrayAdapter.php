<?php
/**
 * *
 *  * Copyright © Elias Kotlyar - All rights reserved.
 *  * See LICENSE.md bundled with this module for license details.
 *
 */
namespace FireGento\FastSimpleImport\Model\Adapters;

class NestedArrayAdapter extends ArrayAdapter
{
    /**
     * ArrayAdapter constructor.
     * @param array $data
     */
    public function __construct($data)
    {
        parent::__construct($data);
        foreach ($this->_array as &$row) {
            foreach ($row as $colName => &$value)
                if (is_array($value)) {
                    $this->convertToArray($value);
                }
        }
        //print_r($this->_array);

    }

    /**
     * Transform nested array to string
     *
     * @param array $line
     * @return array
     */
    protected function convertToArray(&$line)
    {
        $implodeStr = ', ';
        $arr = array_map(
            function ($value, $key) use (&$implodeStr) {
                if (is_array($value) && is_numeric($key)) {
                    $this->convertToArray($value);
                    $implodeStr = '|';
                    return $value;
                }
                return sprintf("%s=%s", $key, $value);
            },
            $line,
            array_keys($line)
        );

        $line = implode(
            $implodeStr, $arr
        );
    }
}
