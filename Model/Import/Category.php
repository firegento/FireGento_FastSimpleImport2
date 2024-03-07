<?php
/**
 * Copyright © 2016 - 2022 FireGento e.V. - All rights reserved.
 * See LICENSE.md bundled with this module for license details.
 */

namespace FireGento\FastSimpleImport\Model\Import;

use FireGento\FastSimpleImport\Model\Config;
use FireGento\FastSimpleImport\Model\Enterprise\CategoryImportVersion;
use FireGento\FastSimpleImport\Model\Enterprise\VersionFeaturesFactory;
use FireGento\FastSimpleImport\Model\Import\Proxy\Category\ResourceModelFactory as CategoryResourceModelFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection as CategoryAttributeCollection;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\CatalogImportExport\Model\Import\Uploader;
use Magento\CatalogImportExport\Model\Import\UploaderFactory;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface as DirectoryWriteInterface;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ImportFactory;
use Magento\ImportExport\Model\ResourceModel\Helper as ImportExportHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;

/**
 * Entity Adapter for importing Magento Categories
 */
class Category extends \Magento\ImportExport\Model\Import\AbstractEntity
{
    /**
     * Size of bunch - part of entities to save in one step.
     */
    public const BUNCH_SIZE = 20;

    /**
     * Data row scopes.
     */
    public const SCOPE_DEFAULT = 1;
    public const SCOPE_WEBSITE = 2;
    public const SCOPE_STORE = 0;
    public const SCOPE_NULL = -1;

    /**
     * Permanent column names.
     *
     * Names that begins with underscore is not an attribute. This name convention is for
     * to avoid interference with same attribute name.
     */
    public const COL_STORE = '_store';
    public const COL_ROOT = '_root';
    public const COL_CATEGORY = '_category';

    /**
     * Error codes.
     */
    public const ERROR_INVALID_SCOPE = 'invalidScope';
    public const ERROR_INVALID_WEBSITE = 'invalidWebsite';
    public const ERROR_INVALID_STORE = 'invalidStore';
    public const ERROR_INVALID_ROOT = 'invalidRoot';
    public const ERROR_CATEGORY_IS_EMPTY = 'categoryIsEmpty';
    public const ERROR_PARENT_NOT_FOUND = 'parentNotFound';
    public const ERROR_NO_DEFAULT_ROW = 'noDefaultRow';
    public const ERROR_DUPLICATE_CATEGORY = 'duplicateCategory';
    public const ERROR_DUPLICATE_SCOPE = 'duplicateScope';
    public const ERROR_ROW_IS_ORPHAN = 'rowIsOrphan';
    public const ERROR_VALUE_IS_REQUIRED = 'valueIsRequired';
    public const ERROR_CATEGORY_NOT_FOUND_FOR_DELETE = 'categoryNotFoundToDelete';

    /**
     * Code of a primary attribute which identifies the entity group if import contains of multiple rows
     *
     * @var string
     */
    protected $masterAttributeCode = self::COL_CATEGORY;

    /**
     * Category attributes parameters.
     *
     *  [attr_code_1] => [
     *      'options' => [],
     *      'type' => 'text', 'price', 'textarea', 'select', etc.
     *      'id' => ..
     *  ],
     *  ...
     *
     * @var array
     */
    private array $attributes = [];

    /**
     * Categories text-path to ID hash with roots checking.
     */
    private array $categoriesWithRoots = [];

    /**
     * Category entity DB table name.
     */
    private ?string $entityTable = null;

    /**
     * Attributes with index (not label) value.
     */
    private array $indexValueAttributes = [
        'default_sort_by',
        CategoryInterface::KEY_AVAILABLE_SORT_BY,
        CategoryInterface::KEY_IS_ACTIVE,
        CategoryInterface::KEY_INCLUDE_IN_MENU,
        'is_anchor'
    ];

    /**
     * Validation failure message template definitions
     */
    private array $messageTemplates = [
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
    ];

    /**
     * Column names that holds images files names
     */
    private array  $imagesArrayKeys = [
        'thumbnail', 'image'
    ];

    private array $newCategory = [];

    /**
     * Column names that holds values with particular meaning.
     */
    protected $_specialAttributes = [
        self::COL_STORE,
        self::COL_ROOT,
        self::COL_CATEGORY
    ];

    /**
     * Permanent entity columns.
     */
    protected $_permanentAttributes = [
        self::COL_ROOT,
        self::COL_CATEGORY
    ];

    /**
     * List of fields that can used config values in case when value does not defined directly
     *
     * @var array
     */
    protected $useConfigFields = [
        'available_sort_by',
        'default_sort_by',
        'filter_price_range'
    ];

    private ?int $errorsLimit = null;
    private array $invalidRows = [];

    /**
     * All stores code-ID pairs.
     */
    private array $storeCodeToId = [];

    /**
     * Store ID to its website stores IDs.
     */
    private array $storeIdToWebsiteStoreIds = [];

    /**
     * Website code-to-ID
     */
    private array $websiteCodeToId = [];

    private bool $unsetEmptyFields = false;

    /** @var bool|string */
    private $symbolEmptyFields = false;

    /** @var bool|string */
    private $symbolIgnoreFields = false;

    private int $defaultAttributeSetId = 0;
    private ?CategoryImportVersion $categoryImportVersionFeature;
    private ?Uploader $fileUploader = null;
    private DirectoryWriteInterface $mediaDirectory;
    private StoreManagerInterface $storeManager;
    private CategoryModel $defaultCategory;
    private CategoryAttributeCollection $attributeCollection;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private ImportExportHelper $resourceHelper;
    private ManagerInterface $eventManager;
    private UploaderFactory $uploaderFactory;
    private ObjectRelationProcessor $objectRelationProcessor;
    private TransactionManagerInterface $transactionManager;
    private CategoryResourceModelFactory $resourceFactory;
    private VersionFeaturesFactory $versionFeatures;
    private CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator;
    private UrlPersistInterface $urlPersist;
    private CategoryRepositoryInterface $categoryRepository;
    private Config $config;

    public function __construct(
        StringUtils $string,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        ImportExportHelper $resourceHelper,
        ResourceConnection $resource,
        ProcessingErrorAggregatorInterface $errorAggregator,
        StoreManagerInterface $storeManager,
        CategoryModel $defaultCategory,
        CategoryAttributeCollection $attributeCollection,
        CategoryCollectionFactory $categoryCollectionFactory,
        EavConfig $eavConfig,
        ManagerInterface $eventManager,
        UploaderFactory $imageUploaderFactory,
        Filesystem $filesystem,
        VersionFeaturesFactory $versionFeatures,
        ObjectRelationProcessor $objectRelationProcessor,
        TransactionManagerInterface $transactionManager,
        CategoryResourceModelFactory $resourceFactory,
        CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator,
        CategoryRepositoryInterface $categoryRepository,
        UrlPersistInterface $urlPersist,
        Config $config,
        array $data = []
    ) {
        parent::__construct(
            $string,
            $scopeConfig,
            $importFactory,
            $resourceHelper,
            $resource,
            $errorAggregator,
            $data
        );

        $this->resourceHelper = $resourceHelper;
        $this->storeManager = $storeManager;
        $this->defaultCategory = $defaultCategory;
        $this->attributeCollection = $attributeCollection;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->eventManager = $eventManager;
        $this->uploaderFactory = $imageUploaderFactory;
        $this->versionFeatures = $versionFeatures;
        $this->objectRelationProcessor = $objectRelationProcessor;
        $this->transactionManager = $transactionManager;
        $this->resourceFactory = $resourceFactory;
        $this->categoryUrlRewriteGenerator = $categoryUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;
        $this->categoryRepository = $categoryRepository;

        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);

        foreach ($this->messageTemplates as $errorCode => $message) {
            $this->getErrorAggregator()->addErrorMessageTemplate($errorCode, $message);
        }

        $this->config                       = $config;

        $this->initOnTapAttributes()
            ->initWebsites()
            ->initStores()
            ->initCategories()
            ->initAttributes()
            ->initAttributeSetId();

        $this->entityTable                  = $this->defaultCategory->getResource()->getEntityTable();
        $this->categoryImportVersionFeature = $this->versionFeatures->create('CategoryImportVersion');
    }

    /**
     * EAV entity type code getter.
     *
     * @abstract
     * @return string
     */
    public function getEntityTypeCode()
    {
        return CategoryModel::ENTITY;
    }

    /**
     * Initialize the default attribute_set_id
     */
    private function initAttributeSetId(): self
    {
        $this->defaultAttributeSetId = (int) $this->defaultCategory->getDefaultAttributeSetId();
        return $this;
    }

    /**
     * Initialize customer attributes.
     */
    private function initAttributes(): self
    {
        foreach ($this->attributeCollection as $attribute) {
            $this->attributes[$attribute->getAttributeCode()] = [
                'id' => $attribute->getId(),
                'is_required' => $attribute->getIsRequired(),
                'is_static' => $attribute->isStatic(),
                'rules' => $attribute->getValidateRules() ? unserialize($attribute->getValidateRules()) : null,
                'type' => Import::getAttributeType($attribute),
                'options' => $this->getAttributeOptions($attribute),
                'attribute' => $attribute
            ];
        }

        return $this;
    }

    /**
     * Returns attributes all values in label-value or value-value pairs form. Labels are lower-cased.
     */
    public function getAttributeOptions(AbstractAttribute $attribute): array
    {
        $options = [];

        if ($attribute->usesSource()) {
            // should attribute has index (option value) instead of a label?
            $index = 'label';
            if (in_array($attribute->getAttributeCode(), $this->indexValueAttributes, true)
                || $attribute->getSourceModel() === Boolean::class) {
                $index = 'value';
            }

            // only default (admin) store values used
            /** @var Attribute $attribute */
            $attribute->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);

            try {
                foreach ($attribute->getSource()->getAllOptions(false) as $option) {
                    foreach (is_array($option['value']) ? $option['value'] : [$option] as $innerOption) {
                        if (strlen($innerOption['value'])) {
                            // skip ' -- Please Select -- ' option
                            $options[strtolower($innerOption['value'])] = (string)$innerOption[$index];
                        }
                    }
                }
            } catch (\Exception $e) {
                // ignore exceptions connected with source models
            }
        }

        return $options;
    }

    /**
     * Initialize categories text-path to ID hash.
     *
     * @return $this
     */
    private function initCategories(): self
    {
        $collection = $this->getCollection();

        /** @var CategoryModel $category */
        foreach ($collection as $category) {
            $structure = explode('/', $category->getData(CategoryModel::KEY_PATH));
            $pathSize = count($structure);

            if ($pathSize > 1) {
                $path = [];
                for ($i = 1; $i < $pathSize; $i++) {
                    /** @var CategoryModel $c */
                    $c = $collection->getItemById($structure[$i]);
                    $path[] = $c->getData(CategoryModel::KEY_NAME);
                }

                $rootCategoryName = array_shift($path);
                if (!isset($this->categoriesWithRoots[$rootCategoryName])) {
                    $this->categoriesWithRoots[$rootCategoryName] = [];
                }

                $index = $this->implodeEscaped($this->config->getCategoryPathSeparator(), $path);
                $this->categoriesWithRoots[$rootCategoryName][$index] = [
                    'entity_id' => $category->getId(),
                    CategoryInterface::KEY_PATH => $category->getData(CategoryInterface::KEY_PATH),
                    CategoryInterface::KEY_LEVEL => $category->getData(CategoryInterface::KEY_LEVEL),
                    CategoryInterface::KEY_POSITION => $category->getData(CategoryInterface::KEY_POSITION)
                ];

                //allow importing by ids.
                if (!isset($this->categoriesWithRoots[$structure[1]])) {
                    $this->categoriesWithRoots[$structure[1]] = [];
                }

                $this->categoriesWithRoots[$structure[1]][$category->getId()] =
                    $this->categoriesWithRoots[$rootCategoryName][$index];
            }
        }

        return $this;
    }

    private function getCollection(): CategoryCollection
    {
        return $this->categoryCollectionFactory->create()->setStoreId(0)->addNameToResult();
    }

    private function implodeEscaped(string $glue, array $array): string
    {
        $newArray = [];
        foreach ($array as $value) {
            $newArray[] = str_replace($glue, '\\' . $glue, $value);
        }
        return implode($this->config->getCategoryPathSeparator(), $newArray);
    }

    /**
     * Initialize stores data
     */
    private function initStores(bool $withDefault = false): self
    {
        /** @var $store \Magento\Store\Model\Store */
        foreach ($this->storeManager->getStores($withDefault) as $store) {
            $this->storeCodeToId[$store->getCode()] = $store->getId();
        }
        return $this;
    }

    /**
     * Initialize website values.
     */
    private function initWebsites(bool $withDefault = false): self
    {
        /** @var $website \Magento\Store\Model\Website */
        foreach ($this->storeManager->getWebsites($withDefault) as $website) {
            $this->websiteCodeToId[$website->getCode()] = $website->getId();
        }
        return $this;
    }

    private function initOnTapAttributes(): self
    {
        // TODO: If the OnTap Merchandiser Exists, add Code here:
        return $this;
    }

    public function setUnsetEmptyFields(bool $value): self
    {
        $this->unsetEmptyFields = $value;
        return $this;
    }

    public function setSymbolEmptyFields(string $value): self
    {
        $this->symbolEmptyFields = $value;
        return $this;
    }

    public function setSymbolIgnoreFields(string $value): self
    {
        $this->symbolIgnoreFields = $value;
        return $this;
    }

    /**
     * Set the error limit when the importer will stop
     */
    public function setErrorLimit(int $limit): self
    {
        if ($limit) {
            $this->errorsLimit = $limit;
        } else {
            $this->errorsLimit = 100;
        }
        return $this;
    }

    /**
     * Get the error limit when the importer will stop
     */
    public function getErrorLimit(): int
    {
        return (int) $this->errorsLimit;
    }

    public function getCategoriesWithRoots(): array
    {
        return $this->categoriesWithRoots;
    }

    /**
     * DB connection getter.
     */
    public function getConnection(): AdapterInterface
    {
        return $this->_connection;
    }

    /**
     * Get next bunch of validatetd rows.
     */
    public function getNextBunch(): ?array
    {
        return $this->_dataSourceModel->getNextBunch();
    }

    /**
     * All website codes to ID getter.
     */
    public function getWebsiteCodes(): array
    {
        return $this->websiteCodeToId;
    }

    /**
     * Get array of affected Categories
     *
     * @return array
     */
    public function getAffectedEntityIds(): array
    {
        $categoryIds = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                $this->filterRowData($rowData);
                if (!$this->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }
                if (!isset($this->newCategory[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]]['entity_id'])) {
                    continue;
                }
                $categoryIds[] = $this->newCategory[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]]['entity_id'];
            }
        }
        return $categoryIds;
    }

    /**
     * Removes empty keys in case value is null or empty string
     * Behavior can be turned off with config setting "fastsimpleimport/general/clear_field_on_empty_string"
     * You can define a string which can be used for clearing a field,
     * configured in "fastsimpleimport/category/symbol_for_clear_field"
     */
    private function filterRowData(array &$rowData): void
    {
        if ($this->unsetEmptyFields || $this->symbolEmptyFields || $this->symbolIgnoreFields) {
            foreach ($rowData as $key => $fieldValue) {
                if ($this->unsetEmptyFields && !strlen($fieldValue)) {
                    unset($rowData[$key]);
                } elseif ($this->symbolEmptyFields && trim($fieldValue) == $this->symbolEmptyFields) {
                    $rowData[$key] = null;
                } elseif ($this->symbolIgnoreFields && trim($fieldValue) == $this->symbolIgnoreFields) {
                    unset($rowData[$key]);
                }
            }
        }
    }

    public function setArraySource(Import\AbstractSource $source): self
    {
        $this->_source = $source;
        $this->_dataValidated = false;

        return $this;
    }

    public function setBehavior(string $behavior): self
    {
        $this->_parameters['behavior'] = $behavior;
        return $this;
    }

    /**
     * Partially reindex newly created and updated categories
     *
     * @throws \Exception
     */
    public function reindexImportedCategories(): self
    {
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->indexDeleteEvents();
                break;
            case Import::BEHAVIOR_REPLACE:
            case Import::BEHAVIOR_APPEND:
            case Import::BEHAVIOR_ADD_UPDATE:
                $this->reindexUpdatedCategories();
                break;
            default:
                throw new \Exception('Unsupported Mode!');
        }
        return $this;
    }

    /**
     * Reindex all categories
     * @throws \Exception
     * @return $this
     */
    private function indexDeleteEvents(): self
    {
        return $this->reindexUpdatedCategories();
    }

    /**
     * Reindex all categories
     * @return $this
     * @throws \Exception
     */
    protected function reindexUpdatedCategories($categoryId)
    {
        /** @var $category \Magento\Catalog\Model\Category */
        $category = $this->categoryRepository->get($categoryId);

        foreach ($category->getStoreIds() as $storeId) {
            if ($storeId == 0) {
                continue;
            }

            $category = $this->categoryRepository->get($categoryId, $storeId);

            $urlRewrites = $this->categoryUrlRewriteGenerator->generate($category, true);
            $this->urlPersist->replace($urlRewrites);
        }
        return $this;
    }

    public function updateChildrenCount()
    {
        // Hopefully not needed anymore in M2
    }

    /**
     * @return array|false
     */
    public function getEntityByCategory(string $root, string $category)
    {
        if (isset($this->categoriesWithRoots[$root][$category])) {
            return $this->categoriesWithRoots[$root][$category];
        }

        if (isset($this->newCategory[$root][$category])) {
            return $this->newCategory[$root][$category];
        }

        return false;
    }

    /**
     * @param bool|int $value
     */
    public function setIgnoreDuplicates($value): self
    {
        $this->_parameters['ignore_duplicates'] = (boolean)$value;
        return $this;
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
            $this->deleteCategories();
        } else {
            $this->saveCategories();
            $this->saveOnTap();
        }

        $this->eventManager->dispatch('catalog_category_import_finish_before', ['adapter' => $this]);

        return true;
    }

    /**
     * Delete categories.
     *
     * @throws \Exception
     */
    private function deleteCategories(): self
    {
        $categoryEntityTable = $this->resourceFactory->create()->getEntityTable();

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $idToDelete = [];

            foreach ($bunch as $rowNum => $rowData) {
                $this->filterRowData($rowData);
                if ($this->validateRow($rowData, $rowNum) && self::SCOPE_DEFAULT == $this->getRowScope($rowData)) {
                    $idToDelete[] =
                        $this->categoriesWithRoots[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]]['entity_id'];
                }
            }
            if ($idToDelete) {
                $this->countItemsDeleted += count($idToDelete);
                $this->transactionManager->start($this->_connection);
                try {
                    $this->objectRelationProcessor->delete(
                        $this->transactionManager,
                        $this->_connection,
                        $categoryEntityTable,
                        $this->_connection->quoteInto('entity_id IN (?)', $idToDelete),
                        ['entity_id' => $idToDelete]
                    );
                    $this->transactionManager->commit();
                } catch (\Exception $e) {
                    $this->transactionManager->rollBack();
                    throw $e;
                }
                $this->eventManager->dispatch(
                    'catalog_category_import_bunch_delete_after',
                    ['adapter' => $this, 'bunch' => $bunch]
                );
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
        $this->filterRowData($rowData);

        // check if row is already validated
        if (isset($this->_validatedRows[$rowNum])) {
            return !isset($this->invalidRows[$rowNum]);
        }
        $this->_validatedRows[$rowNum] = true;


        //check for duplicates
        if (isset($rowData[self::COL_ROOT])
            && isset($rowData[self::COL_CATEGORY])
            && isset($this->newCategory[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]])
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
                && !isset($this->categoriesWithRoots[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]])
            ) {
                $this->addRowError(self::ERROR_CATEGORY_NOT_FOUND_FOR_DELETE, $rowNum);
                return false;
            }
            return true;
        }

        // common validation
        if (self::SCOPE_DEFAULT == $rowScope) {
            // category is specified, row is SCOPE_DEFAULT, new category block begins
            $rowData[CategoryInterface::KEY_NAME] = $this->getCategoryName($rowData);

            $this->_processedEntitiesCount++;

            $root = $rowData[self::COL_ROOT];
            $category = $rowData[self::COL_CATEGORY];

            //check if the root exists
            if (!isset($this->categoriesWithRoots[$root])) {
                $this->addRowError(self::ERROR_INVALID_ROOT, $rowNum);
                return false;
            }

            //check if parent category exists
            if ($this->getParentCategory($rowData) === false) {
                $this->addRowError(self::ERROR_PARENT_NOT_FOUND, $rowNum);
                return false;
            }

            if (!isset($this->categoriesWithRoots[$root][$category])) {
                // validate new category type and attribute set
                if (!isset($this->newCategory[$root][$category])) {
                    $this->newCategory[$root][$category] = ['entity_id' => null];
                }
                if (isset($this->invalidRows[$rowNum])) {
                    // mark SCOPE_DEFAULT row as invalid for future child rows if category not in DB already
                    $category = false;
                }
            }

            // check simple attributes
            foreach ($this->attributes as $attrCode => $attrParams) {
                if (isset($rowData[$attrCode]) && strlen($rowData[$attrCode])) {
                    $this->isAttributeValid($attrCode, $attrParams, $rowData, $rowNum);
                } elseif ($attrParams['is_required'] && !isset($this->categoriesWithRoots[$root][$category])
                    && !in_array($attrCode, $this->useConfigFields)) {
                    $this->addRowError(self::ERROR_VALUE_IS_REQUIRED, $rowNum, $attrCode);
                }
            }

        } else {
            if (null === $category) {
                $this->addRowError(self::ERROR_CATEGORY_IS_EMPTY, $rowNum);
            } elseif (false === $category) {
                $this->addRowError(self::ERROR_ROW_IS_ORPHAN, $rowNum);
            } elseif (self::SCOPE_STORE == $rowScope && !isset($this->storeCodeToId[$rowData[self::COL_STORE]])) {
                $this->addRowError(self::ERROR_INVALID_STORE, $rowNum);
            }
        }

        if (isset($this->invalidRows[$rowNum])) {
            $category = false; // mark row as invalid for next address rows
        }

        return !isset($this->invalidRows[$rowNum]);
    }

    public function getIgnoreDuplicates(): bool
    {
        return (bool) $this->_parameters['ignore_duplicates'];
    }

    /**
     * Obtain scope of the row from row data.
     */
    public function getRowScope(array $rowData): int
    {
        if (isset($rowData[self::COL_CATEGORY]) && strlen(trim($rowData[self::COL_CATEGORY]))) {
            return self::SCOPE_DEFAULT;
        } elseif (empty($rowData[self::COL_STORE])) {
            return self::SCOPE_NULL;
        } else {
            return self::SCOPE_STORE;
        }
    }

    private function getCategoryName(array $rowData): string
    {
        if (isset($rowData[CategoryModel::KEY_NAME]) && strlen($rowData[CategoryModel::KEY_NAME])) {
            return $rowData[CategoryModel::KEY_NAME];
        }

        $categoryParts = $this->explodeEscaped($this->config->getCategoryPathSeparator(), $rowData[self::COL_CATEGORY]);
        return end($categoryParts);
    }

    private function explodeEscaped(string $delimiter, string $string): array
    {
        $exploded = explode($delimiter, $string);
        $fixed = [];
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
            } else {
                $fixed[] = trim($exploded[$k]);
            }
        }
        return $fixed;
    }

    /**
     * Get parent ID of category
     *
     * @return bool|mixed
     */
    protected function getParentCategory(array $rowData)
    {
        if ($rowData[self::COL_CATEGORY] == $this->getCategoryName($rowData)) {
            // if _category eq. name then we don't have parents
            $parent = false;
        } elseif (is_numeric($rowData[self::COL_CATEGORY])
            && isset($this->categoriesWithRoots[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]])) {
            // existing category given via ID, retrieve correct parent
            $categoryParts = explode(
                '/',
                $this->categoriesWithRoots
                    [$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]][CategoryModel::KEY_PATH]
            );
            $parent = $categoryParts[count($categoryParts) - 2];
        } else {
            $categoryParts = $this->explodeEscaped($this->config->getCategoryPathSeparator(),
                $rowData[self::COL_CATEGORY]);
            array_pop($categoryParts);
            $parent = $this->implodeEscaped($this->config->getCategoryPathSeparator(), $categoryParts);
        }

        if ($parent) {
            if (isset($this->categoriesWithRoots[$rowData[self::COL_ROOT]][$parent])) {
                return $this->categoriesWithRoots[$rowData[self::COL_ROOT]][$parent];
            } elseif (isset($this->newCategory[$rowData[self::COL_ROOT]][$parent])) {
                return $this->newCategory[$rowData[self::COL_ROOT]][$parent];
            } else {
                return false;
            }
        } elseif (isset($this->categoriesWithRoots[$rowData[self::COL_ROOT]])) {
            return reset($this->categoriesWithRoots[$rowData[self::COL_ROOT]]);
        } else {
            return false;
        }
    }

    /**
     * Gather and save information about category entities.
     */
    private function saveCategories(): self
    {
        $nextEntityId = $this->resourceHelper->getNextAutoincrement($this->entityTable);
        static $entityId;

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityRowsIn = [];
            $entityRowsUp = [];
            $attributes = [];
            $uploadedGalleryFiles = [];

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                $rowScope = $this->getRowScope($rowData);

                $rowData = $this->_prepareRowForDb($rowData);
                $this->filterRowData($rowData);

                if (self::SCOPE_DEFAULT == $rowScope) {
                    $parentCategory = $this->getParentCategory($rowData);

                    $time = !empty($rowData[CategoryModel::KEY_CREATED_AT])
                        ? strtotime($rowData[CategoryModel::KEY_CREATED_AT])
                        : 'now';

                    // entity table data
                    $entityRow = [
                        CategoryInterface::KEY_PARENT_ID => $parentCategory['entity_id'],
                        CategoryInterface::KEY_LEVEL => $parentCategory[CategoryInterface::KEY_LEVEL] + 1,
                        CategoryInterface::KEY_CREATED_AT => (new \DateTime($time))
                            ->format(DateTime::DATETIME_PHP_FORMAT),
                        CategoryInterface::KEY_UPDATED_AT => "now()",
                        CategoryInterface::KEY_POSITION => $rowData[CategoryInterface::KEY_POSITION]
                    ];

                    if (isset($this->categoriesWithRoots[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]])) {
                        //edit
                        $entityId = $this->categoriesWithRoots
                        [$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]]['entity_id'];
                        $entityRow['entity_id'] = $entityId;
                        $entityRow[CategoryInterface::KEY_PATH] =
                            $parentCategory[CategoryInterface::KEY_PATH] . '/' . $entityId;
                        $entityRowsUp[] = $entityRow;
                        $rowData['entity_id'] = $entityId;

                    } else {
                        // create
                        $entityId = $nextEntityId++;
                        $entityRow['entity_id'] = $entityId;
                        $entityRow[CategoryInterface::KEY_PATH] =
                            $parentCategory[CategoryInterface::KEY_PATH] . '/' . $entityId;
                        $entityRow['attribute_set_id'] = $this->defaultAttributeSetId;
                        $entityRowsIn[] = $entityRow;

                        $this->newCategory[$rowData[self::COL_ROOT]][$rowData[self::COL_CATEGORY]] = [
                            'entity_id' => $entityId,
                            CategoryInterface::KEY_PATH => $entityRow[CategoryInterface::KEY_PATH],
                            CategoryInterface::KEY_LEVEL => $entityRow[CategoryInterface::KEY_LEVEL]
                        ];
                    }
                }

                foreach ($this->imagesArrayKeys as $imageCol) {
                    if (!empty($rowData[$imageCol])) { // 5. Media gallery phase
                        if (!array_key_exists($rowData[$imageCol], $uploadedGalleryFiles)) {
                            $uploadedGalleryFiles[$rowData[$imageCol]] = $this->uploadMediaFiles($rowData[$imageCol]);
                        }
                        $rowData[$imageCol] = $uploadedGalleryFiles[$rowData[$imageCol]];
                    }
                }

                // Attributes phase
                $rowStore = self::SCOPE_STORE == $rowScope ? $this->storeCodeToId[$rowData[self::COL_STORE]] : 0;

                /** @var CategoryModel $category */
                $category = $this->defaultCategory->setData($rowData);

                foreach (array_intersect_key($rowData, $this->attributes) as $attrCode => $attrValue) {
                    if (!$this->attributes[$attrCode]['is_static']) {

                        /** @var Attribute $attribute */
                        $attribute = $this->attributes[$attrCode]['attribute'];

                        if ('multiselect' != $attribute->getFrontendInput()
                            && self::SCOPE_NULL == $rowScope
                        ) {
                            continue; // skip attribute processing for SCOPE_NULL rows
                        }

                        $attrId = $attribute->getAttributeId();
                        $backModel = $attribute->getBackendModel();
                        $attrTable = $attribute->getBackend()->getTable();
                        $attrParams = $this->attributes[$attrCode];
                        $storeIds = [0];

                        if ('select' == $attrParams['type']) {
                            if (isset($attrParams['options'][strtolower($attrValue)])) {
                                $attrValue = $attrParams['options'][strtolower($attrValue)];
                            }
                        } elseif ('datetime' == $attribute->getBackendType() && is_string($attrValue)
                            && strtotime($attrValue)) {
                            $attrValue = (new \DateTime($attrValue))->format(DateTime::DATETIME_PHP_FORMAT);
                        } elseif ($backModel && 'available_sort_by' != $attrCode) {
                            $attribute->getBackend()->beforeSave($category);
                            $attrValue = $category->getData($attribute->getAttributeCode());
                        }

                        if (self::SCOPE_STORE == $rowScope) {
                            if (self::SCOPE_WEBSITE == $attribute->getData('is_global')) {
                                // check website defaults already set
                                if (!isset($attributes[$attrTable][$entityId][$attrId][$rowStore])) {
                                    $storeIds = $this->storeIdToWebsiteStoreIds[$rowStore];
                                }
                            } elseif (self::SCOPE_STORE == $attribute->getData('is_global')) {
                                $storeIds = [$rowStore];
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

            $this->saveCategoryEntity($entityRowsIn, $entityRowsUp);
            $this->saveCategoryAttributes($attributes);
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
            $rowData[CategoryModel::KEY_NAME] = $this->getCategoryName($rowData);

            if (!isset($rowData[CategoryModel::KEY_POSITION])) {
                // diglin - prevent warning message
                $rowData[CategoryModel::KEY_POSITION] = 10000;
            }
        }

        return $rowData;
    }

    /**
     * Uploading files into the "catalog/category" media folder.
     * Return a new file name if the same file is already exists.
     * @todo Solve the problem with images that get imported multiple times.
     */
    private function uploadMediaFiles(string $fileName): string
    {
        try {
            $res = $this->getUploader()->move($fileName);
            return $res['file'];
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Returns an object for upload a media files
     *
     * @throws LocalizedException
     */
    private function getUploader(): Uploader
    {
        if ($this->fileUploader === null) {
            $this->fileUploader = $this->uploaderFactory->create();

            $this->fileUploader->init();

            $dirConfig = DirectoryList::getDefaultConfig();
            $dirAddon = $dirConfig[DirectoryList::MEDIA][DirectoryList::PATH];
            $DS = DIRECTORY_SEPARATOR;
            $tmpPath = $dirAddon . $DS . $this->mediaDirectory->getRelativePath('import');

            if (!$this->fileUploader->setTmpDir($tmpPath)) {
                throw new LocalizedException(
                    __('File directory \'%1\' is not readable.', $tmpPath)
                );
            }

            $destinationDir = "catalog/category";
            $destinationPath = $dirAddon . $DS . $this->mediaDirectory->getRelativePath($destinationDir);
            $this->mediaDirectory->create($destinationPath);

            if (!$this->fileUploader->setDestDir($destinationPath)) {
                throw new LocalizedException(
                    __('File directory \'%1\' is not writable.', $destinationPath)
                );
            }
        }

        return $this->fileUploader;
    }

    /**
     * Update and insert data in entity table.
     *
     * @param array $entityRowsIn Row for insert
     * @param array $entityRowsUp Row for update
     * @return $this
     */
    private function saveCategoryEntity(array $entityRowsIn, array $entityRowsUp): self
    {
        if ($entityRowsIn) {
            if ($this->categoryImportVersionFeature !== null) {
                $entityRowsIn = $this->categoryImportVersionFeature->processCategory($entityRowsIn);
            }

            $this->_connection->insertMultiple($this->entityTable, $entityRowsIn);
        }

        if ($entityRowsUp) {
            $this->_connection->insertOnDuplicate(
                $this->entityTable,
                $entityRowsUp,
                [
                    CategoryInterface::KEY_PARENT_ID,
                    CategoryInterface::KEY_PATH,
                    CategoryInterface::KEY_POSITION,
                    CategoryInterface::KEY_LEVEL,
                    'children_count'
                ]
            );
        }

        return $this;
    }

    /**
     * Save category attributes.
     */
    private function saveCategoryAttributes(array $attributesData): self
    {
        if ($this->categoryImportVersionFeature !== null) {
            $entityFieldName = $this->categoryImportVersionFeature->getEntityFieldName();
        } else {
            $entityFieldName = 'entity_id';
        }

        $entityIds = array();

        foreach ($attributesData as $tableName => $data) {
            $tableData = [];

            foreach ($data as $entityId => $attributes) {
                $entityIds[] = $entityId;

                foreach ($attributes as $attributeId => $storeValues) {
                    foreach ($storeValues as $storeId => $storeValue) {
                        $tableData[] = [
                            $entityFieldName => $entityId,
                            'attribute_id' => $attributeId,
                            'store_id' => $storeId,
                            'value' => $storeValue
                        ];
                    }
                }
            }

            $this->_connection->insertOnDuplicate($tableName, $tableData, ['value']);
        }

        $entityIds = array_unique($entityIds);
        foreach ($entityIds as $entityId) {
            $this->reindexUpdatedCategories($entityId);
        }
        return $this;
    }

    /**
     * Stock item saving.
     * Overwritten in order to fix bug with stock data import
     * See http://www.magentocommerce.com/bug-tracking/issue/?issue=13539
     * See https://github.com/avstudnitz/AvS_FastSimpleImport/issues/3
     */
    private function saveOnTap(): self
    {
        // TODO: If the OnTap Merchandiser Exists, add Code here:
        return $this;
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

            $rowData = $this->customFieldsMapping($rowData);

            $this->validateRow($rowData, $source->key());
            $source->next();
        }

        return parent::_saveValidatedBunches();
    }

    private function customFieldsMapping(array $rowData): array
    {
        return $rowData;
    }
}
