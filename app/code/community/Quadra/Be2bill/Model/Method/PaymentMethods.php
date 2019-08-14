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
class Quadra_Be2bill_Model_Method_PaymentMethods extends Quadra_Be2bill_Model_Method_Abstract implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{

    protected $_code = 'be2bill';

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

    /**
     * Whether can manage recurring profiles
     *
     * @return bool
     */
    public function canManageRecurringProfiles()
    {
        return parent::canManageRecurringProfiles() && Mage::getSingleton('customer/session')->isLoggedIn();
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        //if (!in_array($currencyCode, $this->_allowCurrencyCode))
        //    return false;
        return true;
    }

    public function submitRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile, Mage_Payment_Model_Info $paymentInfo)
    {
        if ($this->isOneClickMode()) {
            $customer = Mage::getSingleton('customer/customer')->load($profile->getCustomerId());
            if ($customer->getId()) {
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);
                $referenceId = $paymentInfo->getAdditionalInformation('alias') . "-" . $profile->getId();
                $profile->setAdditionalInfo(array("alias" => $customer->getBe2billAlias()));
                $profile->setReferenceId($referenceId);
            }
        }
        return $this;
    }

    /**
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return $this;
    }

    /**
     * @param unknown_type $referenceId
     * @param Varien_Object $result
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
        return $this;
    }

    /**
     *
     */
    public function canGetRecurringProfileDetails()
    {
        return false;
    }

    /**
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        // TODO: Auto-generated method stub
    }

    /**
     * @param Mage_Payment_Model_Recurring_Profile $profile
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return $this;
    }

}
