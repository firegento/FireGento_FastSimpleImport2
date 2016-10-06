<?php
namespace FireGento\FastSimpleImport\Model\Enterprise;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Sequence\SequenceRegistry;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Catalog\Api\Data\CategoryInterface;
class CategoryImportVersion
{

    /**
     * @var MetadataPool
     */
    private $metadataPool;
    /**
     * @var SequenceRegistry
     */
    private $sequenceRegistry;
    /**
     * @var VersionManager
     */
    private $versionManager;
    /**
     * @var ReadEntityVersion
     */
    private $readEntityVersion;

    public function __construct(
        MetadataPool $metadataPool,
        SequenceRegistry $sequenceRegistry,
        VersionManager $versionManager,
        ReadEntityVersion $readEntityVersion
    )
    {

        $this->metadataPool = $metadataPool;
        $this->sequenceRegistry = $sequenceRegistry;
        $this->versionManager = $versionManager;
        $this->readEntityVersion = $readEntityVersion;
    }

    /**
     * Processes the Entity
     * @param array $entityRowsIn
     * @return array
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
            $metadata->getEntityConnection()->insertMultiple(
                $this->getEntityConnection()->getTableName($sequenceInfo['sequenceTable']), $newIds
            );
            $this->versionManager->setCurrentVersionId($nextVersionId);
        }
        return $entityRowsIn;
    }
}
