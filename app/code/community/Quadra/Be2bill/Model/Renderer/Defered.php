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
class Quadra_Be2bill_Model_Renderer_Defered
{

    /**
     * Renderer for the select input defered (create / edit merchand account)
     *
     * @return multitype:multitype:number string
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label' => Mage::helper('be2bill')->__('1 jour')),
            array('value' => 2, 'label' => Mage::helper('be2bill')->__('2 jours')),
            array('value' => 3, 'label' => Mage::helper('be2bill')->__('3 jours')),
            array('value' => 4, 'label' => Mage::helper('be2bill')->__('4 jours')),
            array('value' => 5, 'label' => Mage::helper('be2bill')->__('5 jours')),
            array('value' => 6, 'label' => Mage::helper('be2bill')->__('6 jours')),
            array('value' => 7, 'label' => Mage::helper('be2bill')->__('7 jours')),
            array('value' => 8, 'label' => Mage::helper('be2bill')->__('8 jours')),
            array('value' => 9, 'label' => Mage::helper('be2bill')->__('9 jours')),
            array('value' => 10, 'label' => Mage::helper('be2bill')->__('10 jours')),
        );
    }

}
