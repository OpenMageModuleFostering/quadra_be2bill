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
class Quadra_Be2bill_Block_Info_PaymentMethods extends Mage_Payment_Block_Info
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('be2bill/info/payment_methods.phtml');
    }

    /**
     * Nom du moyen de paiement dans le récapitulatif de paiement du tunnel
     * @return string
     */
    public function getBe2billMethod()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getPayment()->getData('additional_information')['be2bill_method_label'];
    }

    /**
     * Retourne des informations de paiement sur la vue de la commande dans l'admin
     * @return string
     */
    public function getType()
    {
        $html = '';
        if ($this->getInfo()->getAdditionalInformation('b2b_recurring') == 1) {
            $html .= $this->__('Profil récurrent') . ' , ';
        }

        $html .= $this->getInfo()->getAdditionalInformation('account') . ' : ' .
                $this->getInfo()->getAdditionalInformation('options') . ' -> ' .
                $this->getInfo()->getAdditionalInformation('be2bill_method_label');

        if ($this->getInfo()->getAdditionalInformation('ntimes') && $this->getInfo()->getAdditionalInformation('ntimes') != '') {
            $html.= ' / ' . Mage::helper('be2bill')->__('Paiement en %s fois', $this->getInfo()->getAdditionalInformation('ntimes'));
        }


        return $html;
    }

    public function getNtimesPaiement()
    {
        if ($this->getInfo()->getAdditionalInformation('ntimes') && $this->getInfo()->getAdditionalInformation('ntimes') != '') {
            $ntimes = (int)$this->getInfo()->getAdditionalInformation('ntimes');
            $createdAt = explode('-', $this->getInfo()->getOrder()->getCreatedAt());
            $amount = round($this->getInfo()->getOrder()->getBaseGrandTotal(), 2) * 100;

            $startDate = new DateTime();
            $startDate->setDate($createdAt[0], $createdAt[1], substr($createdAt[2], 0, 2));

            $schedules = Mage::helper('be2bill')->getSchedule($amount, $ntimes, $startDate);
            return $schedules;
        }
        return '';
    }

    public function toPdf()
    {
        $this->setTemplate('payment/info/pdf/ccsave.phtml');
        return $this->toHtml();
    }

    public function getTransactionId()
    {
        return $this->getInfo()->getLastTransId();
    }

    /**
     * Retrieve credit card type name
     *
     * @return string
     */
    public function getCcTypeName()
    {
        $types = Mage::getSingleton('payment/config')->getCcTypes();
        if (isset($types[$this->getInfo()->getCcType()])) {
            return $types[$this->getInfo()->getCcType()];
        }
        return $this->getInfo()->getCcType();
    }

    /**
     * Retrieve CC expiration month
     *
     * @return string
     */
    public function getCcExpMonth()
    {
        $month = $this->getInfo()->getCcExpMonth();
        /*if ($month < 10) {
            $month = '0' . $month;
        }*/
        return $month;
    }

    /**
     * Retrieve CC expiration date
     *
     * @return Zend_Date
     */
    public function getCcExpDate()
    {
        $date = Mage::app()->getLocale()->date(0);
        $date->setYear($this->getInfo()->getCcExpYear());
        $date->setMonth($this->getInfo()->getCcExpMonth());
        return $date;
    }

}
