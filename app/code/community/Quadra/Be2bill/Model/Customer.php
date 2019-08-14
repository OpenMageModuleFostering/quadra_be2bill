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
class Quadra_Be2bill_Model_Customer extends Mage_Customer_Model_Customer
{

    /**
     *  Retourne la collection d'alias du client
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getAliasCollection()
    {
        return Mage::getModel('be2bill/alias')->getCollection()->addFieldToFilter('id_customer', $this->getId());
    }

    /**
     * Retourne la collection d'alias du client en fonction du compte de paiement
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getAliasByMerchandAccount($merchandId)
    {
        return $this->getAliasCollection()->addFieldToFilter('id_merchand_account', $merchandId)->getFirstItem();
    }

}
