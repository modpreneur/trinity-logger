<?php

namespace Trinity\Bundle\LoggerBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ElasticEntityProcessor
 *
 * Converts an elastic entity to an array and back to an entity.
 *
 * Refactored form ElasticLogService and ElasticReadLogService
 */
class ElasticEntityProcessor
{
    const METADATA_DATETIME_FIELDS = 'DatetimeFields';
    const METADATA_ENTITIES_TO_DECODE_FIELDS = 'EntitiesToDecode';
    const METADATA_SOURCE_ENTITY_CLASS_FIELD = 'SourceEntityClass';
    const METADATA_FIELD = 'META_DATA';
    const DOCTRINE_PROXY_NAMESPACE_PART = 'Proxies\\__CG__\\';

    /** @var  EntityManager */
    private $em;


    /**
     * ElasticEntityProcessor constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(?EntityManager $em = null)
    {
        $this->em = $em;
    }


    /**
     * Function transforms entity into array, the array stores type of entity
     * for recreation when obtain from elastic search and type and id of related
     * entities (FK) so they can be linked in decoding process.
     *
     * Gabi-TODO:Was not tested on M:N , N:1 or 1:N relations !!!
     * Gabi-TODO-2: it is as simple as it could be. N part is usually mapped, on elastic site should not FK
     *
     * @param $entity
     *
     * @return array
     */
    public function getElasticArray($entity): array
    {
        $entityArray = [];
        $entityArray[self::METADATA_FIELD] = [];

        //just a shorthand
        $entityMetadata = &$entityArray[self::METADATA_FIELD];

        $entityMetadata[self::METADATA_ENTITIES_TO_DECODE_FIELDS] = [];
        $entityMetadata[self::METADATA_DATETIME_FIELDS] = [];
        $entityMetadata[self::METADATA_SOURCE_ENTITY_CLASS_FIELD] = \get_class($entity);

        foreach ((array)$entity as $key => $value) {
            $keyParts = \explode("\x00", $key);
            $key = \array_pop($keyParts);

            if (\is_object($value)) {
                //elastic can work with DateTime, not with ours entities
                if ($value instanceof \DateTimeInterface) {

                    $entityMetadata[self::METADATA_DATETIME_FIELDS][] = $key;
                    $entityArray[$key] = $value->getTimestamp() * 1000; //convert seconds to milliseconds

                    continue; //the conversion is done, continue with the next property
                }

                if (\get_class($value) === Request::class) {
                    $entityArray[$key] = (string)$value;
                }

                if (\method_exists($value, 'getId')) {
                    $class = \get_class($value);
                    if (\strpos($class, self::DOCTRINE_PROXY_NAMESPACE_PART) === 0) {
                        $class = \substr($class, \strlen(self::DOCTRINE_PROXY_NAMESPACE_PART));
                    }
                    $id = $value->getId();
                    if ($id) {
                        $entityArray[$key] = "$class\x00$id";
                        $entityMetadata[self::METADATA_ENTITIES_TO_DECODE_FIELDS][] = $key;
                    } else {
                        unset($entityArray[$key]);
                    }
                }
            } else {
                $entityArray[$key] = $value;
            }
        }

        if (\array_key_exists('id', $entityArray) && !$entityArray['id']) {
            unset($entityArray['id']);
        }

        return $entityArray;
    }

    /**
     * Transform document from ElasticSearch obtained as array into entity matching
     * original entity. The relations 1:1 are recreated.     *
     *
     * @param array $responseArray
     * @param string $id
     *
     * @return $entity
     */
    public function decodeArrayFormat($responseArray, $id = '')
    {
        //just a shorthand
        $entityMetadata = &$responseArray[self::METADATA_FIELD];

        $entity = null;

        $relatedEntities = $entityMetadata[self::METADATA_ENTITIES_TO_DECODE_FIELDS];
        $entityClass = $entityMetadata[self::METADATA_SOURCE_ENTITY_CLASS_FIELD];
        $timestampFields = $entityMetadata[self::METADATA_DATETIME_FIELDS] ?? [];

        unset($entityMetadata[self::METADATA_ENTITIES_TO_DECODE_FIELDS]);
        unset($entityMetadata[self::METADATA_SOURCE_ENTITY_CLASS_FIELD]);
        unset($entityMetadata[self::METADATA_DATETIME_FIELDS]);

        $entity = new $entityClass($id);

        foreach ($responseArray as $key => $value) {
            $setter = "set${key}";

            if (\in_array($key, $relatedEntities, true)) {
                $value = $this->getEntity($value);
            }

            if (\in_array($key, $timestampFields, true)) {
                //the timezone is taken from the server config
                //$value contains the timestamp in milliseconds!
                $value = new \DateTime('@' . ($value / 1000));
            }

            if ($value) {
                $entity->$setter($value);
            }
        }

        return $entity;
    }

    /**
     * Transform reference into doctrine entity
     *
     * @param string $identification
     *
     * @return mixed $value
     */
    public function getEntity(string $identification)
    {
        if (null === $this->em) {
            return null;
        }

        $subEntity = \explode("\x00", $identification);
        $value = null;

        if (isset($subEntity[1])) {
            $value = $this->em->getRepository($subEntity[0])->find($subEntity[1]);
        }

        if (!$value) {
            $value = new $subEntity[0]();
        }

        return $value;
    }
}
