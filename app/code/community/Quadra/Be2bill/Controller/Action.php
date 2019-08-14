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
class Quadra_Be2bill_Controller_Action extends Mage_Core_Controller_Front_Action
{

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = false;

    /**
     * Get checkout session model instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Lancer avant les controllers
     * @see Mage_Core_Controller_Front_Action::preDispatch()
     */
    public function preDispatch()
    {
        $action = $this->getRequest()->getActionName();
        $pattern = '/^(ipn)/i';

        if (preg_match($pattern, $action)) {
            if (!$this->_validateServer()) {
                $this->getResponse()->setBody(Mage::helper('be2bill')->__('Mauvaise IP : ') . Mage::helper('core/http')->getRemoteAddr());
                $this->setFlag('', 'no-dispatch', true);
            }
        }

        // Redirection vers le controller correspondant à la méhode de paiement utilisée
        $realOrderId = $this->getRequest()->getParam('ORDERID', false);
        if ($realOrderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($realOrderId);
            if ($order->getId()) {
                $method = $order->getPayment()->getMethod();
                if ($method != $this->getMethodInstance()->getCode()) {
                    $this->setFlag('', 'no-dispatch', true);
                    $exMethod = explode('_', $method);
                    $this->_forward($action, $exMethod[1], $exMethod[0], $this->getRequest()->getParams());
                    return;
                }
            }
        }
        return parent::preDispatch();
    }

    /**
     * Test l'ip du server
     *
     * @return boolean
     */
    protected function _validateServer()
    {
        /* @var $_helper Quadra_Be2bill_Helper_Data */
        $_helper = Mage::helper('be2bill');
        if (!$_helper->isBe2billServer()) {
            return false;
        }

        return true;
    }

    /**
     * Get singleton with be2bill method instance order transaction information
     *
     * @return Quadra_Be2bill_Model_Abstract
     */
    public function getMethodInstance()
    {
        throw new Exception("GET Method instance must be implemented!");
    }

    /**
     * When a customer cancel payment from be2bill.
     */
    public function cancelAction()
    {
        $session = $this->_getCheckoutSession();
        $session->setQuoteId($session->getBe2billStandardQuoteId(true));
        // cancel order
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->addStatusToHistory(
                                $order->getStatus(),
                                // keep order status/state cancel
                                Mage::helper('be2bill')->__('Annuler par le client')
                        )
                        ->cancel()
                        ->save();
            }
        }

        //need to save quote as active again if the user click on cancel payment from be2bill
        $this->_getQuote()->setIsActive(true)->save();
        //and then redirect to cart page
        $this->_redirect('checkout/cart');

        return $this;
    }

    /**
     * When a customer chooses Be2bill on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session = $this->_getCheckoutSession();
        $session->setBe2billStandardQuoteId($session->getQuoteId());

        /* @var $blockRedirect Quadra_Be2bill_Block_Method_Redirect */
        $blockRedirect = $this->getLayout()->createBlock('be2bill/method_redirect');

        //$blockRedirect->setMethodName('be2bill');
        $this->getResponse()->setBody($blockRedirect->toHtml());
        $session->unsQuoteId();
        $session->unsRedirectUrl();

        return $this;
    }

    /**
     * @Deprecated
     * Quand le client choisi le payment Paypal
     */
    public function paypalAction()
    {
        $session = $this->_getCheckoutSession();
        $session->setBe2billStandardQuoteId($session->getQuoteId());

        $session->unsQuoteId();
        $session->unsRedirectUrl();

        /* @var $methodInstance Quadra_Be2bill_Model_Abstract */
        $methodInstance = $this->getMethodInstance();

        try {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId()) {
                Mage::throwException("Commande introuvable!");
            }

            //@var $response Quadra_Be2bill_Model_Api_Response
            $response = $methodInstance->paypal($order->getPayment(), $order->getBaseTotalDue());

            if ($response['execcode'] == '0002') {
                $this->getResponse()->setBody(base64_decode($response['redirecthtml']));
            } else {
                $session->setErrorMessage($response->getMessage());
                Mage::logException(new Exception("Response: " . print_r($response->getData(), 1)));
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $session->setErrorMessage($e->getMessage());
            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    /**
     * Quand le client choisi un payment en mode directLink
     */
    public function directlinkAction()
    {
        $session = $this->_getCheckoutSession();
        $session->setBe2billStandardQuoteId($session->getQuoteId());

        $session->unsQuoteId();
        $session->unsRedirectUrl();

        /* @var $methodInstance Quadra_Be2bill_Model_Abstract */
        $methodInstance = $this->getMethodInstance();

        try {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId()) {
                Mage::throwException("Commande introuvable!");
            }

            //@var $response Quadra_Be2bill_Model_Api_Response
            $response = $methodInstance->directlink($order->getPayment(), $order->getBaseTotalDue());

            if ($response['execcode'] == '0002') {
                $this->getResponse()->setBody(base64_decode($response['redirecthtml']));
            } else {
                $session->setErrorMessage($response->getMessage());
                Mage::logException(new Exception("Response: " . print_r($response->getData(), 1)));
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $session->setErrorMessage($e->getMessage());
            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    /**
     * Si le client paye par Oneclick
     */
    public function oneclickAction()
    {
        $session = $this->_getCheckoutSession();
        $session->setBe2billStandardQuoteId($session->getQuoteId());

        $session->unsQuoteId();
        $session->unsRedirectUrl();

        /* @var $methodInstance Quadra_Be2bill_Model_Abstract */
        $methodInstance = $this->getMethodInstance();

        try {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

            if (!$order->getId()) {
                Mage::throwException("Commande introuvable!");
            }

            /* @var $response Quadra_Be2bill_Model_Api_Response */
            $methodInstance->oneclick($order->getPayment(), $order->getBaseTotalDue());

            $session->setQuoteId($session->getbe2billStandardQuoteId(true));

            /**
             * set the quote as inactive after back from be2bill
             */
            $this->_getQuote()->setIsActive(false)->save();
            $this->_getCheckoutSession()->unsQuoteId();
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } catch (Exception $e) {
            Mage::logException($e);
            $session->setErrorMessage($e->getMessage());
            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
        }
    }

    /**
     * Return checkout session object
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return checkout quote object
     *
     * @return Mage_Sale_Model_Quote
     */
    protected function _getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    /**
     * when be2bill returns
     * The order information at this point is in GET
     * variables.  However, you don't want to "process" the order until you
     * get validation from the IPN.
     */
    public function returnAction()
    {
        $session = $this->_getCheckoutSession();
        $infos = $this->getRequest()->getParams();
        //$infos = $this->getRequest()->getPost();
        $infos['class_method'] = __METHOD__;

        $this->getMethodInstance()->debugData($infos);
        /* @var $response Quadra_Be2bill_Model_Api_Response */
        $response = Mage::getModel('be2bill/api_response')->setData($infos);

        if (!$response->hasExeccode()) {
            $session->setErrorMessage($this->__('INTERDIT!'));
            $this->_redirect('checkout/onepage/failure', array('_secure' => true));
            return $this;
        }

        if ($response->isSuccess()) {
            if (($profileIds = Mage::getSingleton('checkout/session')->getLastRecurringProfileIds())) {
                if (is_array($profileIds)) {
                    //print_r($profileIds);

                    $collection = Mage::getModel('sales/recurring_profile')->getCollection()
                            ->addFieldToFilter('profile_id', array('in' => $profileIds));

                    foreach ($collection as $profile) {
                        $referenceId = $response->getAlias() . "-" . $profile->getId();
                        $profile->setAdditionalInfo(array('transaction_id' => $response->getTransactionId(), "alias" => $response->getAlias()));
                        $profile->setReferenceId($referenceId);
                        $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);
                        $profile->save();
                    }
                }
            }

            $session->setQuoteId($session->getBe2billStandardQuoteId(true));

            /**
             * set the quote as inactive after back from be2bill
             */
            $quote = Mage::getModel('sales/quote')->load($session->getQuoteId());

            $addInfo = $quote->getPayment()->getAdditionalInformation();

            $this->_getQuote()->setIsActive(false)->save();
            $this->_getCheckoutSession()->unsQuoteId();

            $account = $addInfo['account'];
            $mode = $addInfo['mode'];

            if ($mode == 'form-iframe' || $mode == 'directlink') {
                $this->_redirect('be2bill/template/success', array('_secure' => true));
            } else {
                $this->_redirect('checkout/onepage/success', array('_secure' => true));
            }
        } else {
            Mage::helper('be2bill')->reAddToCart($response->getIncrementId());
            if ($response->getExecCode() == "1002") {
                $session->addError($this->__("La date de validité de votre carte Bleue ne permet pas d’effectuer un paiement en plusieurs fois, nous vous invitons à changer de Carte Bleue ou bien à vous diriger vers le paiement en une fois."));
                $session->addError($this->__("Merci de votre compréhension."));
            } else {
                $session->addError($this->__('(Code retour :  %s) %s', $response->getExecCode(), $response->getMessage()));
            }
            Mage::logException(new Exception($response->getMessage(), $response->getExecCode()));
            $session->setCanRedirect(false);
            if ($this->_getQuote()->getPayment()->getAdditionalInformation('mode') == 'form-iframe') {
                $this->_redirect('checkout/cart');
            } else {
                $this->_redirect('be2bill/template/failure', array('_secure' => true));
            }
        }

        return $this;
    }

    /**
     * when be2bill returns via ipn
     * cannot have any output here
     * validate IPN data
     * if data is valid need to update the database that the user has
     */
    public function ipnAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->_redirect('');
            return $this;
        }

        $methodInstance = $this->getMethodInstance();
        $methodInstance->setResponse(Mage::getModel('be2bill/api_response')->setData($this->getRequest()->getPost()));

        try {
            $methodInstance->ipnPostSubmit();
        } catch (Exception $e) {
            $this->getResponse()->setBody($e->getMessage());
            Mage::logException($e);
            return $this;
        }
        $this->getResponse()->setBody("OK");
        return $this;
    }

}
