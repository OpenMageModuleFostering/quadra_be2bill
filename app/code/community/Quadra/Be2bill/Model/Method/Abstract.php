<?php

/**
 * 1997-2016 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License(OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to modules@quadra-informatique.fr so we can send you a copy immediately.
 *
 * @author    Quadra Informatique <modules@quadra-informatique.fr>
 * @copyright 1997-2016 Quadra Informatique
 * @license   http://www.opensource.org/licenses/OSL-3.0 Open Software License(OSL 3.0)
 *
 * @category    Quadra
 * @package     Quadra_Be2bill
 */
abstract class Quadra_Be2bill_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{

    const OPERATION_TYPE_PAYMENT = 'payment';
    const OPERATION_TYPE_AUTH = 'authorization';
    const OPERATION_TYPE_CAPTURE = 'capture';
    const OPERATION_TYPE_REFUND = 'refund';
    const OPERATION_TYPE_ONECLICK = 'oneclick';
    const OPERATION_TYPE_SUBSCRIPTION = 'subscription';

    protected $_currentOperationType = "";
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canManageRecurringProfiles = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canUseInternal = false;
    protected $_formBlockType = 'be2bill/form_paymentMethods';
    protected $_infoBlockType = 'be2bill/info_paymentMethods';

    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund()
    {
        $payment = $this->getInfoInstance();
        $merchandAccount = $payment->getAdditionalInformation('account');
        $result = Mage::getModel('be2bill/api_methods')->hasRefund($merchandAccount);

        return count($result) > 0;
    }

    /**
     * Check partial refund availability for invoice
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        $payment = $this->getInfoInstance();
        $merchandAccount = $payment->getAdditionalInformation('account');
        $result = Mage::getModel('be2bill/api_methods')->hasRefundPartial($merchandAccount);

        return count($result) > 0;
    }

    /**
     * @param string $paymentAction
     * @param Varien_Object $stateObject
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {
        return $this;
    }

    /**
     * @return boolean
     */
    public function isInitializeNeeded()
    {
        if ($this->getConfigPaymentAction() == self::ACTION_AUTHORIZE)
            return false;
        return true;
    }

    public function authorize(Varien_Object $payment, $amount)
    {
        parent::authorize($payment, $amount);
        if ($this->getConfigPaymentAction() == self::ACTION_AUTHORIZE) {
            $payment->setIsTransactionPending(1);
        }
        return $this;
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Method_Purchaseorder
     */
    public function assignData($data)
    {
        parent::assignData($data);

        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        //Sécurité est ce que le client à bien selectionné une methode de paiement Be2bill générique
        if (!isset($data['be2bill_method'])) {
        	Mage::log('Erreur 1 de securite : Le client n a pas selectionne un methode de paiement generique be2bill', null, 'be2bill_erreur_securite.log', true);
        	Mage::log($data, null, 'be2bill_erreur_securite.log', true);
        	Mage::log('Fin Erreur 1', null, 'be2bill_erreur_securite.log', true);
        	
        	Mage::throwException(Mage::helper('be2bill')->__('Veuillez vérifier le choix de votre moyen de paiement'));
            return;
        }

        $infos = explode('.', $data['be2bill_method']);
        $accountId = $infos[0];
        $account = $infos[1];
        $option = $infos[2];

        if (count($infos) != 3) { //Sécurité
        	Mage::log('Erreur 2 de securite : il manque un parametre pour le choix de la methode de paiement be2bill', null, 'be2bill_erreur_securite.log', true);
        	Mage::log($data, null, 'be2bill_erreur_securite.log', true);
        	Mage::log('Fin Erreur 2', null, 'be2bill_erreur_securite.log', true);
        	
        	Mage::throwException(Mage::helper('be2bill')->__('Veuillez vérifier le choix de votre moyen de paiement'));
            return;
        }

        $currency = $this->getQuote()->getData('quote_currency_code');
        $country = $this->getQuote()->getBillingAddress()->getData('country_id');
        $storeId = $this->getQuote()->getData('store_id');
        $recurring = $this->getQuote()->isNominal();
        //Sécurité
        $checkCol = Mage::getModel('be2bill/merchandconfigurationaccount')->checkAvailablePaymentOption($currency, $country, $storeId, $recurring, $accountId, $account, $option);
        if (count($checkCol) < 1) { //Sécurité 
            Mage::log('Erreur 3 de securite : Le moyen de paiement selectionne ne semble pas etre active', null, 'be2bill_erreur_securite.log', true);
            Mage::log($data, null, 'be2bill_erreur_securite.log', true);
            Mage::log('Fin Erreur 3', null, 'be2bill_erreur_securite.log', true);
            
            Mage::throwException(Mage::helper('be2bill')->__('Veuillez vérifier le choix de votre moyen de paiement'));
            return;
        }

        $merchand = Mage::getModel('be2bill/merchandconfigurationaccount')->load($accountId);

        $this->getInfoInstance()->setAdditionalInformation('account_id', $accountId);
        $this->getInfoInstance()->setAdditionalInformation('account', $account);
        $this->getInfoInstance()->setAdditionalInformation('options', $option);

        $info = $data[$data['be2bill_method']];

        $this->getInfoInstance()->setAdditionalInformation('create_oneclick', isset($info['oneclick']) && $info['oneclick'] == "create_oneclick" ? "yes" : "no");
        $this->getInfoInstance()->setAdditionalInformation('use_oneclick', isset($info['oneclick']) && $info['oneclick'] == "use_oneclick" ? "yes" : "no");
        $this->getInfoInstance()->setAdditionalInformation('cvv_oneclick', isset($info['cvv_oneclick']) ? trim($info['cvv_oneclick']) : '');

		
    	if(array_key_exists('label', $info)){
	        $this->getInfoInstance()->setAdditionalInformation('be2bill_method_label', $info['label']);
		}
		
		if(array_key_exists('identificationdocid', $info)){
			$identDocId = $info['identificationdocid'];
			//test sécurité : longueur > 8 & < 65
			if (strlen($identDocId) < 8 || strlen($identDocId) > 64){
				Mage::log('Erreur 4 de securite : le nbre de caracteres "identificationdocid" n est pas correcte (entre 8 et 64)', null, 'be2bill_erreur_securite.log', true);
				Mage::log($data, null, 'be2bill_erreur_securite.log', true);
				Mage::log('Fin Erreur 4', null, 'be2bill_erreur_securite.log', true);
				 
				Mage::throwException(Mage::helper('be2bill')->__('Veuillez vérifier le choix de votre moyen de paiement'));
			} 
			
			$this->getInfoInstance()->setAdditionalInformation('identificationdocid', $info['identificationdocid']);
			
			
		}
		
        if(isset($data['be2bill_method_label'])){
	        $this->getInfoInstance()->setAdditionalInformation('be2bill_method_label', $data['be2bill_method_label']);
        }

        switch ($option) {
            case 'defered':
            case 'delivery':
                $operation = self::OPERATION_TYPE_AUTH;
                $action = self::ACTION_AUTHORIZE;
                break;

            case 'capture':
                $operation = self::OPERATION_TYPE_CAPTURE;
                $action = self::ACTION_AUTHORIZE_CAPTURE;
                break;

            case 'standard':
            default :
                $operation = self::OPERATION_TYPE_PAYMENT;
                $action = self::ACTION_AUTHORIZE_CAPTURE;
                break;
        }

        if ($option == 'ntimes') {
            $this->getInfoInstance()->setAdditionalInformation('ntimes', $merchand->getNtimes());
        } else if ($option == 'defered') { //si paiement différé alors on stock le nombre de jour différé
            $this->getInfoInstance()->setAdditionalInformation('defered-days', true);
        } else if ($option == 'delivery') { //si paiement à la livraison
            $this->getInfoInstance()->setAdditionalInformation('delivery', true);
        }

        $this->getInfoInstance()->setAdditionalInformation('operation', $operation);
        $this->getInfoInstance()->setAdditionalInformation('action', $action);
        $this->getInfoInstance()->setAdditionalInformation('mode', $merchand->getData('b2b_xml_mode_code'));

        if ((isset($info['oneclick']) && $info['oneclick'] == "use_oneclick") && $colAlias = $this->getQuote()->getCustomer()->getAliasByMerchandAccount($accountId)) {
            $this->getInfoInstance()->setAdditionalInformation('alias', $colAlias->getData('alias'));
        } else {
            $this->getInfoInstance()->setAdditionalInformation('alias', null);
        }

        return $this;
    }

    /**
     * Test si OneClick
     *
     * @return boolean
     */
    public function isOneClickMode()
    {
        if ($this->getInfoInstance()->getAdditionalInformation('use_oneclick') == 'yes') {
            return true;
        }

        return false;
    }

    /**
     * @deprecated
     */
    public function isPaypalMode()
    {
        if ($this->getInfoInstance()->getAdditionalInformation('account') == 'paypal') {
            return true;
        }

        return false;
    }

    public function getConfigPaymentAction()
    {
        return $this->getInfoInstance()->getAdditionalInformation('action');
    }

    /**
     * Get be2bill session namespace
     *
     * @return Quadra_Be2bill_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('be2bill/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     *
     * @param float $amount
     */
    public function unFormatAmount($amount)
    {
        return $amount / 100;
    }

    /**
     *
     * @param float $amount
     */
    public function formatAmount($amount)
    {
        return round($amount, 2) * 100;
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getOrderPlaceRedirectUrl()
    {
        $mode = $this->getInfoInstance()->getAdditionalInformation('mode');

        if ($this->isOneClickMode() && $this->getQuote()->isNominal()) {
            return false;
        }

        if ($this->isOneClickMode()) {
            return Mage::getUrl('be2bill/payments/oneclick', array('_secure' => true));
        }

        if ($mode == 'directlink') {
            return Mage::getUrl('be2bill/payments/directlink', array('_secure' => true));
        }

        if ($mode == 'form' || $mode == 'direct-submit') {
            return Mage::getUrl('be2bill/payments/redirect', array('_secure' => true));
        } else if ($mode == 'form-iframe') {
            return 'javascript:void(0)';
        }

        return false;
    }

    public function generateHASH($params, $password , $paramsNoHash = null)
    {
        return $this->getApi()->generateHASH($params, $password, $paramsNoHash);
    }

    public function getRedirectUrl()
    {
        $mode = null;
        if ($this->getOrder()) {
            $mode = $this->getOrder()->getPayment()->getAdditionalInformation('mode');
        }
        return $this->getApi()->getRedirectUrl($mode);
    }

    /**
     * Retourne la liste des parametres pour l'envoi à be2bill
     */
    public function getCheckoutFormFields()
    {
        $params = $this->getParameters();
        $this->_debug($params);
        return $params;
    }

    /**
     *
     * Lors de la facturation de la commande dans l'admin
     * @see Mage_Payment_Model_Method_Abstract::capture()
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture($payment, $amount)
    {
        parent::capture($payment, $amount);
        if ($this->isOneClickMode() && $this->getConfigPaymentAction() == self::ACTION_AUTHORIZE_CAPTURE) {
            //$this->oneclick($payment, $amount);
        } elseif ($this->getConfigPaymentAction() == self::ACTION_AUTHORIZE) {
            $this->_currentOperationType = self::OPERATION_TYPE_CAPTURE;
            $params = $this->getParameters($payment->getOrder(), $this->_currentOperationType, $amount);
            $this->_debug($params);
            $service = $this->getApi();
            // @var $response Quadra_Be2bill_Model_Api_Response
            $response = $service->send($this->_currentOperationType, $params);
            $this->_debug($response);
            if (!$response->isSuccess()) {
                Mage::logException(new Exception("Response: " . print_r($response->getData(), 1)));
                Mage::throwException("Error code: " . $response->getExecCode() . " " . $response->getMessage());
            } else {
                $payment->setIsPaid(1);
                $payment->setTransactionId($response->getTransactionId());
                $payment->setIsTransactionClosed(0);
				
                //ajout du detail de la transaction pour les remboursement
		        $aResponse = $response->getData();
				if(isset($aResponse['amount'])){
					$aResponse['amount'] = Mage::helper('core')->currency($aResponse['amount']/100, true, false);
				}
				else{
					$aResponse['amount'] = Mage::helper('core')->currency($amount, true, false);
				}
                $payment->setTransactionAdditionalInfo(
                        array(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS => $aResponse), $payment->getParentTransactionId()
                );
            }
        }

        return $this;
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see Mage_Checkout_OnepageController::savePaymentAction()
     * @see Mage_Sales_Model_Quote_Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return '';
    }

    public function getOrder()
    {
        /* @var $order Mage_Sales_Model_Order */
        $orderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);


        if (!$order->getId()) {
            return false;
        }

        return $order;
    }

    /**
     * Retourne un tableau de paramètres pour l'api Be2bill
     *
     * Les paramètres servent pour le remboursement
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $operation
     * @param float $amount
     * @return array $params
     */
    public function getParameters($order = null, $operation = null, $amount = null, $miraklOrderId = null, &$paramsNoHash = null)
    {
        //Profil recurrent (nominal)
        //Si la personne passe comme d'un profil R sans alias : il faut lui en créer un
        if (($profileIds = $this->getCheckout()->getLastRecurringProfileIds())) {
            if (is_array($profileIds)) {
                return $this->getParametersForCreateAliasWithoutOrder($profileIds, " - (Alias creation for recurring payment)");
            }
            Mage::throwException("An error occured. Profile Ids not present!");
        }
        if ($order == null) {
            $order = $this->getOrder();
			//$order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true);
			//$order->save();
        }

        $payment = $order->getPayment();
        $addInfo = $payment->getAdditionalInformation();

        $merchand = Mage::getModel('be2bill/merchandconfigurationaccount')->load($addInfo['account_id']);
        if ($operation != null) {
            $addInfo['operation'] = $operation;
        }
        
        //tableau des parametre à ne pas prendre en compte pour le calcul du hash
        if($paramsNoHash == null)
        	$paramsNoHash = array();
     
        $params = $merchand->getDefaultParameters($order, $addInfo, $amount, $miraklOrderId, $paramsNoHash);
        $params = Mage::getModel('be2bill/merchandconfigurationaccountoptions')->setOptionsParameters($addInfo['operation'], $params, $addInfo['account']);
        

        if ($operation != self::OPERATION_TYPE_REFUND && $operation != self::OPERATION_TYPE_CAPTURE) {
            $amount = $order->getBaseGrandTotal();
            $extra_options = Mage::getModel('be2bill/api_methods')->getExtraOptions($addInfo['account_id']);
            if ($extra_options && is_array($extra_options)) {
                foreach ($extra_options as $extra) {
                    $available = true;
                    if ($extra['b2b_xml_option'] == '3dsecure') {
                        $available = $this->_available3dSecure($extra, $order, $amount);
                    }
                    if ($available) {
                        $params = Mage::getModel('be2bill/merchandconfigurationaccountoptions')->setOptionsParameters($extra['b2b_xml_option'], $params, $addInfo);
                    }
                }
            }

            if ($addInfo['use_oneclick'] == 'yes') {
                if ($addInfo['cvv_oneclick'] != null) {
                    $params = Mage::getModel('be2bill/merchandconfigurationaccountoptions')->setOptionsParameters('oneclickcvv', $params, $addInfo);
                } else {
                    $params = Mage::getModel('be2bill/merchandconfigurationaccountoptions')->setOptionsParameters('oneclick', $params, $addInfo);
                }
                unset($params['DISPLAYCREATEALIAS']);
                unset($params['CREATEALIAS']);
                unset($params['HIDECLIENTEMAIL']);
                unset($params['HIDECARDFULLNAME']);
            }


            if ($addInfo['options'] == 'ntimes') {
                $params = Mage::getModel('be2bill/merchandconfigurationaccountoptions')->setOptionsParameters('ntimes', $params, $addInfo);
            }
        }
        
        $params['HASH'] = $this->generateHASH($params, $merchand->getData('password'), $paramsNoHash);
		
        ksort($params);
        return $params;
    }

    /**
     * Verifie si L'option 3D secure peux etre utilisée.
     *
     * @param array $extra
     * @param Mage_Sales_Model_Order $order
     * @param float $amount
     * @return boolean $available
     */
    protected function _available3dSecure($extra, $order, $amount)
    {
        $shippingAdd = $order->getShippingAddress() ? $order->getShippingAddress() : $order->getBillingAddress();

        $available = true;
        //test montant minimum & maximum
        if ((float) $extra['min_amount'] && $amount < (float) $extra['min_amount']) {
            $available = false;
        }
        if ((float) $extra['max_amount'] && $amount > (float) $extra['max_amount']) {
            $available = false;
        }

        if ($extra['active'] == 1) { //Pour 3d secure il deux types d'activation : le full 3d secure active=2 et le selective active=1
            $tabValues = unserialize($extra['b2b_xml_option_extra']);

            if ($shippingAdd->getCountry() == 'FR') {
                //Verification du code Postale
                $allowedPostcode = array_filter(explode(',', $tabValues['postcode']));

                if (!empty($allowedPostcode)) {
                    $postcode = $shippingAdd->getPostcode();
                    $find = false;
                    foreach ($allowedPostcode as $pc) {
                        if (strlen($pc) == 5) {
                            if ($pc == $postcode) {
                                $find = $pc;
                                break;
                            }
                        } else {
                            if (preg_match("/^{$pc}/", $postcode)) {
                                $find = $pc;
                                break;
                            }
                        }
                    }
                    if (!$find) {
                        $available = false;
                    }
                }
            }

            //Verification du pays
            $tabValues['country_iso'] = array_filter($tabValues['country_iso']);
            if (!empty($tabValues['country_iso']) && $tabValues['country_iso'] != '') {
                if (!in_array($shippingAdd->getCountryId(), $tabValues['country_iso'])) {
                    $available = false;
                }
            }

            //Verification de la method de livraison
            $tabValues['shipping_method'] = array_filter($tabValues['shipping_method']);
            if (!empty($tabValues['shipping_method']) && $tabValues['shipping_method'] != '') {
                if (!in_array($order->getData('shipping_method'), $tabValues['shipping_method'])) {
                    $available = false;
                }
            }
        }
        return $available;
    }

    /**
     * Send directlink
     *
     * @param   Varien_Object $invoicePayment
     * @return  Mage_Payment_Model_Abstract
     */
    public function directlink(Varien_Object $payment, $amount)
    {
        $params = $this->getParameters();
        $service = $this->getApi();
        $debugData = array_merge($params, array("method" => __METHOD__));
        $this->_debug($debugData);
        $response = $service->send($payment->getAdditionalInformation('operation'), $params);

        $this->_debug($response);
        return $response;
    }

    /**
     * Send Oneclick
     *
     * @param   Varien_Object $invoicePayment
     * @return  Mage_Payment_Model_Abstract
     */
    public function oneclick(Varien_Object $payment, $amount)
    {
        $params = $this->getParameters();
        $service = $this->getApi();
        $debugData = array_merge($params, array("method" => __METHOD__));
        $this->_debug($debugData);
        $response = $service->send($payment->getAdditionalInformation('operation'), $params);
        $this->setData('response', $response);

        $this->_debug($response);

        if (!$response->isSuccess()) {
            Mage::logException(new Exception("Response: " . print_r($response->getData(), 1)));
            Mage::throwException("Error code: " . $response->getExeccode() . " " . $response->getMessage());
        } else {
            $this->responseToPayment($payment, $response);
        }

        return $this;
    }

    /**
     * Get be2bill api service
     *
     * @return Quadra_Be2bill_Model_Api_Service
     */
    public function getApi()
    {
        return Mage::getSingleton('be2bill/api_service', array('methodInstance' => $this));
    }

    /**
     * Define if debugging is enabled
     *
     * @return bool
     */
    public function getDebugFlag()
    {
        return Mage::getStoreConfig('be2bill/be2bill_api/debug');
    }

    /**
     * Used to call debug method from not Payment Method context
     *
     * @param mixed $debugData
     */
    public function debugData($debugData)
    {
        $this->_debug($debugData);
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    protected function _debug($debugData)
    {
        if ($this->getDebugFlag()) {
            Mage::getModel('be2bill/log_adapter', 'payment_' . $this->getCode() . '.log')
                    ->setFilterDataKeys($this->_debugReplacePrivateDataKeys)
                    ->log($debugData);
        }
    }

    public function ipnPostSubmit()
    {
        if (!$this->hasResponse()) {
            Mage::throwException("NO Response in IPN");
        }

        $this->_debug($this->getResponse()->setData("method", __METHOD__));

        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($this->getResponse()->getIncrementId());
        if (!$order->getId() && strpos($this->getResponse()->getIncrementId(), 'recurring') === false) {
            Mage::throwException("NO Order Found!");
        }
        if (strpos($this->getResponse()->getIncrementId(), 'recurring') !== false) {
            list( $action, $type, $profileId ) = explode("-", $this->getResponse()->getIncrementId());

            if ($profileId) {
                /* @var $profile Mage_Sales_Model_Recurring_Profile */
                $profile = Mage::getModel('sales/recurring_profile')->load($profileId);
                if ($profile->getId()) {
                    $customer = Mage::getModel('customer/customer')->load($profile->getCustomerId());
                    if ($customer->getId()) {
                        $orderInfo = $profile->getOrderInfo();
                        $quote = Mage::getModel('sales/quote')->load($orderInfo['entity_id']);
                        $addInfoPayment = $quote->getPayment()->getAdditionalInformation();
                        $this->responseToCustomerAlias($customer, $addInfoPayment['account_id']);
                        if ($action == 'create' || $action == "payment") {
                            $this->createProfileOrder($profile, $this->getResponse());
                        }
                        return $this;
                    }
                    Mage::throwException(Mage::helper('be2bill')->__("Client % D introuvable (récurrent)", $profile->getCustomerId()));
                }
                Mage::throwException(Mage::helper('be2bill')->__("Profil ID: %d introuvable (récurrent)", $profileId));
            }
            Mage::throwException(Mage::helper('be2bill')->__("Commmande introuvable (récurrent)"));
        }

        $sendOrderEmail = false;
        $lastTxnId = $order->getPayment()->getLastTransId();
        /* @var $payment Mage_Sales_Model_Order_Payment */
        $payment = $order->getPayment();
        // need to save transaction id
        $this->responseToPayment($payment);
        if ($this->getResponse()->isSuccess() && $this->getResponse()->getTransactionId() != $lastTxnId) {
            if ($this->getResponse()->hasAlias() && trim($this->getResponse()->getAlias()) != "") {

                $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());

                if ($customer->getId()) {
                    $this->responseToCustomerAlias($customer, $payment->getAdditionalInformation('account_id'));
                }
            }

            switch ($this->getResponse()->getOperationType()) {
                case self::OPERATION_TYPE_PAYMENT :
                    if ($payment->getAdditionalInformation('options') == 'ntimes' && $order->hasInvoices()) {
                        $order->addStatusToHistory($order->getStatus(), Mage::helper('be2bill')->__('Nouveau débit avec le code % s et le message: % s .', $this->getResponse()->getExecCode(), $this->getResponse()->getMessage()));
                        $order->save();
                    } else {
                        if ($order->isCanceled()) {
                            foreach ($order->getAllItems() as $item) {
                                $item->setQtyCanceled(0);
                            }
                        }
                        $newOrderStatus = "processing";
                        // need to convert from order into invoice
                        $invoice = $order->prepareInvoice();
                        $invoice->register()->capture();
                        Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();

                        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $newOrderStatus, Mage::helper('be2bill')->__('Facture #%s créée', $invoice->getIncrementId()), $notified = true);

                        // for compatibility
                        // if(method_exists($payment, 'addTransaction')) {
                        // $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, $order);
                        // }
                        $sendOrderEmail = true;
                    }
                    break;
                case self::OPERATION_TYPE_AUTH :
                    if ($order->isCanceled()) {
                        foreach ($order->getAllItems() as $item) {
                            $item->setQtyCanceled(0);
                        }
                    }
                    $payment->setIsTransactionClosed(0);
                    $payment->authorize(false, $this->unFormatAmount($this->getResponse()->getAmount()));
                    $newOrderStatus = 'pending_be2bill';

                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $newOrderStatus, Mage::helper('be2bill')->__("En attende de la capture de la transaction '%s' et de montant %s", $this->getResponse()->getTransactionId(), $order->getBaseCurrency()->formatTxt($order->getBaseTotalDue())), $notified = true);
                    // for compatibility
                    // if(method_exists($payment, 'addTransaction')) {
                    // $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, $order);
                    // }
                    $sendOrderEmail = true;
                    break;
                default :
                    $order->addStatusToHistory($order->getStatus(), Mage::helper('be2bill')->__('Notification de Be2bill . Type d\'opération : "% s" , la transaction ID: % s , execcode : "% s" , un message: "% s" .', $this->getResponse()->getOperationType(), $this->getResponse()->getTransactionId(), $this->getResponse()->getExecCode(), $this->getResponse()->getMessage()));
            }
            $order->save();
            if ($sendOrderEmail && !$order->getEmailSent() && $order->getCanSendNewEmailFlag()) {
                try {
                    if (method_exists($order, 'queueNewOrderEmail')) {
                        $order->queueNewOrderEmail();
                    } else {
                        $order->sendNewOrderEmail();
                    }
                } catch (Exception $e) {
                    Mage::log('ipnPostSubmit 18');
                    Mage::logException($e);
                }
            }
        } elseif ($this->getResponse()->getTransactionId() == "" || $this->getResponse()->getTransactionId() != $lastTxnId) {
            $order->cancel();
            $order->addStatusToHistory($order->getStatus(), Mage::helper('be2bill')->__('Erreur lors du traitement du paiement. Code %s ==> %s.', $this->getResponse()->getExecCode(), $this->getResponse()->getMessage()));
            $order->save();
        }
        return $this;
    }

    /**
     *
     * @param Mage_Sales_Model_Recurring_Profile $profile
     * @param Quadra_Be2bill_Model_Api_Response $response
     * @return Mage_Sales_Model_Order
     */
    protected function createProfileOrder(Mage_Sales_Model_Recurring_Profile $profile, Quadra_Be2bill_Model_Api_Response $response)
    {
        $amount = $this->getAmountFromProfile($profile);
        $productItemInfo = new Varien_Object;
        $type = "Regular";

        if ($type == 'Trial') {
            $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_TRIAL);
        } elseif ($type == 'Regular') {
            $productItemInfo->setPaymentType(Mage_Sales_Model_Recurring_Profile::PAYMENT_TYPE_REGULAR);
        }

        if ($this->isInitialProfileOrder($profile)) { // because is not additonned in prodile obj
            $productItemInfo->setPrice($profile->getBillingAmount() + $profile->getInitAmount());
        }

        $order = $profile->createOrder($productItemInfo);

        $this->responseToPayment($order->getPayment(), $response);

        $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($response->getIdentifier(), $order->getStoreId());
        $order->getPayment()->setAdditionalInformation('b2b_recurring', 1);
        $order->getPayment()->setAdditionalInformation('account_id', $merchandAccount->getId());
        $order->getPayment()->setAdditionalInformation('account', $merchandAccount->getData('b2b_xml_account_type_code'));
        $order->getPayment()->setAdditionalInformation('options', 'standard');
        $order->getPayment()->setAdditionalInformation('be2bill_method_label', $merchandAccount->getData('configuration_account_name'));

        $order->save();

        $profile->addOrderRelation($order->getId());
        $order->getPayment()->registerCaptureNotification($amount);
        $order->save();

        // notify customer
        if ($invoice = $order->getPayment()->getCreatedInvoice()) {
            if (!$order->getEmailSent() && $order->getCanSendNewEmailFlag()) {
                try {
                    if (method_exists($order, 'queueNewOrderEmail')) {
                        $order->queueNewOrderEmail();
                    } else {
                        $order->sendNewOrderEmail();
                    }
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
            $message = Mage::helper('be2bill')->__('Le client à été notifié de la facture #%s.', $invoice->getIncrementId());
            $comment = $order->addStatusHistoryComment($message)
                    ->setIsCustomerNotified(true)
                    ->save();

            /* Add this to send invoice to customer */
            $invoice->setEmailSent(true);
            $invoice->save();
            $invoice->sendEmail();
        }

        return $order;
    }

    protected function responseToPayment($payment, $response = null)
    {
        if (is_null($response)) {
            $response = $this->getResponse();
        }

        $payment->setTransactionId($response->getTransactionId());
        $payment->setCcExpMonth($response->getCcExpMonth());
        $payment->setCcExpYear($response->getCcExpYear());
        $payment->setCcOwner($response->getCcOwner());
        $payment->setCcType($response->getCcType());
        $payment->setCcStatusDescription($response->getCcStatusDescription());
        $payment->setCcLast4($response->getCcLast4());
        $payment->setCcNumberEnc($response->getCcNumberEnc());
        /**
         * ajout du detail de la transaction
         */
         
        $aResponse = $response->getData();
		if(isset($aResponse['amount'])){
			$aResponse['amount'] = Mage::helper('core')->currency($aResponse['amount']/100, true, false);
		}
        $payment->setTransactionAdditionalInfo(
                array(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS => $aResponse), $response->getTransactionId()
        );

        return $this;
    }

    /**
     * Add method to calculate amount from recurring profile
     * @param Mage_Sales_Model_Recurring_Profile $profile
     * @return int $amount
     * */
    public function getAmountFromProfile(Mage_Sales_Model_Recurring_Profile $profile)
    {
        $amount = $profile->getBillingAmount() + $profile->getTaxAmount() + $profile->getShippingAmount();

        if ($this->isInitialProfileOrder($profile)) {
            $amount += $profile->getInitAmount();
        }

        return $amount;
    }

    protected function isInitialProfileOrder(Mage_Sales_Model_Recurring_Profile $profile)
    {
        if (count($profile->getChildOrderIds()) && current($profile->getChildOrderIds()) == "-1") {
            return true;
        }

        return false;
    }

    /**
     * Si le client a selectionné un produit récurrent et si le client n'a pas d'alias déjà existant.
     * Retourne un tableau de paramètre pour la création de l'alias
     *
     * A Revoir (refacto)
     * @param array $profileIds
     * @param string $desc
     * @return array $params
     */
    public function getParametersForCreateAliasWithoutOrder(array $profileIds, $desc = ' - (Alias creation)')
    {
        $this->_currentOperationType = self::OPERATION_TYPE_PAYMENT;
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $params['CLIENTIDENT'] = $customer->getId();

        $orderId = 'create-recurring';
        $amount = 0;
     
        foreach ($profileIds as $profileId) {
            /* @var $profile Mage_Sales_Model_Recurring_Profile */
            $profile = Mage::getModel('sales/recurring_profile')->load($profileId);
            $orderInfo = $profile->getOrderInfo();
            $quote = Mage::getModel('sales/quote')->load($orderInfo['entity_id']);
            $addInfoPayment = $quote->getPayment()->getAdditionalInformation();
            $merchand = Mage::getModel('be2bill/merchandconfigurationaccount')->load($addInfoPayment['account_id']);
            $parameters = Mage::getModel('be2bill/api_methods')->getAccountTypeParameters($addInfoPayment['account']);

            foreach ($parameters as $param) {
                $version = $param['b2b_xml_account_type_parameter_set_version'];
                break;
            }

            $orderId .= "-" . $profileId;
            $amount += $this->getAmountFromProfile($profile);

            $params['IDENTIFIER'] = $merchand->getLogin();
            $params['DESCRIPTION'] = $addInfoPayment['operation'];
            $params['VERSION'] = $version;

            break; //because only one nominal item in cart is authorized and be2bill not manage many profiles
        }

        $params['OPERATIONTYPE'] = $this->_currentOperationType;
        $params['ORDERID'] = $orderId;
        $params['AMOUNT'] = $this->formatAmount($amount);
        $params['CLIENTEMAIL'] = $customer->getEmail();
        $params['FIRSTNAME'] = $customer->getFirstname();
        $params['LASTNAME'] = $customer->getLastname();
        $params['3DSECURE'] = $this->getConfigData('use_3dsecure') ? "yes" : "no";
        $params['CREATEALIAS'] = "yes";
        $params['DESCRIPTION'] = $parameters['DESCRIPTION'] . $desc;
        $params['HIDECLIENTEMAIL'] = $this->getConfigData('hide_client_email') ? 'yes' : 'no';
        $params['HIDECARDFULLNAME'] = $this->getConfigData('hide_card_fullname') ? 'yes' : 'no';
        $params['HASH'] = $this->generateHASH($params, $merchand->getData('password'));

        return $params;
    }

    /**
     * A Revoir (refacto)
     * Appeler par l'observer pour les paiements reccurents
     * @param Mage_Sales_Model_Recurring_Profile $profile
     * @return Quadra_Be2bill_Model_Method_Abstract
     */
    public function subscription(Mage_Sales_Model_Recurring_Profile $profile)
    {
        $this->_currentOperationType = self::OPERATION_TYPE_PAYMENT;
        $orderInfo = unserialize($profile->getOrderInfo());
        $additionalInfo = unserialize($profile->getAdditionalInfo());
        $quote = Mage::getModel('sales/quote')->load($orderInfo['entity_id']);
        $addInfoPayment = $quote->getPayment()->getAdditionalInformation();
        $merchand = Mage::getModel('be2bill/merchandconfigurationaccount')->load($addInfoPayment['account_id']);

        $parameters = Mage::getModel('be2bill/api_methods')->getAccountTypeParameters($addInfoPayment['account']);

        foreach ($parameters as $param) {
            $version = $param['b2b_xml_account_type_parameter_set_version'];
            break;
        }

        $amount = $this->getAmountFromProfile($profile);
        if (!count($profile->getChildOrderIds())) {
            $amount += $profile->getInitAmount();
        }

        $params['IDENTIFIER'] = $merchand->getLogin();
        $params['DESCRIPTION'] = $addInfoPayment['operation'];
        $params['VERSION'] = $version;
        $params['CLIENTIP'] = $orderInfo['remote_ip'];
        $params['CLIENTREFERRER'] = Mage::helper('core/http')->getRequestUri() != '' ? Mage::helper('core/http')->getRequestUri() : 'Unknow';
        $params['CLIENTUSERAGENT'] = Mage::helper('core/http')->getHttpUserAgent() != '' ? Mage::helper('core/http')->getHttpUserAgent() : 'Server';
        $params['CLIENTIDENT'] = $orderInfo['customer_id'];
        $params['CLIENTEMAIL'] = $orderInfo['customer_email'];
        $params['ORDERID'] = "payment-recurring-" . $profile->getId();
        $params['AMOUNT'] = $this->formatAmount($amount);
        $params['DESCRIPTION'] = $params['DESCRIPTION'] . " (Recurring)";
        $params['OPERATIONTYPE'] = self::OPERATION_TYPE_PAYMENT;
        $params['ALIAS'] = $addInfoPayment['alias'];
        $params['ALIASMODE'] = self::OPERATION_TYPE_SUBSCRIPTION;
        $params['HASH'] = $this->generateHASH($params, $merchand->getData('password'));


        $service = $this->getApi();
        $debugData = array_merge($params, array("method" => __METHOD__));
        $this->_debug($debugData);
        $response = $service->send($this->_currentOperationType, $params);
        $this->setData('response', $response);
        $this->_debug($response);

        if (!$response->isSuccess()) {
            Mage::logException(new Exception("Response: " . print_r($response->getData(), 1)));
        }
        return $this;
    }

    /**
     * @return Quadra_Be2bill_Model_Api_Response
     */
    public function getResponse()
    {
        return $this->getData('response');
    }

    /**
     * Creer / MAJ d'un alias pour le client (lier a un moyen de paiement générique be2bill ex : amex)
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param int $merchandAccountId
     * @param Object $response
     * @return $this
     */
    protected function responseToCustomerAlias($customer, $merchandAccountId, $response = null)
    {
        if (is_null($response)) {
            $response = $this->getResponse();
        }

        $alias = $customer->getAliasByMerchandAccount($merchandAccountId);
        if (!$alias->getId()) {
            $alias = Mage::getModel('be2bill/alias');
        }

        $dateEnd = new Zend_Date($response->getCcValidityDate(), 'MM-yy');
        $dateEnd->set(1, Zend_Date::DAY)->set(0, Zend_Date::HOUR_SHORT)->set(0, Zend_Date::MINUTE_SHORT);

        $alias->setIdCustomer($customer->getId());
        $alias->setIdMerchandAccount($merchandAccountId);
        $alias->setAlias($response->getAlias());
        $alias->setCardType(strtolower($response->getCardtype()));
        $alias->setDateEnd($dateEnd);
        $alias->setDateAdd(now());
        $alias->setCardNumber($response->getCcNumberEnc());

        $alias->save();
        return $this;
    }

    /**
     * Refund money
     *
     * @param   Varien_Object $payment
     * @param   float $amount
     * @return  Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $this->_currentOperationType = self::OPERATION_TYPE_REFUND;
        $params = $this->getParameters($payment->getOrder(), $this->_currentOperationType, $amount);

        parent::refund($payment, $amount);

        $service = $this->getApi();
        $this->_debug($params);

        $response = $service->send($this->_currentOperationType, $params);
        $this->_debug($response);

        if (!$response->isSuccess()) {
            if ($response->getData('execcode') == '2008') {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('be2bill')->__('Commande réglée en 3x. Veuillez effectuer le remboursement des échéances via l\'Extranet Be2bill'));
            } else {
                Mage::throwException("Error code: " . $response->getExeccode() . " " . $response->getMessage());
            }

            Mage::logException(new Exception("Response: " . print_r($response->getData(), 1)));
        } else {
            $payment->setTransactionId($response->getTransactionId());
            $payment->setIsTransactionClosed(1);

            //Ajout du detail de la transaction pour les rembrousement
	        $aResponse = $response->getData();
			if(isset($aResponse['amount'])){
				$aResponse['amount'] = Mage::helper('core')->currency($aResponse['amount']/100, true, false);
			}
            $payment->setTransactionAdditionalInfo(
                    array(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS => $aResponse), $payment->getParentTransactionId()
            );
        }

        return $this;
    }

    /**
     * Mirakl
     * Capture marketplace products
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return  Mage_Payment_Model_Abstract
     */
    public function captureMkpProducts($payment, $amount, $miraklOrderId)
    {
        parent::capture($payment, $amount);

        if ($this->isOneClickMode() && $this->getConfigPaymentAction() == self::ACTION_AUTHORIZE_CAPTURE) {
            //$this->oneclick($payment, $amount);
        } elseif ($this->getConfigPaymentAction() == self::ACTION_AUTHORIZE) {
            $order = $payment->getOrder();
            $shippingAmount = 0;

            if (Mage::helper('mirakl_connector/order')->isFullRemoteOrder($order) && ($amount == ($order->getTotalDue() - $order->getBaseShippingAmount()))) {                        // Capture also shipping amount
                $shippingAmount = $order->getBaseShippingAmount();
                $amount += $shippingAmount;
            }

            $this->_currentOperationType = self::OPERATION_TYPE_CAPTURE;
            $paramsNoHash = array();
            $params = $this->getParameters($payment->getOrder(), $this->_currentOperationType, $amount , null ,$paramsNoHash);

            $addInfo = $payment->getAdditionalInformation();
            $extra_options = Mage::getModel('be2bill/api_methods')->getMiraklExtraOptions($addInfo['account_id']);

            if ($extra_options && is_array($extra_options)) {
                foreach ($extra_options as $extra) {
                    $optionExtra = unserialize($extra['b2b_xml_option_extra']);

                    if (array_key_exists('mkp_login', $optionExtra) && !empty($optionExtra['mkp_login'])) {
                        $params['IDENTIFIER'] = $optionExtra['mkp_login'];
                        unset($params['HASH']);
                        $params['HASH'] = $this->generateHASH($params, $optionExtra['mkp_password'],$paramsNoHash);
                        ksort($params);
                        break;
                    }
                }
            }

            $this->_debug($params);

            $service = $this->getApi();
            // @var $response Quadra_Be2bill_Model_Api_Response
            $response = $service->send($this->_currentOperationType, $params);
            $this->_debug($response);

            $this->setResponseIsSuccess($response->isSuccess());

            if (!$response->isSuccess()) {
                Mage::logException(new Exception("Response: " . print_r($response->getData(), 1)));
            } else {
                $payment->setIsPaid(1);
                $payment->setTransactionId($response->getTransactionId());
                $payment->setIsTransactionClosed(0);

                //ajout du detail de la transaction pour les remboursement
				$aResponseData = $response->getData();
				$aResponseData['miraklorderid'] = $miraklOrderId;
				$aResponseData['amount'] = Mage::helper('core')->currency($amount, true, false);
                $payment->setTransactionAdditionalInfo(
                        array(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS => $aResponseData),
                        $payment->getParentTransactionId()
                );
                $payment->setParentTransactionId($params['TRANSACTIONID']);
                $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, $order);

                // Update totals
                $updateTotals = array(
                    'amount_paid' => $order->getStore()->convertPrice($amount),
                    'base_amount_paid' => $amount,
                    'shipping_captured' => $order->getStore()->convertPrice($shippingAmount),
                    'base_shipping_captured' => $shippingAmount,
                );

                foreach ($updateTotals as $key => $newAmount) {
                    if (null !== $newAmount) {
                        $was = $payment->getDataUsingMethod($key);
                        $payment->setDataUsingMethod($key, $was + $newAmount);
                    }
                }

                $order->setTotalPaid(
                        $order->getTotalPaid() + $order->getStore()->convertPrice($amount)
                );
                $order->setBaseTotalPaid(
                        $order->getBaseTotalPaid() + $amount
                );

                $order->save();
            }
        }

        return $this;
    }

    /**
     * Mirakl
     * Refund marketplace products
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return  Mage_Payment_Model_Abstract
     */
    public function refundMkpProducts($payment, $amount, $miraklOrderId)
    {
        parent::refund($payment, $amount);
		
        $this->_currentOperationType = self::OPERATION_TYPE_REFUND;
        
        $paramsNoHash = array();
        $params = $this->getParameters($payment->getOrder(), $this->_currentOperationType, $amount, $miraklOrderId , $paramsNoHash);

        $addInfo = $payment->getAdditionalInformation();
        $extra_options = Mage::getModel('be2bill/api_methods')->getMiraklExtraOptions($addInfo['account_id']);

        if ($extra_options && is_array($extra_options)) {
            foreach ($extra_options as $extra) {
                $optionExtra = unserialize($extra['b2b_xml_option_extra']);

                if (array_key_exists('mkp_login', $optionExtra) && !empty($optionExtra['mkp_login'])) {
                    $params['IDENTIFIER'] = $optionExtra['mkp_login'];
                    unset($params['HASH']);
                    $params['HASH'] = $this->generateHASH($params, $optionExtra['mkp_password'],$paramsNoHash);
                    ksort($params);
                    break;
                }
            }
        }

        $this->_debug($params);

        $service = $this->getApi();
        $response = $service->send($this->_currentOperationType, $params);
        $this->_debug($response);

        $this->setResponseIsSuccess($response->isSuccess());

        if (!$response->isSuccess()) {
            if ($response->getData('execcode') == '2008') {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('be2bill')->__('Commande réglée en 3x. Veuillez effectuer le remboursement des échéances via l\'Extranet Be2bill'));
            }

            Mage::logException(new Exception("Response: " . print_r($response->getData(), 1)));
        } else {
            $order = $payment->getOrder();

            $payment->setTransactionId($response->getTransactionId());
            $payment->setIsTransactionClosed(1);
			
            //Ajout du detail de la transaction pour les rembrousement
			$aResponseData = $response->getData();
			$aResponseData['miraklorderid'] = $miraklOrderId;
			$aResponseData['amount'] = Mage::helper('core')->currency($amount, true, false);
            $payment->setTransactionAdditionalInfo(
                    array(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS => $aResponseData), $payment->getParentTransactionId()
            );

            $payment->setParentTransactionId($params['TRANSACTIONID']);
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND, $order);

            // Update totals
            $updateTotals = array(
                'amount_refunded' => $order->getStore()->convertPrice($amount),
                'base_amount_refunded' => $amount,
                'shipping_refunded' => 0,
                'base_shipping_refunded' => 0,
            );

            foreach ($updateTotals as $key => $newAmount) {
                if (null !== $newAmount) {
                    $was = $payment->getDataUsingMethod($key);
                    $payment->setDataUsingMethod($key, $was + $newAmount);
                }
            }

            $order->setBaseTotalRefunded(
                    $order->getBaseTotalRefunded() + $amount
            );
            $order->setTotalRefunded(
                    $order->getTotalRefunded() + $order->getStore()->convertPrice($amount)
            );
            $order->save();
        }

        return $this;
    }

}
