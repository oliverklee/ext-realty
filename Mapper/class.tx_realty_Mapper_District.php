<?php

/**
 * This class represents a mapper for districts.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_District extends Tx_Oelib_DataMapper
{
    /**
     * @var string the name of the database table for this mapper
     */
    protected $tableName = 'tx_realty_districts';

    /**
     * @var string the model class name for this mapper, must not be empty
     */
    protected $modelClassName = 'tx_realty_Model_District';

    /**
     * @var string[] the (possible) relations of the created models in the format DB column name => mapper name
     */
    protected $relations = [
        'city' => 'tx_realty_Mapper_City',
    ];

    /**
     * the column names of additional string keys
     *
     * @var string[]
     */
    protected $additionalKeys = ['title'];

    /**
     * cache by district name and city UID, using values from
     * createCacheKeyFromNameAndCityUid as keys
     *
     * @var tx_realty_Model_District[]
     */
    private $cacheByNameAndCityUid = [];

    /**
     * Finds all districts that belong to a certain city.
     *
     * If $uid is zero, this function returns all districts without a city.
     *
     * @param int $uid
     *        the UID of the city for which to find the disctricts, must be >= 0
     *
     * @return Tx_Oelib_List the districts within the given city, may be empty
     */
    public function findAllByCityUid($uid)
    {
        return $this->findByWhereClause('city = ' . $uid, 'title ASC');
    }

    /**
     * Finds all districts that belong to a certain or no city.
     *
     * If $uid is zero, this function returns all districts without a city.
     *
     * @param int $uid
     *        the UID of the city for which to find the disctricts, must be >= 0
     *
     * @return Tx_Oelib_List the districts within the given city or without a city,
     *                       may be empty
     */
    public function findAllByCityUidOrUnassigned($uid)
    {
        return $this->findByWhereClause('city = 0 OR city = ' . $uid, 'title ASC');
    }

    /**
     * Finds a district by its name.
     *
     * @throws Tx_Oelib_Exception_NotFound if there is no district with the
     *                                     given name
     *
     * @param string $name the name of the district to find, must not be empty
     *
     * @return tx_realty_Model_District the district with the given name
     */
    public function findByName($name)
    {
        return $this->findOneByKey('title', $name);
    }

    /**
     * Finds a district by its name and its associated city.
     *
     * @throws Tx_Oelib_Exception_NotFound if there is no district with the
     *                                     given name and city
     *
     * @param string $districtName
     *        the name of the district to find, must not be empty
     * @param int $cityUid
     *        the UID of the city of the district to find, must be >= 0
     *
     * @return tx_realty_Model_District the district with the given name and city
     */
    public function findByNameAndCityUid($districtName, $cityUid)
    {
        if ($districtName === '') {
            throw new InvalidArgumentException('$districtName must not be empty.', 1333035628);
        }
        if ($cityUid < 0) {
            throw new InvalidArgumentException('$cityUid must be >= 0.', 1333035639);
        }

        try {
            $model = $this->findByNameAndCityUidFromCache(
                $districtName,
                $cityUid
            );
        } catch (Tx_Oelib_Exception_NotFound $exception) {
            $model = $this->findByNameAndCityUidFromDatabase(
                $districtName,
                $cityUid
            );
        }

        return $model;
    }

    /**
     * Finds a district by its name and its associated city from the cache.
     *
     * @throws Tx_Oelib_Exception_NotFound
     *         if there is no district with the given name and city in the cache
     *
     * @param string $districtName
     *        the name of the district to find, must not be empty
     * @param int $cityUid
     *        the UID of the city of the district to find, must be >= 0
     *
     * @return tx_realty_Model_District the district with the given name and city
     */
    private function findByNameAndCityUidFromCache($districtName, $cityUid)
    {
        $cacheKey = $this->createCacheKeyFromNameAndCityUid(
            $districtName,
            $cityUid
        );
        if (!isset($this->cacheByNameAndCityUid[$cacheKey])) {
            throw new Tx_Oelib_Exception_NotFound('No model found.', 1333035709);
        }

        return $this->cacheByNameAndCityUid[$cacheKey];
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
        $districtName = isset($data['title']) ? $data['title'] : '';
        if ($districtName === '') {
            return;
        }

        $cityUid = isset($data['city']) ? $data['city'] : 0;

        $cacheKey = $this->createCacheKeyFromNameAndCityUid($districtName, $cityUid);
        $this->cacheByNameAndCityUid[$cacheKey] = $model;
    }

    /**
     * Creates a unique cache key for a district name and a city UID.
     *
     * @param string $districtName
     *        the name of a district, must not be empty
     * @param int $cityUid the UID of a city of a district, must be >= 0
     *
     * @return string a cache key, will be unique for that name/city pair,
     *                will not be empty
     */
    private function createCacheKeyFromNameAndCityUid($districtName, $cityUid)
    {
        return $cityUid . ':' . $districtName;
    }

    /**
     * Finds a district by its name and its associated city from the database.
     *
     * @throws Tx_Oelib_Exception_NotFound if there is no district with the
     *                                     given name and city in the database
     *
     * @param string $districtName
     *        the name of the district to find, must not be empty
     * @param int $cityUid
     *        the UID of the city of the district to find, must be >= 0
     *
     * @return tx_realty_Model_District the district with the given name and city
     */
    private function findByNameAndCityUidFromDatabase($districtName, $cityUid)
    {
        return $this->findSingleByWhereClause([
            'title' => $districtName,
            'city' => $cityUid,
        ]);
    }
}
