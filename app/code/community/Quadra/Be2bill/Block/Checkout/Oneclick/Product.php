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
class Quadra_Be2bill_Block_Checkout_Oneclick_Product extends Mage_Catalog_Block_Product_View
{

    /**
     * Retrieves url for form submitting:
     * some objects can use setSubmitRouteData() to set route and params for form submitting,
     * otherwise default url will be used
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $additional
     * @return string
     */
    public function getSubmitUrl($product, $additional = array())
    {
        $submitRouteData = $this->getData('submit_route_data');
        if ($submitRouteData) {
            $route = $submitRouteData['route'];
            $params = isset($submitRouteData['params']) ? $submitRouteData['params'] : array();
            $submitUrl = $this->getUrl($route, array_merge($params, $additional));
        } else {
            $params['product'] = $product->getId();
            $submitUrl = $this->getUrl('be2bill/checkout_oneclick/orderProduct', array_merge($params, $additional));
        }
        return $submitUrl;
    }

    /**
     * URL OneClick
     *
     * @return string
     */
    public function getOneclickUrl()
    {
        return $this->getSubmitUrl($this->getProduct(), array('_secure' => true));
    }

    /**
     * Utilsation possible du oneClick
     * @return boolean
     */
    public function canOneclick()
    {
        $helper = Mage::helper('customer');
        $customer = $helper->getCustomer();
        /**
         * oneClick non actif
         * B2B standard non actif
         * Client non connecté
         * Client ne possede pas adresse
         * Client ne possede pas alias
         * Client ne possede pas N° CB
         * Client ne possede pas CB avec date valide
         */
        if ($helper->isLoggedIn() && $helper->customerHasAddresses()) {
            $colAlias = $customer->getAliasCollection();
            if ($colAlias != null) {
                foreach ($colAlias as $alias) {
                    $account = Mage::getModel('be2bill/merchandconfigurationaccount')->load($alias->getData('id_merchand_account'));
                    if ($alias->getData('card_number') != '' &&
                            $account->getActive() &&
                            $account->getOneClick()->getId() != '' &&
                            Mage::helper('be2bill')->checkIfCcExpDateIsValid($alias)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Utilsation possible du paiement n fois  via le oneClick
     * @return boolean
     */
    public function canSeveralOneclick()
    {
        /**
         * canOneclick non valide
         * B2B several non actif
         * Produit non eligible à oneClick
         */
        if (!$this->canOneclick() || !$this->getProduct()->getBe2billEnableOcSevPayment()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    /**
     *
     * @return boolean
     */
    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     *
     * @return boolean
     */
    public function customerHasAddresses()
    {
        return count($this->getCustomer()->getAddresses());
    }

    /**
     *
     * @param string $type
     * @return string Html
     */
    public function getAddressesHtmlSelect($type)
    {
        if ($this->isCustomerLoggedIn()) {
            $options = array();
            foreach ($this->getCustomer()->getAddresses() as $address) {
                $options[] = array(
                    'value' => $address->getId(),
                    'label' => $address->format('oneline')
                );
            }

            if ($type == 'billing') {
                $address = $this->getCustomer()->getPrimaryBillingAddress();
            } else {
                $address = $this->getCustomer()->getPrimaryShippingAddress();
            }

            if ($address) {
                $addressId = $address->getId();
            } else {
                return $this->__('S\'il vous plait, <a href="%s">ajouter une adresse de %s </a>.', Mage::getUrl('customer/address/', array('_secure' => true)), $type);
            }

            $select = $this->getLayout()->createBlock('core/html_select')
                    ->setName($type . '_address_id')
                    ->setId($type . '-address-select')
                    ->setClass('address-select')
                    ->setValue($addressId)
                    ->setOptions($options);

            return $select->getHtml();
        }
        return $this->__('S\'il vous plait, <a href="%s">ajouter une adresse</a>', Mage::getUrl('customer/address/new/', array('_secure' => true)));
    }

    /**
     * Creation des valeurs du select : choix de l ' alias
     * @return string
     */
    public function getAliasSelect()
    {
        $helper = Mage::helper('customer');
        $customer = $helper->getCustomer();
        $colAlias = $customer->getAliasCollection();
        $options = array();

		// boucle sur les alias du client
        foreach ($colAlias as $alias) {
            if (!Mage::helper('be2bill')->checkIfCcExpDateIsValid($alias)) {
                continue;
            }
			
			// recuperation des operations de paiement disponibles
			$oPaymentMethod = Mage::getModel('be2bill/merchandconfigurationaccount')->load($alias->getIdMerchandAccount());
			// verification si MP actif ?
            if (!$oPaymentMethod->getData('active')) {
                continue;
            }
			
			// si mirakl, il faut filtrer les operations selon Quadra_Be2bill_Helper_Mirakl
			if (Mage::helper('be2bill')->isMiraklInstalledAndActive() && $this->getProduct()->getMiraklOfferId()){
				// s'il s'agit d'un MP autorisé par Mirakl
				if(in_array($oPaymentMethod->getdata('b2b_xml_account_type_code'), Mage::helper('be2bill/mirakl')->getAllowedMPCode())){
					$oPaymentOptions = $oPaymentMethod->getOptionsCollectionForProductOck($this->getProduct()->getPrice(), true, $this->getProduct()->getBe2billEnableOcSevPayment());
					
                	// filtre sur le "Statut Mirakl" depuis la configuration de l'OPERATION "delivery"
                    $expr = new Zend_Db_Expr('
                        (main_table.b2b_xml_option_extra LIKE "%mkp_only%" AND main_table.b2b_xml_option_extra NOT LIKE "%all%")
                        OR
                        (main_table.b2b_xml_option_extra NOT LIKE "%mkp_only%" AND main_table.b2b_xml_option_extra LIKE "%all%")
                    ');
                    $oPaymentOptions
                    	->addFieldToFilter('b2b_xml_option', array('delivery'))
                    	->getSelect()->where($expr);
				}				
			}
			// si hors mirakl ou produit operateur
			else{
				$oPaymentOptions = $oPaymentMethod->getOptionsCollectionForProductOck($this->getProduct()->getPrice(), false, $this->getProduct()->getBe2billEnableOcSevPayment());
                $oPaymentOptions
                	->getSelect()->where('b2b_xml_option_extra NOT LIKE ?', '%mkp_only%');
			}
			
			$aOperation = array();
			if(isset($oPaymentOptions)){
				foreach ($oPaymentOptions as $oPaymentOption){
					// création du libellé		
					if($oPaymentOption->getdata('front_label') != null){
						$_label = $oPaymentOption->getdata('front_label');
					}
					else{
						if($oPaymentOption->getdata('b2b_xml_option') === 'ntimes'){
							$_label = str_replace('ntimes', $this->__('%s times', unserialize($oPaymentOption->getData('b2b_xml_option_extra'))), $oPaymentMethod->getFrontendLabel($oPaymentOption->getdata('b2b_xml_option')));	
						}
						else{
							$_label = $oPaymentMethod->getFrontendLabel($oPaymentOption->getdata('b2b_xml_option'));	
						}
					}
					
					// création de la value de l'operation          	
	            	$_code = $alias->getAlias() . '|' . $oPaymentMethod->getId() . '|' . $oPaymentOption->getdata('b2b_xml_option');
					
					$aOperation[$_code] = $_label;
					
					unset($oPaymentOption);
				}
				
				if($aOperation){
					$options[] = array('label' => $oPaymentMethod->getConfigurationAccountName() . ' ' . $alias->getCardNumber(), 'value' => $aOperation);
				}
				
				unset($oPaymentOptions);
				unset($oPaymentMethod);
			}
        }

		if($options){
	        $select = $this->getLayout()->createBlock('core/html_select')
		        ->setName('alias_code')
		        ->setId('alias-card-select')
		        ->setClass('select required')
		        ->setValue()
		        ->setOptions($options);
				
			unset($options);
	        return $select->getHtml();
		}
		else{
			return false;
		}
			
    }

    /**
     * Creation des valeurs du select : choix de la méthode de livraison
     * @return string
     */
	public function getShippingMethodsSelect()
	{
		return  Mage::helper('be2bill')->getShippingMethodsSelect(
			$this->getProduct()->getId(),
			Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping(),
			$this->getProduct()->getStockItem()->getMinSaleQty());
	}
	
    /**
     * Utilisation du paiement n fois en one click sur la page produit
     *
     * @return string
     */
    public function getArrayCanSeveralOneclick()
    {
        $result = array();

        $helper = Mage::helper('customer');
        $customer = $helper->getCustomer();
        $colAlias = $customer->getAliasCollection();


        foreach ($colAlias as $alias) {
            if (!Mage::helper('be2bill')->checkIfCcExpDateIsValid($alias)) {
                continue;
            }
            $merchand = Mage::getModel('be2bill/merchandconfigurationaccount')->load($alias->getData('id_merchand_account'));
            if ($merchand->getActive() && $merchand->getSeveral()->getData('id_b2b_merchand_configuration_account_options') != '' && $this->getProduct()->getBe2billEnableOcSevPayment()) {
                $result[$alias->getIdB2bAlias()] = unserialize($merchand->getSeveral()->getData('b2b_xml_option_extra'));
            }
        }
        return '(' . json_encode($result) . ')';
    }

}
