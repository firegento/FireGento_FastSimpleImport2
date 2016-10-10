<?php
namespace FireGento\FastSimpleImport\Model\Enterprise;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Sequence\SequenceRegistry;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class CategoryImportVersion
{
    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @var SequenceRegistry
     */
    protected $sequenceRegistry;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var ReadEntityVersion
     */
    protected $readEntityVersion;

    /**
     * @var ResourceConnection
     */
    protected $appResource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CategoryImportVersion constructor.
     *
     * @param MetadataPool $metadataPool
     * @param SequenceRegistry $sequenceRegistry
     * @param VersionManager $versionManager
     * @param ReadEntityVersion $readEntityVersion
     * @param ResourceConnection $appResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        MetadataPool $metadataPool,
        SequenceRegistry $sequenceRegistry,
        VersionManager $versionManager,
        ReadEntityVersion $readEntityVersion,
        ResourceConnection $appResource,
        LoggerInterface $logger
    )
    {
        $this->metadataPool = $metadataPool;
        $this->sequenceRegistry = $sequenceRegistry;
        $this->versionManager = $versionManager;
        $this->readEntityVersion = $readEntityVersion;
        $this->appResource = $appResource;
        $this->logger = $logger;
    }

    /**
     * Processes the Entity
     *
     * @param array $entityRowsIn
     * @return array
     *
     * @throws \Exception
     */
    public function processCategory(array $entityRowsIn)
    {
        $metadata = $this->metadataPool->getMetadata(CategoryInterface::class);
        $sequenceInfo = $this->sequenceRegistry->retrieve(CategoryInterface::class);

        if (isset($sequenceInfo['sequenceTable'])) {
            $newIds = [];
            $previousVersionId = $this->readEntityVersion->getPreviousVersionId(
                CategoryInterface::class,
                1
            );

            $nextVersionId = $this->readEntityVersion->getNextVersionId(CategoryInterface::class, 1);
            $this->versionManager->setCurrentVersionId($previousVersionId);

            foreach ($entityRowsIn as $key => $row) {
                $entityRowsIn[$key]['created_in'] = $previousVersionId;
                $entityRowsIn[$key]['updated_in'] = $nextVersionId;
                $newIds[]['sequence_value'] = $row['entity_id'];
            }

            try {
                $connection = $this->appResource->getConnectionByName($metadata->getEntityConnectionName());
                $connection->insertMultiple(
                    $this->appResource->getTableName($sequenceInfo['sequenceTable']), $newIds
                );
            } catch (\Exception $e) {
                $this->logger->critical($e->getMessage(), $e->getTrace());
                throw new \Exception('TODO: use correct Exception class' . PHP_EOL . $e->getMessage());
            }

            $this->versionManager->setCurrentVersionId($nextVersionId);
        }

        return $entityRowsIn;
    }

    /**
     * Get entity field name
     *
     * @return string
     */
    public function getEntityFieldName()
    {
        $sequenceInfo = $this->sequenceRegistry->retrieve(CategoryInterface::class);
        if (isset($sequenceInfo['sequenceTable'])) {
            return 'row_id';
        }

        return 'entity_id';
    }
}
