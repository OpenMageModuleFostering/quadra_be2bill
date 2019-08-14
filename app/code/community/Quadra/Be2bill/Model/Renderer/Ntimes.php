<?php

/**
 * 1997-2016 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to modules@quadra-informatique.fr so we can send you a copy immediately.
 *
 * @author    Quadra Informatique <modules@quadra-informatique.fr>
 * @copyright 1997-2016 Quadra Informatique
 * @license   http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *
 * @category    Quadra
 * @package     Quadra_Be2bill
 */
class Quadra_Be2bill_Model_Renderer_Ntimes
{

    /**
     * Renderer for the select input ntimes (create / edit merchand account)
     *
     * @return multitype:multitype:number string
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 2, 'label' => '2'),
            array('value' => 3, 'label' => '3'),
            array('value' => 4, 'label' => '4'),
        );
    }

}
