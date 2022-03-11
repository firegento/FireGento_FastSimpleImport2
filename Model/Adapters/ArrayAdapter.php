<?php
/**
 * Copyright © 2016 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace FireGento\FastSimpleImport\Model\Adapters;

use Magento\ImportExport\Model\Import\AbstractSource;

class ArrayAdapter extends AbstractSource
{
    private int     $position = 0;
    protected array $array    = [];

    public function __construct(
        array $data
    ) {
        $this->array = $data;
        $colnames = array_keys($this->current());
        parent::__construct($colnames);
    }

    /**
     * Go to given position and check if it is valid
     *
     * @param int $position
     * @return void
     * @throws \OutOfBoundsException
     */
    public function seek($position)
    {
        $this->position = $position;

        if (!$this->valid()) {
            throw new \OutOfBoundsException("Invalid seek position ($position)");
        }
    }

    /**
     * Rewind to starting position
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Get data at current position
     */
    public function current(): array
    {
        return $this->array[$this->position];
    }

    /**
     * Get current position
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Set pointer to next position
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Is current position valid?
     */
    public function valid(): bool
    {
        return isset($this->array[$this->position]);
    }

    /**
     * @return array|string[]
     */
    public function getColNames(): array
    {
        $colNames = [];
        foreach ($this->array as $row) {
            foreach (array_keys($row) as $key) {
                if (!is_numeric($key) && !isset($colNames[$key])) {
                    $colNames[$key] = $key;
                }
            }
        }
        return $colNames;
    }

    public function setValue(string $key, $value): void
    {
        if (!$this->valid()) {
            return;
        }

        $this->array[$this->position][$key] = $value;
    }

    public function unsetValue(string $key): void
    {
        if (!$this->valid()) {
            return;
        }

        unset($this->array[$this->position][$key]);
    }

    /**
     * Render next row
     *
     * Return array or false on error
     *
     * @return array|false
     */
    protected function _getNextRow()
    {
        $this->next();
        return $this->current();
    }
}
