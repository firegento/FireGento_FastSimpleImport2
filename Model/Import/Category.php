<?php
namespace FireGento\FastSimpleImport2\Model\Import;

use Magento\Framework\Stdlib\DateTime;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Entity Adapter for importing Magento Categories
 *
 *
 */
class Category extends \Magento\ImportExport\Model\Import\AbstractEntity
{
    /**
     * Size of bunch - part of entities to save in one step.
     */
    const BUNCH_SIZE = 20;

    /**
     * Data row scopes.
     */
    const SCOPE_DEFAULT = 1;
    const SCOPE_WEBSITE = 2;
    const SCOPE_STORE = 0;
    const SCOPE_NULL = -1;

    /**
     * Permanent column names.
     *
     * Names that begins with underscore is not an attribute. This name convention is for
     * to avoid interference with same attribute name.
     */
    const COL_STORE = '_store';
    const COL_ROOT = '_root';
    const COL_CATEGORY = '_category';

    /**
     * Error codes.
     */
    const ERROR_INVALID_SCOPE = 'invalidScope';
    const ERROR_INVALID_WEBSITE = 'invalidWebsite';
    const ERROR_INVALID_STORE = 'invalidStore';
    const ERROR_INVALID_ROOT = 'invalidRoot';
    const ERROR_CATEGORY_IS_EMPTY = 'categoryIsEmpty';
    const ERROR_PARENT_NOT_FOUND = 'parentNotFound';
    const ERROR_NO_DEFAULT_ROW = 'noDefaultRow';
    const ERROR_DUPLICATE_CATEGORY = 'duplicateCategory';
    const ERROR_DUPLICATE_SCOPE = 'duplicateScope';
    const ERROR_ROW_IS_ORPHAN = 'rowIsOrphan';
    const ERROR_VALUE_IS_REQUIRED = 'valueIsRequired';
    const ERROR_CATEGORY_NOT_FOUND_FOR_DELETE = 'categoryNotFoundToDelete';

    /**
     * Code of a primary attribute which identifies the entity group if import contains of multiple rows
     *
     * @var string
     */
    protected $masterAttributeCode = '_category';

    /**
     * Category attributes parameters.
     *
     *  [attr_code_1] => array(
     *      'options' => array(),
     *      'type' => 'text', 'price', 'textarea', 'select', etc.
     *      'id' => ..
     *  ),
     *  ...
     *
     * @var array
     */
    protected $_attributes = array();

    /**
     * Categories text-path to ID hash with roots checking.
     *
     * @var array
     */
    protected $_categoriesWithRoots = array();

    /**
     * Category entity DB table name.
     *
     * @var string
     */
    protected $_entityTable;

    /**
     * Attributes with index (not label) value.
     *
     * @var array
     */
    protected $_indexValueAttributes = array(
        'default_sort_by',
        'available_sort_by'
    );


    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::ERROR_INVALID_SCOPE => 'Invalid value in Scope column',
        self::ERROR_INVALID_WEBSITE => 'Invalid value in Website column (website does not exists?)',
        self::ERROR_INVALID_STORE => 'Invalid value in Store column (store does not exists?)',
        self::ERROR_INVALID_ROOT => 'Root category doesn\'t exist',
        self::ERROR_CATEGORY_IS_EMPTY => 'Category is empty',
        self::ERROR_PARENT_NOT_FOUND => 'Parent Category is not found, add parent first',
        self::ERROR_NO_DEFAULT_ROW => 'Default values row does not exists',
        self::ERROR_DUPLICATE_CATEGORY => 'Duplicate category',
        self::ERROR_DUPLICATE_SCOPE => 'Duplicate scope',
        self::ERROR_ROW_IS_ORPHAN => 'Orphan rows that will be skipped due default row errors',
        self::ERROR_VALUE_IS_REQUIRED => 'Required attribute \'%s\' has an empty value',
        self::ERROR_CATEGORY_NOT_FOUND_FOR_DELETE => 'Category not found for delete'
    );

    /**
     * Column names that holds images files names
     *
     * @var array
     */
    protected $_imagesArrayKeys = array(
        'thumbnail', 'image'
    );

    protected $_newCategory = array();

    /**
     * Column names that holds values with particular meaning.
     *
     * @var array
     */
    protected $_specialAttributes = array(
        self::COL_STORE, self::COL_ROOT, self::COL_CATEGORY
    );

    /**
     * Permanent entity columns.
     *
     * @var array
     */
    protected $_permanentAttributes= array(
        self::COL_ROOT, self::COL_CATEGORY
    );

    /**
     * All stores code-ID pairs.
     *
     * @var array
     */
    protected $_storeCodeToId = array();

    /**
     * Store ID to its website stores IDs.
     *
     * @var array
     */
    protected $_storeIdToWebsiteStoreIds = array();

    /**
     * Website code-to-ID
     *
     * @var array
     */
    protected $_websiteCodeToId = array();

    /**
     * Website code to store code-to-ID pairs which it consists.
     *
     * @var array
     */
    protected $_websiteCodeToStoreIds = array();

    /**
     * Media files uploader
     *
     * @var \Magento\CatalogImportExport\Model\Import\Uploader
     */
    protected $_fileUploader;

    /** @var bool */
    protected $_ignoreDuplicates = false;

    /** @var bool */
    protected $_unsetEmptyFields = false;

    /** @var bool|string */
    protected $_symbolEmptyFields = false;

    /** @var bool|string */
    protected $_symbolIgnoreFields = false;

    protected $_defaultAttributeSetId = 0;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var \FireGento\FastSimpleImport2\ResourceModel\Import\Category\StorageFactory
     */
    private $_storageFactory;
    /**
     * @var \Magento\Catalog\Model\Category
     */
    private $_defaultCategory;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection
     */
    private $_attributeCollection;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    private $_categoryCollection;
    /**
     * @var \Magento\ImportExport\Model\ResourceModel\Helper
     */
    private $resourceHelper;

    /**
     * Category constructor.
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\ImportExport\Model\ImportFactory $importFactory
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \FireGento\FastSimpleImport2\ResourceModel\Import\Category\StorageFactory $storageFactory
     * @param \Magento\Catalog\Model\Category $defaultCategory
     * @param \Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection $attributeCollection
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\ImportExport\Model\ImportFactory $importFactory,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\App\ResourceConnection $resource,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \FireGento\FastSimpleImport2\ResourceModel\Import\Category\StorageFactory $storageFactory,
        \Magento\Catalog\Model\Category $defaultCategory,
        \Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection $attributeCollection,
        \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection,
        \Magento\Eav\Model\Config $eavConfig,
        array $data = []
    )
    {
        parent::__construct(
            $string,
            $scopeConfig,
            $importFactory,
            $resourceHelper,
            $resource,
            $errorAggregator,
            $data
        );


        $this->_storeManager = $storeManager;
        $this->_storageFactory = $storageFactory;
        $this->_defaultCategory = $defaultCategory;
        $this->_attributeCollection = $attributeCollection;
        $this->_categoryCollection = $categoryCollection;
        $this->resourceHelper = $resourceHelper;

        $entityType = $eavConfig->getEntityType($this->getEntityTypeCode());
        $this->_entityTypeId = $entityType->getEntityTypeId();

        foreach ($this->_messageTemplates as $errorCode => $message) {
            $this->getErrorAggregator()->addErrorMessageTemplate($errorCode, $message);
        }


        $this
            ->_initOnTabAttributes()
            ->_initWebsites()
            ->_initStores()
            ->_initCategories()
            ->_initAttributes()
            ->_initAttributeSetId();
        /*

        /* @var $categoryResource Mage_Catalog_Model_Resource_Category */
        //$categoryResource = Mage::getModel('catalog/category')->getResource();
        //$this->_entityTable = $categoryResource->getEntityTable();



    }

    /**
     * Initialize the default attribute_set_id
     * @return $this
     */
    protected function _initAttributeSetId()
    {
        $this->_defaultAttributeSetId = $this->_defaultCategory->getDefaultAttributeSetId();
        return $this;
    }

    /**
     * Initialize customer attributes.
     *
     * @return $this
     */
    protected function _initAttributes()
    {


        foreach ($this->_attributeCollection as $attribute) {

            $this->_attributes[$attribute->getAttributeCode()] = array(
                'id' => $attribute->getId(),
                'is_required' => $attribute->getIsRequired(),
                'is_static' => $attribute->isStatic(),
                'rules' => $attribute->getValidateRules() ? unserialize($attribute->getValidateRules()) : null,
                'type' => \Magento\ImportExport\Model\Import::getAttributeType($attribute),
                'options' => $this->getAttributeOptions($attribute),
                'attribute' => $attribute
            );
        }

        return $this;
    }

    /**
     * Initialize categories text-path to ID hash.
     *
     * @return $this
     */
    protected function _initCategories()
    {

        $collection = $this->_categoryCollection->addNameToResult();

        foreach ($collection as $category) {

            $structure = explode('/', $category->getPath());
            $pathSize  = count($structure);
            if ($pathSize > 1) {
                $path = array();
                for ($i = 1; $i < $pathSize; $i++) {
                    $path[] = $collection->getItemById($structure[$i])->getName();
                }
                $rootCategoryName = array_shift($path);
                if (!isset($this->_categoriesWithRoots[$rootCategoryName])) {
                    $this->_categoriesWithRoots[$rootCategoryName] = array();
                }
                $index = $this->_implodeEscaped('/', $path);
                $this->_categoriesWithRoots[$rootCategoryName][$index] = array(
                    'entity_id' => $category->getId(),
                    'path' => $category->getPath(),
                    'level' => $category->getLevel(),
                    'position' => $category->getPosition()
                );
                //allow importing by ids.
                if (!isset($this->_categoriesWithRoots[$structure[1]])) {
                    $this->_categoriesWithRoots[$structure[1]] = array();
                }
                $this->_categoriesWithRoots[$structure[1]][$category->getId()] =
                    $this->_categoriesWithRoots[$rootCategoryName][$index];
            }
        }
        return $this;

    }

    /**
     * Initialize stores data
     *
     * @param bool $withDefault
     * @return $this
     */
    protected function _initStores($withDefault = false)
    {
        /** @var $store \Magento\Store\Model\Store */
        foreach ($this->_storeManager->getStores($withDefault) as $store) {
            $this->_storeCodeToId[$store->getCode()] = $store->getId();
        }
        return $this;
    }

    /**
     * Initialize website values.
     *
     * @return $this
     */
    protected function _initWebsites($withDefault = false)
    {
        /** @var $website \Magento\Store\Model\Website */
        foreach ($this->_storeManager->getWebsites($withDefault) as $website) {
            $this->_websiteCodeToId[$website->getCode()] = $website->getId();
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _initOnTabAttributes()
    {

        // TODO: If the OnTap Merchandiser Exists, add Code here:
        return $this;
    }

    /**
     * @param boolean $value
     * @return $this
     */
    public function setUnsetEmptyFields($value)
    {
        $this->_unsetEmptyFields = (boolean)$value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setSymbolEmptyFields($value)
    {
        $this->_symbolEmptyFields = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setSymbolIgnoreFields($value)
    {
        $this->_symbolIgnoreFields = $value;
        return $this;
    }

    /**
     * Set the error limit when the importer will stop
     * @param $limit
     */
    public function setErrorLimit($limit)
    {
        if ($limit) {
            $this->_errorsLimit = $limit;
        } else {
            $this->_errorsLimit = 100;
        }
    }

    public function getCategoriesWithRoots()
    {
        return $this->_categoriesWithRoots;
    }

    /**
     * DB connection getter.
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * EAV entity type code getter.
     *
     * @abstract
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'catalog_category';
    }

    /**
     * Get next bunch of validatetd rows.
     *
     * @return array|null
     */
    public function getNextBunch()
    {
        return $this->_dataSourceModel->getNextBunch();
    }

    /**
     * All website codes to ID getter.
     *
     * @return array
     */
    public function getWebsiteCodes()
    {
        return $this->_websiteCodeToId;
    }

    /**
     * Get array of affected Categories
     *
     * @return array
     */
    public function getAffectedEntityIds()
    {
        $categoryIds = array();
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                $this->_filterRowData($rowData);
                if (!$this->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }
                if (!isset($this->_newCategory[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]]['entity_id'])) {
                    continue;
                }
                $categoryIds[] = $this->_newCategory[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]]['entity_id'];
            }
        }
        return $categoryIds;
    }

    /**
     * Removes empty keys in case value is null or empty string
     * Behavior can be turned off with config setting "fastsimpleimport/general/clear_field_on_empty_string"
     * You can define a string which can be used for clearing a field, configured in "fastsimpleimport/product/symbol_for_clear_field"
     *
     * @param array $rowData
     */
    protected function _filterRowData(&$rowData)
    {
        if ($this->_unsetEmptyFields || $this->_symbolEmptyFields || $this->_symbolIgnoreFields) {
            foreach ($rowData as $key => $fieldValue) {
                if ($this->_unsetEmptyFields && !strlen($fieldValue)) {
                    unset($rowData[$key]);
                } else if ($this->_symbolEmptyFields && trim($fieldValue) == $this->_symbolEmptyFields) {
                    $rowData[$key] = NULL;
                } else if ($this->_symbolIgnoreFields && trim($fieldValue) == $this->_symbolIgnoreFields) {
                    unset($rowData[$key]);
                }
            }
        }
    }

    /**
     * Source model setter.
     *
     * @param array $source
     * @return \Magento\ImportExport\Model\Import\Entity\AbstractEntity
     */
    public function setArraySource($source)
    {
        $this->_source = $source;
        $this->_dataValidated = false;

        return $this;
    }

    /**
     * Import behavior setter
     *
     * @param string $behavior
     */
    public function setBehavior($behavior)
    {
        $this->_parameters['behavior'] = $behavior;
    }

    /**
     * Partially reindex newly created and updated products
     *
     * @return $this
     */
    public function reindexImportedCategories()
    {
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->_indexDeleteEvents();
                break;
            case Import::BEHAVIOR_REPLACE:
            case Import::BEHAVIOR_APPEND:

                $this->_reindexUpdatedCategories();
                break;
        }
        return $this;
    }

    /**
     * Reindex all categories
     * @throws \Exception
     * @return $this
     */
    protected function _indexDeleteEvents()
    {
        return $this->_reindexUpdatedCategories();
    }

    /**
     * Reindex all categories
     * @return $this
     * @throws \Exception
     */
    protected function _reindexUpdatedCategories()
    {
        // Reindexing hopefully not needed in Magento2
        return $this;
    }

    public function updateChildrenCount()
    {
        // Hopefully not needed anymore in M2
    }

    /**
     * @param $sku
     * @return array|false
     */
    public function getEntityByCategory($root, $category)
    {
        if (isset($this->_categoriesWithRoots[$root][$category])) {
            return $this->_categoriesWithRoots[$root][$category];
        }

        if (isset($this->_newCategory[$root][$category])) {
            return $this->_newCategory[$root][$category];
        }

        return false;
    }

    /**
     * Create Category entity from raw data.
     *
     * @throws \Exception
     * @return bool Result of operation.
     */
    protected function _importData()
    {
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->_deleteCategories();
        } else {
            $this->_saveCategories();
            $this->_saveOnTab();
        }
        $this->_eventManager->dispatch('catalog_product_import_finish_before', ['adapter' => $this]);
        return true;
    }

    /**
     * Delete products.
     *
     * @return $this
     * @throws \Exception
     */
    protected function _deleteCategories()
    {
        $productEntityTable = $this->_resourceFactory->create()->getEntityTable();

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $idToDelete = [];

            foreach ($bunch as $rowNum => $rowData) {
                $this->_filterRowData($rowData);
                if ($this->validateRow($rowData, $rowNum) && self::SCOPE_DEFAULT == $this->getRowScope($rowData)) {
                    $idToDelete[] = $this->_categoriesWithRoots[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]]['entity_id'];
                }
            }
            if ($idToDelete) {
                $this->countItemsDeleted += count($idToDelete);
                $this->transactionManager->start($this->_connection);
                try {
                    $this->objectRelationProcessor->delete(
                        $this->transactionManager,
                        $this->_connection,
                        $productEntityTable,
                        $this->_connection->quoteInto('entity_id IN (?)', $idToDelete),
                        ['entity_id' => $idToDelete]
                    );
                    $this->transactionManager->commit();
                } catch (\Exception $e) {
                    $this->transactionManager->rollBack();
                    throw $e;
                }
                $this->_eventManager->dispatch('catalog_product_import_bunch_delete_after', ['adapter' => $this, 'bunch' => $bunch]);
            }
        }
        return $this;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     */
    public function validateRow(array $rowData, $rowNum)
    {


        static $root = null;
        static $category = null;

        if (isset($rowData['fsi_line_number'])) {
            $rowNum = $rowData['fsi_line_number'];
        }
        $this->_filterRowData($rowData);

        // check if row is already validated
        if (isset($this->_validatedRows[$rowNum])) {
            return !isset($this->_invalidRows[$rowNum]);
        }
        $this->_validatedRows[$rowNum] = true;


        //check for duplicates
        if (isset($rowData[self::COL_ROOT])
            && isset($rowData[self::COL_CATEGORY])
            && isset($this->_newCategory[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]])
        ) {
            if (!$this->getIgnoreDuplicates()) {
                $this->addRowError(self::ERROR_DUPLICATE_CATEGORY, $rowNum);
            }

            return false;
        }
        $rowScope = $this->getRowScope($rowData);

        // BEHAVIOR_DELETE use specific validation logic
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            if (self::SCOPE_DEFAULT == $rowScope
                && !isset($this->_categoriesWithRoots[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]])
            ) {
                $this->addRowError(self::ERROR_CATEGORY_NOT_FOUND_FOR_DELETE, $rowNum);
                return false;
            }
            return true;
        }

        // common validation
        if (self::SCOPE_DEFAULT == $rowScope) { // category is specified, row is SCOPE_DEFAULT, new category block begins
            $rowData['name'] = $this->_getCategoryName($rowData);

            $this->_processedEntitiesCount++;

            $root = $rowData[self::COL_ROOT];
            $category = $rowData[self::COL_CATEGORY];

            //check if the root exists
            if (!isset($this->_categoriesWithRoots[$root])) {
                $this->addRowError(self::ERROR_INVALID_ROOT, $rowNum);
                return false;
            }

            //check if parent category exists
            if ($this->_getParentCategory($rowData) === false) {

                $this->addRowError(self::ERROR_PARENT_NOT_FOUND, $rowNum);
                return false;
            }

            if (isset($this->_categoriesWithRoots[$root][$category])) {

            } else { // validate new category type and attribute set
                if (!isset($this->_newCategory[$root][$category])) {
                    $this->_newCategory[$root][$category] = array(
                        'entity_id' => null,
                    );
                }
                if (isset($this->_invalidRows[$rowNum])) {
                    // mark SCOPE_DEFAULT row as invalid for future child rows if category not in DB already
                    $category = false;
                }
            }

            // check simple attributes
            foreach ($this->_attributes as $attrCode => $attrParams) {
                if (isset($rowData[$attrCode]) && strlen($rowData[$attrCode])) {
                    $this->isAttributeValid($attrCode, $attrParams, $rowData, $rowNum);
                } elseif ($attrParams['is_required'] && !isset($this->_categoriesWithRoots[$root][$category])) {
                    $this->addRowError(self::ERROR_VALUE_IS_REQUIRED, $rowNum, $attrCode);
                }
            }

        } else {
            if (null === $category) {
                $this->addRowError(self::ERROR_CATEGORY_IS_EMPTY, $rowNum);
            } elseif (false === $category) {
                $this->addRowError(self::ERROR_ROW_IS_ORPHAN, $rowNum);
            } elseif (self::SCOPE_STORE == $rowScope && !isset($this->_storeCodeToId[$rowData[self::COL_STORE]])) {
                $this->addRowError(self::ERROR_INVALID_STORE, $rowNum);
            }
        }

        if (isset($this->_invalidRows[$rowNum])) {
            $category = false; // mark row as invalid for next address rows
        }


        return !isset($this->_invalidRows[$rowNum]);
    }

    public function getIgnoreDuplicates()
    {
        return $this->_ignoreDuplicates;
    }

    public function setIgnoreDuplicates($ignore)
    {
        $this->_ignoreDuplicates = (boolean)$ignore;
    }

    /**
     * Obtain scope of the row from row data.
     *
     * @param array $rowData
     * @return int
     */
    public function getRowScope(array $rowData)
    {
        if (isset($rowData[self::COL_CATEGORY]) && strlen(trim($rowData[self::COL_CATEGORY]))) {
            return self::SCOPE_DEFAULT;
        } elseif (empty($rowData[self::COL_STORE])) {
            return self::SCOPE_NULL;
        } else {
            return self::SCOPE_STORE;
        }
    }

    protected function _getCategoryName($rowData)
    {
        if (isset($rowData['name']) && strlen($rowData['name']))
            return $rowData['name'];
        $categoryParts = $this->_explodeEscaped('/', $rowData[self::COL_CATEGORY]);
        return end($categoryParts);
    }

    protected function _explodeEscaped($delimiter = '/', $string)
    {
        $exploded = explode($delimiter, $string);
        $fixed = array();
        for ($k = 0, $l = count($exploded); $k < $l; ++$k) {
            $eIdx = strlen($exploded[$k]) - 1;
            if ($eIdx >= 0 && $exploded[$k][$eIdx] == '\\') {
                if ($k + 1 >= $l) {
                    $fixed[] = trim($exploded[$k]);
                    break;
                }
                $exploded[$k][$eIdx] = $delimiter;
                $exploded[$k] .= $exploded[$k + 1];
                array_splice($exploded, $k + 1, 1);
                --$l;
                --$k;
            } else $fixed[] = trim($exploded[$k]);
        }
        return $fixed;
    }

    /**
     * Get the categorie's parent ID
     *
     * @param array $rowData
     * @return bool|mixed
     */
    protected function _getParentCategory($rowData)
    {
        $categoryParts = $this->_explodeEscaped('/', $rowData[self::COL_CATEGORY]);
        array_pop($categoryParts);
        $parent = $this->_implodeEscaped('/', $categoryParts);

        if ($parent) {
            if (isset($this->_categoriesWithRoots[$rowData[self::COL_ROOT]][$parent])) {
                return $this->_categoriesWithRoots[$rowData[self::COL_ROOT]][$parent];
            } elseif (isset($this->_newCategory[$rowData[self::COL_ROOT]][$parent])) {
                return $this->_newCategory[$rowData[self::COL_ROOT]][$parent];
            } else {
                return false;
            }
        } elseif (isset($this->_categoriesWithRoots[$rowData[self::COL_ROOT]])) {
            return reset($this->_categoriesWithRoots[$rowData[self::COL_ROOT]]);
        } else {
            return false;
        }
    }

    protected function _implodeEscaped($glue, $array)
    {
        $newArray = array();
        foreach ($array as $value) {
            $newArray[] = str_replace($glue, '\\' . $glue, $value);
        }
        return implode('/', $newArray);
    }



    /**
     * Gather and save information about category entities.
     *
     * @return AvS_FastSimpleImport_Model_Import_Entity_Category
     */
    protected function _saveCategories()
    {

        //$strftimeFormat = \Varien_Date::convertZendToStrftime(Varien_Date::DATETIME_INTERNAL_FORMAT, true, true);
        // TODO: Get this Constant
        $strftimeFormat = "12.00.2016";
        $nextEntityId = $this->resourceHelper->getNextAutoincrement($this->_entityTable);
        static $entityId;

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityRowsIn = array();
            $entityRowsUp = array();
            $attributes = array();
            $uploadedGalleryFiles = array();

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                $rowScope = $this->getRowScope($rowData);

                $rowData = $this->_prepareRowForDb($rowData);
                $this->_filterRowData($rowData);

                if (self::SCOPE_DEFAULT == $rowScope) {
                    $rowCategory = $rowData[self::COL_CATEGORY];

                    $parentCategory = $this->_getParentCategory($rowData);

                    // entity table data
                    $entityRow = array(
                        'parent_id' => $parentCategory['entity_id'],
                        'level' => $parentCategory['level'] + 1,
                        'created_at' => empty($rowData['created_at']) ? "now()"
                            : gmstrftime($strftimeFormat, strtotime($rowData['created_at'])),
                        'updated_at' => "now()",
                        'position' => $rowData['position']
                    );

                    if (isset($this->_categoriesWithRoots[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]])) { //edit

                        $entityId = $this->_categoriesWithRoots[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]]['entity_id'];
                        $entityRow['entity_id'] = $entityId;
                        $entityRow['path'] = $parentCategory['path'] . '/' . $entityId;
                        $entityRowsUp[] = $entityRow;
                        $rowData['entity_id'] = $entityId;
                    } else { // create
                        $entityId = $nextEntityId++;
                        $entityRow['entity_id'] = $entityId;
                        $entityRow['path'] = $parentCategory['path'] . '/' . $entityId;
                        $entityRow['entity_type_id'] = $this->_entityTypeId;
                        $entityRow['attribute_set_id'] = $this->_defaultAttributeSetId;
                        $entityRowsIn[] = $entityRow;

                        $this->_newCategory[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]] = array(
                            'entity_id' => $entityId,
                            'path' => $entityRow['path'],
                            'level' => $entityRow['level']
                        );

                    }
                }

                foreach ($this->_imagesArrayKeys as $imageCol) {
                    if (!empty($rowData[$imageCol])) { // 5. Media gallery phase
                        if (!array_key_exists($rowData[$imageCol], $uploadedGalleryFiles)) {
                            $uploadedGalleryFiles[$rowData[$imageCol]] = $this->_uploadMediaFiles($rowData[$imageCol]);
                        }
                        $rowData[$imageCol] = $uploadedGalleryFiles[$rowData[$imageCol]];
                    }
                }

                // Attributes phase
                $rowStore = self::SCOPE_STORE == $rowScope ? $this->_storeCodeToId[$rowData[self::COL_STORE]] : 0;

                /** @var $category \Magento\Catalog\Model\Category */
                $category = $this->_defaultCategory->setData($rowData);

                foreach (array_intersect_key($rowData, $this->_attributes) as $attrCode => $attrValue) {
                    if (!$this->_attributes[$attrCode]['is_static']) {

                        /** @var $attribute Mage_Eav_Model_Entity_Attribute */
                        $attribute = $this->_attributes[$attrCode]['attribute'];

                        if ('multiselect' != $attribute->getFrontendInput()
                            && self::SCOPE_NULL == $rowScope
                        ) {
                            continue; // skip attribute processing for SCOPE_NULL rows
                        }

                        $attrId = $attribute->getAttributeId();
                        $backModel = $attribute->getBackendModel();
                        $attrTable = $attribute->getBackend()->getTable();
                        $attrParams = $this->_attributes[$attrCode];
                        $storeIds = array(0);

                        if ('select' == $attrParams['type']) {
                            if (isset($attrParams['options'][strtolower($attrValue)])) {
                                $attrValue = $attrParams['options'][strtolower($attrValue)];
                            }
                        } elseif ('datetime' == $attribute->getBackendType() && strtotime($attrValue)) {
                            $attrValue = gmstrftime($strftimeFormat, strtotime($attrValue));
                        } elseif ($backModel && 'available_sort_by' != $attrCode) {
                            $attribute->getBackend()->beforeSave($category);
                            $attrValue = $category->getData($attribute->getAttributeCode());
                        }

                        if (self::SCOPE_STORE == $rowScope) {
                            if (self::SCOPE_WEBSITE == $attribute->getIsGlobal()) {
                                // check website defaults already set
                                if (!isset($attributes[$attrTable][$entityId][$attrId][$rowStore])) {
                                    $storeIds = $this->_storeIdToWebsiteStoreIds[$rowStore];
                                }
                            } elseif (self::SCOPE_STORE == $attribute->getIsGlobal()) {
                                $storeIds = array($rowStore);
                            }
                        }

                        foreach ($storeIds as $storeId) {
                            if ('multiselect' == $attribute->getFrontendInput()) {
                                if (!isset($attributes[$attrTable][$entityId][$attrId][$storeId])) {
                                    $attributes[$attrTable][$entityId][$attrId][$storeId] = '';
                                } else {
                                    $attributes[$attrTable][$entityId][$attrId][$storeId] .= ',';
                                }
                                $attributes[$attrTable][$entityId][$attrId][$storeId] .= $attrValue;
                            } else {
                                $attributes[$attrTable][$entityId][$attrId][$storeId] = $attrValue;
                            }
                        }

                        $attribute->setBackendModel($backModel); // restore 'backend_model' to avoid 'default' setting
                    }
                }
            }

            $this->_saveCategoryEntity($entityRowsIn, $entityRowsUp);
            $this->_saveCategoryAttributes($attributes);
        }
        return $this;
    }

    /**
     * Set valid attribute set and category type to rows with all scopes
     * to ensure that existing Categories doesn't changed.
     *
     * @param array $rowData
     * @return array
     */
    protected function _prepareRowForDb(array $rowData)
    {
        $rowData = parent::_prepareRowForDb($rowData);
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            return $rowData;
        }

        if (self::SCOPE_DEFAULT == $this->getRowScope($rowData)) {
            $rowData['name'] = $this->_getCategoryName($rowData);
            if (!isset($rowData['position'])) $rowData['position'] = 10000; // diglin - prevent warning message
        }

        return $rowData;
    }

    /**
     * Uploading files into the "catalog/category" media folder.
     * Return a new file name if the same file is already exists.
     * @todo Solve the problem with images that get imported multiple times.
     *
     * @param string $fileName
     * @return string
     */
    protected function _uploadMediaFiles($fileName)
    {
        try {
            $res = $this->_getUploader()->move($fileName);
            return $res['file'];
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Returns an object for upload a media files
     */
    protected function _getUploader()
    {
        if (is_null($this->_fileUploader)) {
            $this->_fileUploader = new \Magento\CatalogImportExport\Model\Import\Uploader();

            $this->_fileUploader->init();
            $this->_fileUploader->removeValidateCallback('catalog_product_image');
            $this->_fileUploader->setFilesDispersion(false);

            $tmpDir = Mage::getConfig()->getOptions()->getMediaDir() . '/import';
            $destDir = Mage::getConfig()->getOptions()->getMediaDir() . '/catalog/category';
            if (!is_writable($destDir)) {
                @mkdir($destDir, 0777, true);
            }
            // diglin - add auto creation in case folder doesn't exist
            if (!file_exists($tmpDir)) {
                @mkdir($tmpDir, 0777, true);
            }
            if (!$this->_fileUploader->setTmpDir($tmpDir)) {
                Mage::throwException("File directory '{$tmpDir}' is not readable.");
            }
            if (!$this->_fileUploader->setDestDir($destDir)) {
                Mage::throwException("File directory '{$destDir}' is not writable.");
            }
        }
        return $this->_fileUploader;
    }

    /**
     * Update and insert data in entity table.
     *
     * @param array $entityRowsIn Row for insert
     * @param array $entityRowsUp Row for update
     * @return Mage_ImportExport_Model_Import_Entity_Customer
     */
    protected function _saveCategoryEntity(array $entityRowsIn, array $entityRowsUp)
    {
        if ($entityRowsIn) {
            $this->_connection->insertMultiple($this->_entityTable, $entityRowsIn);
        }
        if ($entityRowsUp) {
            $this->_connection->insertOnDuplicate(
                $this->_entityTable,
                $entityRowsUp,
                array('parent_id', 'path', 'position', 'level', 'children_count')
            );
        }
        return $this;
    }

    /**
     * Save category attributes.
     *
     * @param array $attributesData
     * @return AvS_FastSimpleImport_Model_Import_Entity_Category
     */
    protected function _saveCategoryAttributes(array $attributesData)
    {
        foreach ($attributesData as $tableName => $data) {
            $tableData = array();

            foreach ($data as $entityId => $attributes) {

                foreach ($attributes as $attributeId => $storeValues) {
                    foreach ($storeValues as $storeId => $storeValue) {
                        $tableData[] = array(
                            'entity_id' => $entityId,
                            'entity_type_id' => $this->_entityTypeId,
                            'attribute_id' => $attributeId,
                            'store_id' => $storeId,
                            'value' => $storeValue
                        );
                    }
                }
            }
            $this->_connection->insertOnDuplicate($tableName, $tableData, array('value'));
        }
        return $this;
    }

    /**
     * Stock item saving.
     * Overwritten in order to fix bug with stock data import
     * See http://www.magentocommerce.com/bug-tracking/issue/?issue=13539
     * See https://github.com/avstudnitz/AvS_FastSimpleImport/issues/3
     *
     * @return Mage_ImportExport_Model_Import_Entity_Product
     */
    protected function _saveOnTab()
    {
        // TODO: If the OnTap Merchandiser Exists, add Code here:
    }

    /**
     * Returns boolean TRUE if row scope is default (fundamental) scope.
     *
     * @param array $rowData
     * @return bool
     */
    protected function _isRowScopeDefault(array $rowData)
    {
        return strlen(trim($rowData[self::COL_CATEGORY])) ? true : false;
    }

    /**
     * Ids of products which have been created, updated or deleted
     *
     * @return array
     */
    protected function _getProcessedCategoryIds()
    {
        $categoryIds = array();
        $source = $this->getSource();

        $source->rewind();
        while ($source->valid()) {
            $current = $source->current();
            if (isset($this->_newCategory[$current[self::COL_ROOT]][$current[self::COL_CATEGORY]])) {
                $categoryIds[] = $this->_newCategory[$current[self::COL_ROOT]][$current[self::COL_CATEGORY]];
            } elseif (isset($this->_categoriesWithRoots[$current[self::COL_ROOT]][$current[self::COL_CATEGORY]])) {
                $categoryIds[] = $this->_categoriesWithRoots[$current[self::COL_ROOT]][$current[self::COL_CATEGORY]];
            }

            $source->next();
        }

        return $categoryIds;
    }

    /**
     * Validate data rows and save bunches to DB
     *
     * @return $this
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->getSource();
        $source->rewind();
        while ($source->valid()) {
            try {
                $rowData = $source->current();
            } catch (\InvalidArgumentException $e) {
                $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                $this->_processedRowsCount++;
                $source->next();
                continue;
            }

            $rowData = $this->_customFieldsMapping($rowData);

            $this->validateRow($rowData, $source->key());
            $source->next();
        }
        //$this->checkUrlKeyDuplicates();
        //$this->getOptionEntity()->validateAmbiguousData();
        //var_dump($source);
        return parent::_saveValidatedBunches();
    }
    protected function _customFieldsMapping($rowData)
    {
        return $rowData;
    }
    /**
     * Returns attributes all values in label-value or value-value pairs form. Labels are lower-cased.
     *
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute
     * @return array
     */
    public function getAttributeOptions(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute)
    {
        $options = [];

        if ($attribute->usesSource()) {
            // should attribute has index (option value) instead of a label?
            $index = in_array($attribute->getAttributeCode(), $this->_indexValueAttributes) ? 'value' : 'label';

            // only default (admin) store values used
            $attribute->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);

            try {
                foreach ($attribute->getSource()->getAllOptions(false) as $option) {
                    foreach (is_array($option['value']) ? $option['value'] : [$option] as $innerOption) {
                        if (strlen($innerOption['value'])) {
                            // skip ' -- Please Select -- ' option
                            $options[$innerOption['value']] = (string)$innerOption[$index];
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore exceptions connected with source models
            }
        }
        return $options;
    }
}
