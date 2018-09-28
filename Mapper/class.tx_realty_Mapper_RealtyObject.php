<?php

/**
 * This class represents a mapper for realty objects.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_RealtyObject extends Tx_Oelib_DataMapper
{
    /**
     * @var string the name of the database table for this mapper
     */
    protected $tableName = 'tx_realty_objects';

    /**
     * @var string the model class name for this mapper, must not be empty
     */
    protected $modelClassName = 'tx_realty_Model_RealtyObject';

    /**
     * the (possible) relations of the created models in the format DB column name => mapper name
     *
     * @var string[]
     */
    protected $relations = [];

    /**
     * cache by object number, OpenImmo object ID and language, using values
     * from createCacheKeyFromObjectNumberAndObjectIdAndLanguage as keys
     *
     * @var tx_realty_Model_RealtyObject[]
     */
    private $cacheByObjectNumberAndObjectIdAndLanguage = [];

    /**
     * Returns the number of realty objects in the city $city.
     *
     * @param tx_realty_Model_City $city the city for which to count the objects
     * @param string $additionalWhereClause must either be empty or start with "AND"
     *
     * @return int the number of objects in the given city, will be >= 0
     *
     * @throws \Tx_Oelib_Exception_Database
     */
    public function countByCity(tx_realty_Model_City $city, $additionalWhereClause = '')
    {
        return Tx_Oelib_Db::count(
            $this->tableName,
            '(city = ' . $city->getUid() . ') ' . $additionalWhereClause . ' AND ' . $this->getUniversalWhereClause()
        );
    }

    /**
     * Returns the number of realty objects in the district $district.
     *
     * @param tx_realty_Model_District $district
     *        the district for which to count the objects
     * @param string $additionalWhereClause must either be empty or start with "AND"
     *
     * @return int the number of objects in the given district, will be >= 0
     *
     * @throws \Tx_Oelib_Exception_Database
     */
    public function countByDistrict(tx_realty_Model_District $district, $additionalWhereClause = '')
    {
        return Tx_Oelib_Db::count(
            $this->tableName,
            '(district = ' . $district->getUid() . ') ' . $additionalWhereClause . ' AND '
            . $this->getUniversalWhereClause()
        );
    }

    /**
     * Finds a realty object by its object number, OpenImmo object ID and
     * language.
     *
     * @throws Tx_Oelib_Exception_NotFound
     *         if there is no realty object with the provided data
     *
     * @param string $objectNumber
     *        the object number of the object to find, may be empty
     * @param string $openImmoObjectId
     *        the OpenImmo Object ID of the object to find, must not be empty
     * @param string $language
     *        the language code (any format) of the object to find, may be empty
     *
     * @return tx_realty_Model_RealtyObject
     *         the realty object that matches all three parameters
     */
    public function findByObjectNumberAndObjectIdAndLanguage(
        $objectNumber,
        $openImmoObjectId,
        $language = ''
    ) {
        try {
            $model = $this->findByObjectNumberAndObjectIdAndLanguageFromCache(
                $objectNumber,
                $openImmoObjectId,
                $language
            );
        } catch (Tx_Oelib_Exception_NotFound $exception) {
            $model = $this->findByObjectNumberAndObjectIdAndLanguageFromDatabase(
                $objectNumber,
                $openImmoObjectId,
                $language
            );
        }

        return $model;
    }

    /**
     * Finds a realty object by its object number, OpenImmo object ID and
     * language from the cache.
     *
     * @throws Tx_Oelib_Exception_NotFound
     *         if there is no realty object with the provided data in the cache
     *
     * @param string $objectNumber
     *        the object number of the object to find, may be empty
     * @param string $openImmoObjectId
     *        the OpenImmo Object ID of the object to find, must not be empty
     * @param string $language
     *        the language code (any format) of the object to find, may be empty
     *
     * @return tx_realty_Model_RealtyObject
     *         the realty object that matches all three parameters
     */
    private function findByObjectNumberAndObjectIdAndLanguageFromCache(
        $objectNumber,
        $openImmoObjectId,
        $language
    ) {
        $cacheKey = $this->createCacheKeyFromObjectNumberAndObjectIdAndLanguage(
            $objectNumber,
            $openImmoObjectId,
            $language
        );
        if (!isset($this->cacheByObjectNumberAndObjectIdAndLanguage[$cacheKey])) {
            throw new Tx_Oelib_Exception_NotFound('No model found.', 1333035741);
        }

        return $this->cacheByObjectNumberAndObjectIdAndLanguage[$cacheKey];
    }

    /**
     * Creates a unique cache key for an object number, an OpenImmo object ID
     * and a language code.
     *
     * @param string $objectNumber
     *        an object number, may be empty
     * @param string $openImmoObjectId
     *        an OpenImmo Object ID, may be empty
     * @param string $language
     *        a language code (any format), may be empty
     *
     * @return string
     *         a cache key, will be unique for the provided triplet, will not be
     *         empty
     */
    private function createCacheKeyFromObjectNumberAndObjectIdAndLanguage(
        $objectNumber,
        $openImmoObjectId,
        $language
    ) {
        return $objectNumber . ':' . $openImmoObjectId . ':' . $language;
    }

    /**
     * Caches a model by additional combined keys.
     *
     * @param Tx_Oelib_Model $model the model to cache
     * @param string[] $data the data of the model as it is in the DB, must not be empty
     *
     * @return void
     */
    protected function cacheModelByCombinedKeys(
        Tx_Oelib_Model $model,
        array $data
    ) {
        $objectNumber = isset($data['object_number'])
            ? $data['object_number'] : '';
        $openImmoObjectId = isset($data['openimmo_obid'])
            ? $data['openimmo_obid'] : '';
        $language = isset($data['language']) ? $data['language'] : '';

        $cacheKey = $this->createCacheKeyFromObjectNumberAndObjectIdAndLanguage(
            $objectNumber,
            $openImmoObjectId,
            $language
        );
        $this->cacheByObjectNumberAndObjectIdAndLanguage[$cacheKey] = $model;
    }

    /**
     * Finds a realty object by its object number, OpenImmo object ID and
     * language from the database.
     *
     * @throws Tx_Oelib_Exception_NotFound
     *         if there is no realty object with the provided data in the
     *         database
     *
     * @param string $objectNumber
     *        the object number of the object to find, may be empty
     * @param string $openImmoObjectId
     *        the OpenImmo Object ID of the object to find, must not be empty
     * @param string $language
     *        the language code (any format) of the object to find, may be empty
     *
     * @return tx_realty_Model_RealtyObject
     *         the realty object that matches all three parameters
     */
    private function findByObjectNumberAndObjectIdAndLanguageFromDatabase(
        $objectNumber,
        $openImmoObjectId,
        $language
    ) {
        return $this->findSingleByWhereClause(
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $openImmoObjectId,
                'language' => $language,
            ]
        );
    }

    /**
     * Finds objects by ANID and PID. Only the first four characters of the ANID will be used for matching.
     *
     * @param string $anid offerer ID, may be empty
     * @param int $pid page UID
     *
     * @return \Tx_Oelib_List \Tx_Oelib_List<\tx_realty_Model_RealtyObject>
     */
    public function findByAnidAndPid($anid, $pid)
    {
        $databaseConnection = \Tx_Oelib_Db::getDatabaseConnection();
        $relevantPartOfAnid = \mb_substr($anid, 0, 4, 'UTF-8');

        return $this->findByWhereClause(
            'pid = ' . $pid . ' AND LEFT(openimmo_anid, 4) = "' .
            $databaseConnection->quoteStr($relevantPartOfAnid, $this->tableName) . '"'
        );
    }

    /**
     * Deletes objects that have the offerer ID $anid and PID $pid, but spares the $exceptions.
     *
     * Only the first four characters of the ANID will be used for matching.
     *
     * @param string $anid may not be empty
     * @param int $pid page UID
     * @param \Tx_Oelib_List $exceptions \Tx_Oelib_List<\tx_realty_Model_RealtyObject> to not delete
     *
     * @return \tx_realty_Model_RealtyObject[] deleted objects
     */
    public function deleteByAnidAndPidWithExceptions($anid, $pid, \Tx_Oelib_List $exceptions)
    {
        /** @var \tx_realty_Model_RealtyObject[] $deletedObjects */
        $deletedObjects = [];

        $matches = $this->findByAnidAndPid($anid, $pid);
        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        foreach ($matches as $realtyObject) {
            if (!$exceptions->hasUid($realtyObject->getUid())) {
                $this->delete($realtyObject);
                $deletedObjects[] = $realtyObject;
            }
        }

        return $deletedObjects;
    }
}
