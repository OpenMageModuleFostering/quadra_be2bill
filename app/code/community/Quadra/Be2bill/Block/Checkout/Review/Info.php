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
 * @author Quadra Informatique <modules@quadra-informatique.fr>
 * @copyright 1997-2015 Quadra Informatique
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
class Quadra_Be2bill_Block_Checkout_Review_Info extends Mage_Checkout_Block_Cart_Abstract
{

    protected $_instance = null;

    public function getMethodInstance()
    {
        if (empty($this->_instance)) {
            $this->_instance = $this->getQuote()->getPayment()->getMethodInstance();
        }
        return $this->_instance;
    }

    public function getFormatedAmount()
    {
        $amount = $this->getQuote()->getBaseGrandTotal();
        return Mage::Helper('be2bill')->formatAmount($amount);
    }

    public function priceFormat($amount)
    {
        return Mage::helper('core')->currency($amount, true, false);
    }

}
