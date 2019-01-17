<?php

namespace OliverKlee\Realty\Tests\Functional\FrontEnd\Fixtures;

/**
 * This class represents a view for testing purposes.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class TestingFrontEndView extends \tx_realty_pi1_FrontEndView
{
    /**
     * Renders the view and returns its content.
     *
     * @param array $piVars form data array (piVars)
     *
     * @return string the view's content
     */
    public function render(array $piVars = [])
    {
        return 'Hi, I am the testingFrontEndView!';
    }
}
