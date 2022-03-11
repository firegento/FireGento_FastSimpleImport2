<?php
/**
 * Copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace FireGento\FastSimpleImport\Helper;

use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

class ImportError extends \Magento\Framework\App\Helper\AbstractHelper
{
    private const LIMIT_ERRORS_MESSAGE = 100;

    /**
     * TODO: Refactor Code
     *
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return string
     */
    public function getImportErrorMessages(
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        $resultText = '';
        if ($errorAggregator->getErrorsCount()) {
            $message = '';
            $counter = 0;
            foreach ($this->getErrorMessages($errorAggregator) as $error) {
                $message .= ++$counter . '. ' . $error . '<br>';
                if ($counter >= self::LIMIT_ERRORS_MESSAGE) {
                    break;
                }
            }
            if ($errorAggregator->hasFatalExceptions()) {
                foreach ($this->getSystemExceptions($errorAggregator) as $error) {
                    $message .= $error->getErrorMessage() . __('Additional data') . ': ' . $error->getErrorDescription()
                        . '</div>';
                }
            }
            try {
                $resultText .= '<strong>' . __('Following Error(s) has been occurred during importing process:')
                    . '</strong><br>' . '<div class="import-error-wrapper">' . __(
                        'Only first 100 errors are displayed here. '
                    ) . '<a href="' . '">' . __('Download full report') . '</a><br>' . '<div class="import-error-list">'
                    . $message . '</div></div>';
            } catch (\Exception $e) {
                foreach ($this->getErrorMessages($errorAggregator) as $errorMessage) {
                    $resultText .= $errorMessage;
                }
            }
        }

        return $resultText;
    }

    /**
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return array|string[]
     */
    private function getErrorMessages(ProcessingErrorAggregatorInterface $errorAggregator): array
    {
        $messages = [];
        $rowMessages = $errorAggregator->getRowsGroupedByErrorCode([], [AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
        foreach ($rowMessages as $errorCode => $rows) {
            $messages[] = $errorCode . ' ' . __('in rows:') . ' ' . implode(', ', $rows);
        }
        return $messages;
    }

    /**
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @return array|ProcessingError[]
     */
    private function getSystemExceptions(ProcessingErrorAggregatorInterface $errorAggregator): array
    {
        return $errorAggregator->getErrorsByCode([AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION]);
    }
}
