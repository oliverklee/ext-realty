<?php

/**
 * This class represents a marker on a Google Map.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_mapMarker
{
    /**
     * @var string this marker's latitude
     */
    private $latitude = '';

    /**
     * @var string this marker's longitude
     */
    private $longitude = '';

    /**
     * @var bool
     */
    protected $coordinatesHaveBeenSet = false;

    /**
     * @var string this marker's title, quote- and tag-safe
     */
    private $title = '';

    /**
     * @var string this marker's info window HTML
     */
    private $infoWindowHtml = '';

    /**
     * Renders the JavaScript for creating this marker and adding it to an
     * object 'map'.
     *
     * @return string JavaScript snippet for the this marker, will be
     *                empty if this marker has no coordinates
     */
    public function render()
    {
        if (!$this->hasCoordinates()) {
            return '';
        }

        $result = 'var marker = new google.maps.Marker({' . LF .
            'position: ' . $this->getCoordinates() . ',' . LF .
            'map: map,' . LF .
            'title: "' . $this->title . '"});' . LF .
            'markersArray.push(marker);' . LF .
            'google.maps.event.addListener(marker, \'click\', function() {' . LF .
            'myInfoWindow.setContent(\'' . $this->infoWindowHtml . '\');' . LF .
            'myInfoWindow.open(map,this);});' . LF;

        return $result;
    }

    /**
     * Sets this marker's coordinates.
     *
     * @param float $latitude latitude
     * @param float $longitude longitude
     *
     * @return void
     */
    public function setCoordinates($latitude, $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        $this->coordinatesHaveBeenSet = true;
    }

    /**
     * Gets this marker's coordinates as a JavaScript GLatLng instantiation.
     *
     * @return string this marker's coordinates as a GLatLng instantiation
     *                JavaScript code snippet, an empty string if this
     *                marker has no coordinates.
     */
    public function getCoordinates()
    {
        if (!$this->hasCoordinates()) {
            return '';
        }

        return 'new google.maps.LatLng(' . number_format($this->latitude, 6, '.', '') . ',' .
            number_format($this->longitude, 6, '.', '') . ')';
    }

    /**
     * Sets this marker's title.
     *
     * @param string $title title, may be empty, must not be HTML-safe
     *
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = trim(addslashes(strip_tags($title)));
    }

    /**
     * Sets this marker's info window HTML
     *
     * @param string $html info window HTML, may be empty
     *
     * @return void
     */
    public function setInfoWindowHtml($html)
    {
        // 1. escapes \ to \\
        // 2. escapes ' to \'
        // 3. escapes </ to <\/ (because this is embedded JavaScript)
        // Note: We cannot use addslashes because " must not be escaped.
        $this->infoWindowHtml = str_replace(
            ['\\', '\'', '</'],
            ['\\\\', '\\\'', '<\\/'],
            $html
        );
    }

    /**
     * Checks whether this marker has both latitude and longitude.
     *
     * @return bool TRUE if this marker has coordinates, FALSE otherwise
     */
    private function hasCoordinates()
    {
        return $this->coordinatesHaveBeenSet;
    }
}
