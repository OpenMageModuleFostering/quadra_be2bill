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
class Quadra_Be2bill_Block_Method_Iframe extends Mage_Payment_Block_Form
{

    /**
     * Whether the block should be eventually rendered
     *
     * @var bool
     */
    protected $_shouldRender = false;

    /**
     * Order object
     *
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_paymentMethodCode;

    /**
     * Current iframe block instance
     *
     * @var Mage_Payment_Block_Form
     */
    protected $_block;

    /**
     * Internal constructor
     * Set info template for payment step
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $paymentCode = $this->_getCheckout()
                ->getQuote()
                ->getPayment()
                ->getMethod();

        if ($paymentCode == $this->helper('be2bill')->getMethod()) {
            $this->_paymentMethodCode = $paymentCode;
            $this->setTemplate('be2bill/checkout/iframe.phtml');
        }
    }

    /**
     * Get current block instance
     *
     * @return Mage_Paypal_Block_Iframe
     */
    protected function _getBlock()
    {
        if (!$this->_block) {
            $this->_block = $this->getAction()
                    ->getLayout()
                    ->createBlock('be2bill/checkout_iframe');
        }

        return $this->_block;
    }

    /**
     * Get order object
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if (!$this->_order) {
            $incrementId = $this->_getCheckout()->getLastRealOrderId();
            $this->_order = Mage::getModel('sales/order')
                    ->loadByIncrementId($incrementId);
        }
        return $this->_order;
    }

    /**
     * Get frontend checkout session object
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Before rendering html, check if is block rendering needed
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _beforeToHtml()
    {
        if ($this->_getOrder()->getId() &&
                $this->_getOrder()->getQuoteId() == $this->_getCheckout()->getLastQuoteId() &&
                $this->_paymentMethodCode) {
            $this->_shouldRender = true;
        }

        if ($this->getGotoSection() || $this->getGotoSuccessPage()) {
            $this->_shouldRender = true;
        }

        return parent::_beforeToHtml();
    }

    /**
     * Check whether block is rendering after save payment
     *
     * @return bool
     */
    protected function _isAfterPaymentSave()
    {
        $quote = $this->_getCheckout()->getQuote();
        if ($quote->getPayment()->getMethod() == $this->_paymentMethodCode &&
                $quote->getIsActive() &&
                $this->getTemplate() &&
                $this->getRequest()->getActionName() == 'savePayment') {
            return true;
        }

        return false;
    }

    /**
     * Get iframe action URL
     *
     * @return string
     */
    public function getFrameActionUrl()
    {
        return Mage::getUrl('be2bill/payments/redirect', array('_secure' => true));
    }

    /**
     * Get secure token
     *
     * @return string
     */
    public function getSecureToken()
    {
        return $this->_getBlock()->getSecureToken();
    }

    /**
     * Get secure token ID
     *
     * @return string
     */
    public function getSecureTokenId()
    {
        return $this->_getBlock()->getSecureTokenId();
    }

    /**
     * Get payflow transaction URL
     *
     * @return string
     */
    public function getTransactionUrl()
    {
        return $this->_getBlock()->getTransactionUrl();
    }

    /**
     * Check sandbox mode
     *
     * @return bool
     */
    public function isTestMode()
    {
        return $this->_getBlock()->isTestMode();
    }

}
