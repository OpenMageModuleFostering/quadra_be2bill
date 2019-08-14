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
class Quadra_Be2bill_Model_Merchandconfigurationaccount extends Mage_Core_Model_Abstract
{

    protected $_availablePayments;
    protected $_availablePaymentOptions;
    protected $_hasAlias;
    protected $_helperData;
    protected $_oneClick;
    protected $_oneClickCVV;
    protected $_several;

    /**
     * Initialize
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('be2bill/merchandconfigurationaccount');
    }

    /**
     * Load borne by Identifiant
     *
     * @param   string $identifier
     * @return  Quadra_Be2bill_Model_Merchandconfigurationaccount
     */
    public function loadByIdentifier($identifier, $store = null)
    {
        $this->_getResource()->loadByIdentifier($this, $identifier, $store);
        return $this;
    }

    /**
     * Get Helper Be2bill Data
     * @return Quadra_Be2bill_Helper_Data
     */
    public function getHelperData()
    {
        if ($this->_helperData === null) {
            $this->_helperData = Mage::helper('be2bill');
        }
        return $this->_helperData;
    }

    /**
     * Get Collection of Contries
     */
    public function getAccountCountriesCollection()
    {
        return Mage::getModel('be2bill/merchandconfigurationaccountcountries')
                        ->getCollection()
                        ->addFieldToFilter('id_b2b_merchand_configuration_account', $this->getId());
    }

    /**
     * Delete Collection of Contries
     */
    public function deleteAccountCountriesCollection()
    {
        $collection = $this->getAccountCountriesCollection();
        foreach ($collection as $obj) {
            $obj->delete();
        }
    }

    /**
     * Get Collection of Options
     */
    public function getOptionsCollection()
    {
        return Mage::getModel('be2bill/merchandconfigurationaccountoptions')
                        ->getCollection()
                        ->addFieldToFilter('id_b2b_merchand_configuration_account', $this->getId());
    }

    /**
     * Get Collection of Options for oneClick from product page
     */
    public function getOptionsCollectionForProductOck($fAmount, $bIsMkpProduct, $bCanOckSeveral)
    {
    	
		// liste des code options a exclure car pas operation utilisable sur le front
		$aExcludedOptions = array(
			'oneclick',
			'oneclickcvv',
			'displaycreatealias',
			'3dsecure',
			'ageverification',
			'recurring');
		
		// si ock est désactivé sur la fiche produit
		if(!$bCanOckSeveral){
			$aExcludedOptions[] = 'ntimes';
		}
		
        $oCollection = Mage::getModel('be2bill/merchandconfigurationaccountoptions')
                        ->getCollection()
                        ->addFieldToFilter('id_b2b_merchand_configuration_account', $this->getId())
						->addFieldToFilter('active', 1)
		                ->addFieldToFilter('min_amount', array(array('null' => true), array('lt' => $fAmount)))
		                ->addFieldToFilter('max_amount', array(array('null' => true), array('gt' => $fAmount)))
						->addFieldToFilter('b2b_xml_option', array(
							'nin' => $aExcludedOptions));
							
		// si mirakl, il faut filtrer les operations selon 
		if (Mage::helper('be2bill')->isMiraklInstalledAndActive() && $bIsMkpProduct){
			// uniquement les paiements standards sont autorisés
			$oCollection->addFieldToFilter('b2b_xml_option', 'delivery');
		}								
								
		return $oCollection;
    }

    /**
     * Delete Collection of Options
     */
    public function deleteOptionsCollection()
    {
        $collection = $this->getOptionsCollection();
        foreach ($collection as $obj) {
            $obj->delete();
        }
    }

    /**
     * Retourne une liste de payments configurer dans l'admin
     * en fonction de :
     *
     * @param string $currency
     * @param string $country
     * @param int $storeId
     * @param boolean $recurring
     */
    public function getAvailablePayments($currency, $country, $storeId, $recurring = null)
    {
        if ($this->_availablePayments === null) {
            $this->_availablePayments = $this->_getResource()->getAvailablePayments($this, $currency, $country, $storeId, $recurring);
        }
        return $this->_availablePayments;
    }

    public function checkAvailablePaymentOption($currency, $country, $storeId, $recurring, $accountId, $account, $option)
    {
        return $this->_getResource()->checkAvailablePaymentOption($this, $currency, $country, $storeId, $recurring, $accountId, $account, $option);
    }

    /**
     * Retourne le nombre de jours pour le paiement différé
     * @return int $days
     */
    public function getDeferedDays()
    {
        $days = null;
        $col = Mage::getModel('be2bill/merchandconfigurationaccountoptions')
                ->getCollection()
                ->addFieldToFilter('id_b2b_merchand_configuration_account', $this->getId())
                ->addFieldToFilter('b2b_xml_option', 'defered')
                ->addFieldToFilter('active', '1');

        if ($col != null) {
            $days = unserialize($col->getFirstItem()->getData('b2b_xml_option_extra'));
        }
        return $days;
    }

    /**
     * Retourne un tableau de status pour la capture du paiement
     * @return array $status
     */
    public function getDeliveryStatus()
    {
        $status = null;
        $col = Mage::getModel('be2bill/merchandconfigurationaccountoptions')
                ->getCollection()
                ->addFieldToFilter('id_b2b_merchand_configuration_account', $this->getId())
                ->addFieldToFilter('b2b_xml_option', 'delivery')
                ->addFieldToFilter('active', '1');

        if ($col != null) {
            $status = unserialize($col->getFirstItem()->getData('b2b_xml_option_extra'));
        }

        return $status;
    }

    /**
     * Retourne les Options affichables sur le front
     *
     * @param float $amount
     * @param boolean $recurring
     */
    public function getAvailableFrontOptions($amount, $recurring)
    {
        if ($this->_availablePaymentOptions === null) {
            $this->_availablePaymentOptions = $this->_getResource()->getAvailableFrontOptions($this, $amount, $recurring);
        }
        return $this->_availablePaymentOptions;
    }

    /**
     * Retourne toutes les options en fonction de :
     *
     * @param float $amount
     * @param boolean $hasAlias
     */
    public function getAvailableOptions($amount, $hasAlias)
    {
        if ($this->_availablePaymentOptions === null) {
            $this->_availablePaymentOptions = $this->_getResource()->getAvailableOptions($this, $amount, $hasAlias);
        }
        return $this->_availablePaymentOptions;
    }

    /**
     * Utilisation de l'option OneClick possible ?
     */
    public function getOneClick()
    {
        if ($this->_oneClick === null) {
            $this->_oneClick = $this->_getResource()->getOneClick($this);
        }
        return $this->_oneClick;
    }

    /**
     * Utilisation de l'option OneClick CVV obligatoire ?
     */
    public function useOneClickCVV()
    {
        if ($this->_oneClickCVV === null) {
            $oneclick = $this->_getResource()->useOneClickCVV($this);
            if ($oneclick) {
                $this->_oneClickCVV = $oneclick->getActive();
            }
        }
        return $this->_oneClickCVV;
    }

    /**
     * Utilisation de l'option paiement en N fois ?
     */
    public function getSeveral()
    {
        if ($this->_several === null) {
            $this->_several = $this->_getResource()->getSeveral($this);
        }
        return $this->_several;
    }

    /**
     * Retourne l'url du Logo
     * @return string
     */
    public function getLogo()
    {
        if (file_exists(Mage::getBaseDir('media') . DS . $this->getLogoUrl())) {
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $this->getLogoUrl();
        } else {
            return $this->getLogoUrl();
        }
    }

    /**
     * Retourne le label pour le front du moyen de paiement Be2bill sélectionné
     *
     * @return string
     */
    public function getFrontendLabel($option = null)
    {
        $label = $this->getData('configuration_account_name');

        if ($option != null) {
            $label .= ' : ' . $option;
        }

        return $label;
    }

    /**
     * Utilisation de l'option de paiement 3 fois possible ?
     * @return array
     */
    public function getNtimes()
    {
        $result = $this->getOptionsCollection()
                ->addFieldToFilter('b2b_xml_option', 'ntimes');
        return unserialize($result->getFirstItem()->getData('b2b_xml_option_extra'));
    }

    /**
     * si refundAmount = remboursement
     * 
     * @param array $paramsNoHash passe en reference
     * @return boolean|string
     */
    public function getDefaultParameters($order, $info, $refundAmount = null, $miraklOrderId = null , &$paramsNoHash)
    {
    	$billingAddress = $order->getBillingAddress();
    	
        $parameters = Mage::getModel('be2bill/api_methods')->getAccountTypeParameters($info['account'], $info['operation'], $billingAddress->getCountryId());

        if (!$parameters) {
            return false;
        }
 
        $shippingAddress = $order->getShippingAddress();
        $customerId = $order->getCustomerId();
        if ($refundAmount != null) {
            $amount = $refundAmount;
        } else {
            $amount = $order->getBaseGrandTotal();
        }

        $customerEmail = ($billingAddress->getEmail()) ? $billingAddress->getEmail() : $order->getCustomerEmail();
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $default_params = array();
        foreach ($parameters as $param) {
            $version = $param['b2b_xml_account_type_parameter_set_version'];
            $type = strtoupper($param['b2b_xml_parameter_type']);
            $value = strtoupper($param['b2b_xml_parameter_value']);
    		$merchantHash = strtoupper($param['b2b_xml_parameter_merchant_hash']);
            
    		//Update du tableau des paramètres a ne pas prendre en compte pour le calcul du hash
            if($merchantHash == 'NO'){
            	$paramsNoHash[] = $param['b2b_xml_parameter_name'];
            }
            
            if ($type == 'REQUIRED' && $value = 'YES') {
                switch ($param['b2b_xml_parameter_name']) {
                    case 'AMOUNT':
                        $defaultParams['AMOUNT'] = $this->getHelperData()->formatAmount($amount);
                        break;
                    case 'ASKIBAN':
                        $defaultParams['ASKIBAN'] = 'NO';
                        break;
                    case 'BILLINGADDRESS':
                        $defaultParams['BILLINGADDRESS'] = substr(preg_replace("/\r|\n/", " ", $billingAddress->getStreetFull()), 0, 49);
                        break;
                    case 'BILLINGCITY':
                        $defaultParams['BILLINGCITY'] = substr($billingAddress->getCity(), 0, 254);
                        break;
                    case 'BILLINGCOUNTRY':
                        $defaultParams['BILLINGCOUNTRY'] = substr($billingAddress->getCountryId(), 0, 2);
                        break;
                    case 'BILLINGFIRSTNAME':
                        $defaultParams['BILLINGFIRSTNAME'] = substr($billingAddress->getFirstname(), 0, 14);
                        break;
                    case 'BILLINGLASTNAME':
                        $defaultParams['BILLINGLASTNAME'] = substr($billingAddress->getLastname(), 0, 29);
                        break;
                    case 'BILLINGMOBILEPHONE':
                        $defaultParams['BILLINGMOBILEPHONE'] = '0000000000';
                        if($billingAddress->getTelephone() != ''){
                        	$defaultParams['BILLINGMOBILEPHONE'] = $billingAddress->getTelephone();
                        }
                        break;
                    case 'BILLINGPHONE':
                        $defaultParams['BILLINGPHONE'] = substr(preg_replace('/[-\.\/\s]/', '', $billingAddress->getTelephone()), 0, 31);
                        break;
                    case 'BILLINGPOSTALCODE':
                        $defaultParams['BILLINGPOSTALCODE'] = substr($billingAddress->getPostcode(), 0, 8);
                        break;
                    case 'CART[N][NAME]':
                    	$items = $order->getAllVisibleItems();
                    	$i = 0;
                    	foreach ($items as $item) {
                    		$defaultParams['CART'][$i]['NAME'] = $item->getData('name');
                    		$i++;
                    	}
                        break;
                    case 'CART[N][TAX]':
                    	$items = $order->getAllVisibleItems();
                    	$i = 0;
                    	foreach ($items as $item) {
                    		$defaultParams['CART'][$i]['TAX'] = $this->getHelperData()->formatAmount($item->getData('tax_percent'));
                    		$i++;
                    	}
                        break;
                    case 'CART[N][QUANTITY]':
                    	$items = $order->getAllVisibleItems();
                    	$i = 0;
                    	foreach ($items as $item) {
                    		$defaultParams['CART'][$i]['QUANTITY'] = round($item->getData('qty_ordered'));
                    		$i++;
                    	}
                        break;
                    case 'CART[N][PRICE]':
                    	$items = $order->getAllVisibleItems();
                    	$i = 0;
                    	foreach ($items as $item) {
                    		$defaultParams['CART'][$i]['PRICE'] = $this->getHelperData()->formatAmount($item->getData('base_row_total'));
                    		$i++;
                    	}
                        break;
                    case 'CART[N][MERCHANTITEMID]':
                    	$items = $order->getAllVisibleItems();
                    	$i = 0;
                    	foreach ($items as $item) {
                    		$defaultParams['CART'][$i]['MERCHANTITEMID'] = $item->getData('product_id');
                    		$i++;
                    	}
                        break;
                    case 'CART[N][DISCOUNT]':
                    	$items = $order->getAllVisibleItems();
                    	$i = 0;
                    	foreach ($items as $item) {
                    		$defaultParams['CART'][$i]['DISCOUNT'] = '0';
                    		$i++;
                    	}
                        break;
                    case 'CLIENTADDRESS':
                        $defaultParams['CLIENTADDRESS'] = substr($billingAddress->format("oneline"), 0, 509);
                        break;
                    case 'CLIENTDOB':
                    	$defaultParams['CLIENTDOB'] = '1970-01-01';
                    	if ($customer->getDob() != "") {
                            $dateTimePart = explode(" ", $customer->getDob());
                            if (isset($dateTimePart[0])) {
                                $defaultParams['CLIENTDOB'] = $dateTimePart[0];
                            }
                        }
                        break;
                    case 'CLIENTEMAIL':
                        $defaultParams['CLIENTEMAIL'] = substr($customerEmail, 0, 254);
                        break;
                    case 'CLIENTGENDER':
                        if ($customer->getGender() == '1') {
                            $defaultParams['CLIENTGENDER'] = 'M';
                        } elseif ($customer->getGender() == '2') {
                            $defaultParams['CLIENTGENDER'] = 'F';
                        }
                        else {
                        	$defaultParams['CLIENTGENDER'] = 'M';
                        }
                        break;
                    case 'CLIENTIDENT':
                        $defaultParams['CLIENTIDENT'] = is_numeric($customerId) ? $customerId : substr($customerEmail, 0, 254);
                        break;
                    case 'CREATEALIAS':
                        $defaultParams['CREATEALIAS'] = 'yes';
                        break;
                    case 'DESCRIPTION':
                        $defaultParams['DESCRIPTION'] = ucfirst($info['operation']);
                        break;
                    case 'EXTRADATA':
                        $extradata = array(
                            'be2bill-' . $info['account_id'] . '-' . $info['operation']
                        );

                        if (Mage::helper('be2bill')->isMiraklInstalledAndActive()) {
                            if (Mage::helper('mirakl_connector/order')->isFullRemoteOrder($order)) {
                                $priceInclTax = array();
                                foreach ($order->getAllItems() as $item) {
                                    $key = $item->getMiraklShopId();
                                    if (!array_key_exists($key, $priceInclTax)) {
                                        $priceInclTax[$key] = 0;
                                    }
                                    $priceInclTax[$key] += $item->getRowTotalInclTax();
                                }
                                foreach ($priceInclTax as $shopId => $price) {
                                    $extradata[] = 'm[' . $shopId . ']=' . $this->getHelperData()->formatAmount($price);
                                }
                            } elseif (Mage::helper('mirakl_connector/order')->isRemoteOrder($order)) {
                                $oPriceInclTax = 0;
                                $mkpPriceInclTax = array();
                                foreach ($order->getAllItems() as $item) {
                                    $key = $item->getMiraklShopId();
                                    if (!empty($key)) {
                                        if (!array_key_exists($key, $mkpPriceInclTax)) {
                                            $mkpPriceInclTax[$key] = 0;
                                        }
                                        $mkpPriceInclTax[$key] += $item->getRowTotalInclTax();
                                    } else {
                                        $oPriceInclTax += $item->getRowTotalInclTax();
                                    }
                                }
                                $extradata[] = 'o=' . $this->getHelperData()->formatAmount($oPriceInclTax);
                                foreach ($mkpPriceInclTax as $shopId => $price) {
                                    $extradata[] = 'm[' . $shopId . ']=' . $this->getHelperData()->formatAmount($price);
                                }
                            } else {
                                $priceInclTax = 0;
                                foreach ($order->getAllItems() as $item) {
                                    $priceInclTax += $item->getRowTotalInclTax();
                                }
                                $extradata[] = 'o=' . $this->getHelperData()->formatAmount($priceInclTax);
                            }
                        } else {
                            $priceInclTax = 0;
                            foreach ($order->getAllItems() as $item) {
                                $priceInclTax += $item->getRowTotalInclTax();
                            }
                            $extradata[] = 'o=' . $this->getHelperData()->formatAmount($priceInclTax);
                        }

                        $defaultParams['EXTRADATA'] = implode('&', $extradata);
                        break;
                    case 'HIDECARDFULLNAME':
                        $defaultParams['HIDECARDFULLNAME'] = 'NO';
                        break;
                    case 'HIDECLIENTEMAIL':
                        $defaultParams['HIDECLIENTEMAIL'] = 'YES';
                        break;
                    case 'IDENTIFICATIONDOCID':
                        $defaultParams['IDENTIFICATIONDOCID'] = $info['identificationdocid'];
                        break;
                    case 'IDENTIFICATIONDOCTYPE':
                        $defaultParams['IDENTIFICATIONDOCTYPE'] = Mage::getStoreConfig('be2bill/be2bill_api/klarna_doc');
                        break; 
                    case 'IDENTIFIER':
                        $defaultParams['IDENTIFIER'] = $this->getLogin();
                        break;
                    case 'LANGUAGE':
                        $defaultParams['LANGUAGE'] = strtolower(substr(Mage::app()->getLocale()->getLocaleCode(), 3, 4));
                        break;
                    case 'METADATA':
                        $metadata = array(
                            'BC=' . substr($billingAddress->getCountryId(), 0, 2),
                            'BZ=' . substr($billingAddress->getPostcode(), 0, 8),
                            'SC=' . substr($shippingAddress->getCountryId(), 0, 2),
                            'SZ=' . substr($shippingAddress->getPostcode(), 0, 8)
                        );
                        $defaultParams['METADATA'] = implode('&', $metadata);
                        break;
                    case 'OPERATIONTYPE':
                        $defaultParams['OPERATIONTYPE'] = $info['operation'];
                        break;
                    case 'ORDERID':
                        $defaultParams['ORDERID'] = $order->getIncrementId();
                        break;
                    case 'SHIPTOADDRESS':
                        if ($shippingAddress) {
                            $defaultParams['SHIPTOADDRESS'] = substr(preg_replace("/\r|\n/", " ", $shippingAddress->getStreetFull()), 0, 49);
                        }
                        break;
                    case 'SHIPTOCITY':
                        if ($shippingAddress) {
                            $defaultParams['SHIPTOCITY'] = substr($shippingAddress->getCity(), 0, 254);
                        }
                        break;
                    case 'SHIPTOCOUNTRY':
                        if ($shippingAddress) {
                            $defaultParams['SHIPTOCOUNTRY'] = substr($shippingAddress->getCountryId(), 0, 2);
                        }
                        break;
                    case 'SHIPTOFIRSTNAME':
                        if ($shippingAddress) {
                            $defaultParams['SHIPTOFIRSTNAME'] = substr($shippingAddress->getFirstname(), 0, 14);
                        }
                        break;
                    case 'SHIPTOLASTNAME':
                        if ($shippingAddress) {
                            $defaultParams['SHIPTOLASTNAME'] = substr($shippingAddress->getLastname(), 0, 29);
                        }
                        break;
                    case 'SHIPTOPHONE':
                        if ($shippingAddress) {
                            $defaultParams['SHIPTOPHONE'] = substr(preg_replace('/[-\.\/\s]/', '', $shippingAddress->getTelephone()), 0, 31);
                        }
                        break;
                    case 'SHIPTOPOSTALCODE':
                        if ($shippingAddress) {
                            $defaultParams['SHIPTOPOSTALCODE'] = substr($shippingAddress->getPostcode(), 0, 8);
                        }
                        break;
                    case 'TRANSACTIONID':
						$defaultParams['TRANSACTIONID'] = $order->getPayment()->getParentTransactionId() != null ? $order->getPayment()->getParentTransactionId() : $order->getPayment()->getLastTransId();
						
						// si capture, nous recuperons l'id de l'authorization
						if($defaultParams['OPERATIONTYPE'] === 'capture'){
							$defaultParams['TRANSACTIONID'] = Mage::getModel('sales/order_payment_transaction')
								->getCollection()
									->addAttributeToFilter('order_id', array('eq' => $order->getEntityId()))
									->addAttributeToFilter('txn_type', array('eq' => 'authorization'))
									->getFirstItem()
										->getTxnId();
						}
						// si refund, nous recuperons l'id de la capture
						elseif($defaultParams['OPERATIONTYPE'] === 'refund'){
						 	$oTransactionCollection = Mage::getModel('sales/order_payment_transaction')
								->getCollection()
									->addAttributeToFilter('order_id', array('eq' => $order->getEntityId()))
									->addAttributeToFilter('txn_type', array('eq' => 'capture'));
									
							// si remboursement d'une commande Mirakl
							if($miraklOrderId){
								// boucle sur toutes les transactions pour recuperer celle correspondante à l'order mirakl
								foreach($oTransactionCollection as $oTransaction){
									$aAdditionalInformation = $oTransaction->getAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
									if(array_key_exists('miraklorderid', $aAdditionalInformation) && $aAdditionalInformation['miraklorderid'] === $miraklOrderId){
										$defaultParams['TRANSACTIONID'] = $oTransaction->getTxnId();
									}
								}
							}
							// si remboursement hors Mirakl
							else{
								
							}
						}
                        break;
                    case 'VERSION':
                        $defaultParams['VERSION'] = $version;
                        break;
                    case 'CLIENTIP':
                        $defaultParams['CLIENTIP'] = $_SERVER['REMOTE_ADDR'];
                        break;
                    case 'CLIENTREFERRER':
                        $defaultParams['CLIENTREFERRER'] = Mage::helper('core/http')->getRequestUri() != '' ? Mage::helper('core/http')->getRequestUri() : 'Unknow';
                        break;
                    case 'CLIENTUSERAGENT':
                        $defaultParams['CLIENTUSERAGENT'] = Mage::helper('core/http')->getHttpUserAgent() != '' ? Mage::helper('core/http')->getHttpUserAgent() : 'Server';
                        break;
                    case 'CARDFULLNAME':
                        $defaultParams['CARDFULLNAME'] = $billingAddress->getFirstname() . " " . $billingAddress->getLastname();
                        break;
                }
            }
        }

        return $defaultParams;
    }

}
