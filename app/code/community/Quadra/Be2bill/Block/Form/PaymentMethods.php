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
class Quadra_Be2bill_Block_Form_PaymentMethods extends Mage_Payment_Block_Form
{
	
	const OPERATION_TYPE_PAYMENT = 'payment';
	const OPERATION_TYPE_AUTH = 'authorization';
	const OPERATION_TYPE_CAPTURE = 'capture';
	const OPERATION_TYPE_REFUND = 'refund';
	const OPERATION_TYPE_ONECLICK = 'oneclick';
	const OPERATION_TYPE_SUBSCRIPTION = 'subscription';
	
    protected $_checkout;
    protected $_quote;
    protected $_notFrontOptions = array(
        'displaycreatealias',
        'oneclick',
        'oneclickcvv',
        '3dsecure'
    );

    /**
     *
     * @see Mage_Core_Block_Template::_construct()
     */
    protected function _construct()
    {
        $this->setTemplate('be2bill/form/payment_methods.phtml')->setMethodTitle('');
        parent::_construct();
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
     * Retrieve checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }

    /**
     * Retrieve sales quote model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if (empty($this->_quote)) {
            $this->_quote = $this->getCheckout()->getQuote();
        }
        return $this->_quote;
    }

    /**
     *
     * @param int accountId
     * @return boolean
     */
    public function ccExpDateIsValid($accountId)
    {
        return $this->helper('be2bill')->checkIfCcExpDateIsValid($this->getCustomerAlias($accountId));
    }

    public function getCcExpDate($date)
    {
        $_date = new Zend_date($date);
        return $_date->getYear()->toString("MM") . ' - ' . $_date->getYear()->toString("YY");
    }

    /**
     * Retourne Base Grand Total de la quote
     *
     * @return float
     */
    public function getQuoteBaseGrandTotal()
    {
        return (float)$this->getQuote()->getBaseGrandTotal();
    }

    public function getMethodLabelAfterHtml()
    {
        return $this->getTitle();
    }

    /**
     * Retourne le Titre du moyen de paiement be2bill
     * Ce titre est caché pr le js spécifique Be2bill
     * @return string
     */
    public function getTitle()
    {
        return $this->__('Choisir parmi...');
    }

    /**
     * Retourne l'objet Alias Du client pour le id du compte en paramètre
     *
     * @param int id merchand
     * @return Quadra_Be2bill_Model_Alias
     */
    public function getCustomerAlias($accountId)
    {
        return $this->getCustomer()->getAliasByMerchandAccount($accountId);
    }

    /**
     *
     * @param int $accountId
     * @return boolean
     */
    public function getCustomerHasAlias($accountId)
    {
        if ($this->getCustomerAlias($accountId)->getId() != null) {
            return true;
        }
        return false;
    }

    public function getOneClick($paymentMethod)
    {
        if ($this->getCustomerHasAlias($paymentMethod->getId()) && $paymentMethod->getOneClick() != null) {
            return $paymentMethod->getOneClick();
        } else {
            return null;
        }
    }

    /**
     * Retourne les paiements génériques configurer par le client
     * @return
     */
    public function getAvailablePayments()
    {
        $quote = $this->getQuote();

        $currency = $quote->getData('quote_currency_code');
        $country = $quote->getBillingAddress()->getData('country_id');
        $storeId = $quote->getData('store_id');
        $recurring = $quote->isNominal();
        $result = Mage::getModel('be2bill/merchandconfigurationaccount')->getAvailablePayments($currency, $country, $storeId, $recurring);

        return $result;
    }

    /**
     * Retourne les options affichage des paiements
     * @return
     */
    public function getAvailableFrontOptions($paymentMethod)
    {
        $amount = $this->getQuoteBaseGrandTotal();
        $recurring = $this->getQuote()->isNominal();
        $options = $paymentMethod->getAvailableFrontOptions($amount, $recurring);
        return $options;
    }

    
    /**
     * Est ce que le compte possède le paramètre identificationdocid ? (Pour Klarna)
     * @param $paymentMethod
     */
    public function getHasIdenDocId($code , $option)
    {
    	//echo    $code . ' : '.	$option;
    	switch ($option) {
    		case 'defered':
    		case 'delivery':
    			$operation = self::OPERATION_TYPE_AUTH;
    			break;
    		case 'capture':
    			$operation = self::OPERATION_TYPE_CAPTURE;
    			break;
    		case 'standard':
    		default :
    			$operation = self::OPERATION_TYPE_PAYMENT;
    			break;
    	}

    	$country = $this->getQuote()->getBillingAddress()->getCountryId();
    	$result = Mage::getModel('be2bill/api_methods')->hasIdenDocId($code, $operation, $country);
    	return $result != null ? true : false;
    	 
    }
    
    
    /**
     * Tableau des options non affichable sur le front
     * @return array
     */
    public function getNotFrontOptions()
    {
        return $this->_notFrontOptions;
    }

}
