<?php

/**
 * This class represents a titled model.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
abstract class tx_realty_Model_AbstractTitledModel extends Tx_Oelib_Model
{
    /**
     * @var string
     */
    protected $titleFieldName = 'title';

    /**
     * @var bool
     */
    protected $allowEmptyTitle = false;

    /**
     * Gets this model's title.
     *
     * @return string the model's title, might be empty
     */
    public function getTitle()
    {
        return $this->getAsString($this->titleFieldName);
    }

    /**
     * Sets this model's title.
     *
     * @param string $title the title to set, may be empty
     *
     * @return void
     */
    public function setTitle($title)
    {
        if (!$this->allowEmptyTitle && ($title === '')) {
            throw new InvalidArgumentException('$title must not be empty.', 1421163107);
        }

        $this->setAsString($this->titleFieldName, $title);
    }
}
