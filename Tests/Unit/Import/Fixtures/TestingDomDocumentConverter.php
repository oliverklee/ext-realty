<?php

namespace OliverKlee\Realty\Tests\Unit\Import\Fixtures;

/**
 * This is merely a class used for unit tests. Don't use it for any other purpose.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class TestingDomDocumentConverter extends \tx_realty_domDocumentConverter
{
    /**
     * Adds a new element $value to the array $arrayExpand, using the key $key.
     * The element will not be added if is NULL.
     *
     * @param string[] $arrayToExpand
     *        array into which the new element should be inserted, may be empty
     * @param string $key the key to insert, must not be empty
     * @param mixed $value the value to insert, may be empty or even NULL
     *
     * @return void
     */
    public function addElementToArray(array &$arrayToExpand, $key, $value)
    {
        parent::addElementToArray($arrayToExpand, $key, $value);
    }

    /**
     * Returns the first grandchild of an element inside the realty record
     * with the current record number specified by the child's and the
     * grandchild's name. If one of these names can not be found or there are no
     * realty records, NULL is returned.
     *
     * @param string $nameOfChild name of child, must not be empty
     * @param string $nameOfGrandchild name of grandchild, must not be empty
     *
     * @return \DOMNode|null first grandchild with this name
     */
    public function findFirstGrandchild($nameOfChild, $nameOfGrandchild)
    {
        return parent::findFirstGrandchild($nameOfChild, $nameOfGrandchild);
    }

    /**
     * Fetches an attribute from a given node and returns name/value pairs as an
     * array. If there are no attributes, the returned array will be empty.
     *
     * @param \DOMNode|\DOMNodeList|null $nodeWithAttributes node from where to fetch the attribute, may be NULL
     *
     * @return string[] attributes and attribute values, empty if there are no attributes
     */
    public function fetchDomAttributes($nodeWithAttributes = null)
    {
        return parent::fetchDomAttributes($nodeWithAttributes);
    }

    /**
     * Substitutes XML namespaces from a node name and returns the name.
     *
     * @param \DOMNode|null $domNode node, may be NULL
     *
     * @return string node name without namespaces, may be empty
     */
    public function getNodeName(\DOMNode $domNode = null)
    {
        return parent::getNodeName($domNode);
    }

    /**
     * Loads the raw data from a \DOMDocument.
     *
     * @param \DOMDocument $rawRealtyData raw data to load, must not be NULL
     *
     * @return void
     */
    public function setRawRealtyData(\DOMDocument $rawRealtyData)
    {
        parent::setRawRealtyData($rawRealtyData);
    }
}
