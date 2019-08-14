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
class Quadra_Be2bill_Model_Resource_Merchandconfigurationaccount extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Initialize
     */
    protected function _construct()
    {
        $this->_init('be2bill/merchandconfigurationaccount', 'id_b2b_merchand_configuration_account');
    }

    /**
     * Load Account Merchand by identifier and store id
     * @param Quadra_Be2bill_Model_Merchandconfigurationaccount $account
     * @param string $identifier
     * @param int $store
     * @return Quadra_Be2bill_Model_Resource_Merchandconfigurationaccount
     */
    public function loadByIdentifier(Quadra_Be2bill_Model_Merchandconfigurationaccount $account, $identifier, $store = null)
    {
        $adapter = $this->_getReadAdapter();
        $bind = array('login' => $identifier);
        $select = $adapter->select()
                ->from($this->getMainTable())
                ->where('login = "' . $identifier . '"');

        if ($store != null) {
            $select->where('core_store_id = ' . $store);
        }

        $accoundId = $adapter->fetchOne($select);

        if ($accoundId) {
            $this->load($account, $accoundId);
        } else {
            $account->setData(array());
        }

        return $this;
    }

    /**
     * Retourne les Options affichables sur le front
     *
     * @param Quadra_Be2bill_Model_Merchandconfigurationaccount $account
     * @param float $amount
     * @param boolean $recurring
     */
    public function getAvailableFrontOptions(Quadra_Be2bill_Model_Merchandconfigurationaccount $account, $amount, $recurring)
    {
        $collection = $account->getOptionsCollection()
                ->addFieldToFilter('active', 1)
                ->addFieldToFilter('min_amount', array(array('null' => true), array('lt' => $amount)))
                ->addFieldToFilter('max_amount', array(array('null' => true), array('gt' => $amount)));

        // Si profil recurrent alors on autorise uniquement le paiement standard
        if ($recurring) {
            $collection->addFieldToFilter('b2b_xml_option', array('standard'));
        } else {
            if (Mage::helper('be2bill')->isMiraklInstalledAndActive()) {
                $quote = Mage::getSingleton('checkout/session')->getQuote();

                if (Mage::helper('mirakl_frontend/quote')->isFullMiraklQuote($quote)) {
                    // Commandes marketplace uniquement
                    $collection->addFieldToFilter('b2b_xml_option', array('delivery'));

                    $expr = new Zend_Db_Expr('
                        (main_table.b2b_xml_option_extra LIKE "%mkp_only%" AND main_table.b2b_xml_option_extra NOT LIKE "%all%")
                        OR
                        (main_table.b2b_xml_option_extra NOT LIKE "%mkp_only%" AND main_table.b2b_xml_option_extra LIKE "%all%")
                    ');
                    $collection->getSelect()->where($expr);
                } elseif (Mage::helper('mirakl_frontend/quote')->isMiraklQuote($quote)) {
                    // Commandes mixtes
                    $collection->addFieldToFilter('b2b_xml_option', array('delivery'));
                    $collection->addFieldToFilter('b2b_xml_option_extra', array('like' => '%all%'));
                } else {
                    // Commandes opérateur uniquement
                    $collection->addFieldToFilter('b2b_xml_option', array('nin' => array('oneclick', 'oneclickcvv', 'displaycreatealias', '3dsecure', 'ageverification', 'recurring')));

                    $resource = Mage::getSingleton('core/resource');
                    $readConnection = $resource->getConnection('core_read');

                    $collection->getSelect()
					->where('b2b_xml_option_extra NOT LIKE ?', '%mkp_only%');
                }
            } else {
                $collection->addFieldToFilter('b2b_xml_option', array('nin' => array('oneclick', 'oneclickcvv', 'displaycreatealias', '3dsecure', 'ageverification', 'recurring')));
            }
        }
		//Mage::log($collection->getSelect()->__toString(), Zend_Log::DEBUG, 'be2bill.log', true);
        return $collection;
    }

    /**
     * Utilisation de l'option OneClick possible ?
     */
    public function getOneClick(Quadra_Be2bill_Model_Merchandconfigurationaccount $account)
    {
        $collection = $account->getOptionsCollection()
                ->addFieldToFilter('active', 1)
                ->addFieldToFilter('b2b_xml_option', array('in' => array('oneclick')));

        return $collection->getFirstItem();
    }

    /**
     * Utilisation de l'option payment n fois possible ?
     */
    public function getSeveral(Quadra_Be2bill_Model_Merchandconfigurationaccount $account)
    {
        $collection = $account->getOptionsCollection()
                ->addFieldToFilter('active', 1)
                ->addFieldToFilter('b2b_xml_option', array('in' => array('ntimes')));

        return $collection->getFirstItem();
    }

    /**
     * Utilisation de l'option OneClick CVV obligatoire ?
     */
    public function useOneClickCVV(Quadra_Be2bill_Model_Merchandconfigurationaccount $account)
    {
        $collection = $account->getOptionsCollection()
                ->addFieldToFilter('active', 1)
                ->addFieldToFilter('b2b_xml_option', array('in' => array('oneclickcvv')));

        return $collection->getFirstItem();
    }

    /**
     * Retourne une liste de payments configurer dans l'admin
     * en fonction de :
     *
     * @param Quadra_Be2bill_Model_Merchandconfigurationaccount $account
     * @param string $isoCurrency
     * @param string $isoCountry
     * @param int $storeId
     * @param boolean $recurring
     */
    public function getAvailablePayments(Quadra_Be2bill_Model_Merchandconfigurationaccount $account, $isoCurrency, $isoCountry, $storeId, $recurring)
    {
        $collection = $account->getCollection()
                ->addFieldToFilter('currency_iso', strtolower($isoCurrency))
                ->addFieldToFilter('active', 1)
                ->addFieldToFilter('core_store_id', array('in' => array(0, $storeId)));

        $collection->getSelect()
                ->joinLeft(
                        array('account_countries' => $this->getTable('be2bill/merchandconfigurationaccountcountries')),
                        'main_table.id_b2b_merchand_configuration_account=account_countries.id_b2b_merchand_configuration_account',
                        null
                )
                ->where('account_countries.country_iso = "' . strtolower($isoCountry) . '"');

        if ($recurring) {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $subQuery = $readConnection->select()
                    ->from(
                            array('account_options' => $this->getTable('be2bill/merchandconfigurationaccountoptions')),
                            array("account_options.id_b2b_merchand_configuration_account")
                    )
                    ->where('active = 1')
                    ->where('b2b_xml_option = "recurring"')
                    ->where('account_options.id_b2b_merchand_configuration_account = main_table.id_b2b_merchand_configuration_account')
            ;

            $collection->getSelect()->where('main_table.id_b2b_merchand_configuration_account IN (' . $subQuery . ')');
        }

		// si le module mirakl est installe et actif
		if (Mage::helper('be2bill')->isMiraklInstalledAndActive()) {
			// recuperation de la quote
			$quote = Mage::getSingleton('checkout/session')->getQuote();
	
			// Commandes marketplace uniquement OU Commandes mixtes
			if (Mage::helper('mirakl_frontend/quote')->isFullMiraklQuote($quote) || Mage::helper('mirakl_frontend/quote')->isMiraklQuote($quote)) {
				// ajouter filtre sur les MP eligibles a Mirakl  'CB/Visa/MasterCard' et 'Visa/MasterCard'
				$collection->getSelect()->where('main_table.b2b_xml_account_type_code IN ("'. implode('","',Mage::helper('be2bill/mirakl')->getAllowedMPCode()) .'")');
			}
			// Commandes opérateur uniquement
			else {
				// pas de filtre supplementaire
			}
		}

        return $collection;
    }

    public function checkAvailablePaymentOption(Quadra_Be2bill_Model_Merchandconfigurationaccount $accountM, $currency, $country, $storeId, $recurring, $accountId, $account, $option)
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        $collection = $accountM->getCollection()
                ->addFieldToFilter('currency_iso', strtolower($currency))
                ->addFieldToFilter('active', 1)
                ->addFieldToFilter('main_table.id_b2b_merchand_configuration_account', $accountId)
                ->addFieldToFilter('b2b_xml_account_type_code', $account)
                ->addFieldToFilter('core_store_id', array('in' => array(0, $storeId)));

        $collection->getSelect()
                ->joinLeft(
                        array('account_countries' => $this->getTable('be2bill/merchandconfigurationaccountcountries')),
                        'main_table.id_b2b_merchand_configuration_account=account_countries.id_b2b_merchand_configuration_account',
                        null
                )
                ->where('account_countries.country_iso = "' . strtolower($country) . '"');

        $subQuery1 = $readConnection->select()
                ->from(
                        array('account_options' => $this->getTable('be2bill/merchandconfigurationaccountoptions')),
                        array("account_options.id_b2b_merchand_configuration_account")
                )
                ->where('active = 1')
                ->where('b2b_xml_option = "' . $option . '"')
                ->where('account_options.id_b2b_merchand_configuration_account = main_table.id_b2b_merchand_configuration_account');

        $collection->getSelect()->where('main_table.id_b2b_merchand_configuration_account IN (' . $subQuery1 . ')');

        if ($recurring) {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $subQuery = $readConnection->select()
                    ->from(
                            array('account_options' => $this->getTable('be2bill/merchandconfigurationaccountoptions')),
                            array("account_options.id_b2b_merchand_configuration_account")
                    )
                    ->where('active = 1')
                    ->where('b2b_xml_option = "recurring"')
                    ->where('account_options.id_b2b_merchand_configuration_account = main_table.id_b2b_merchand_configuration_account')
            ;
            $collection->getSelect()->where('main_table.id_b2b_merchand_configuration_account IN (' . $subQuery . ')');
        }

        return $collection;
    }

}
