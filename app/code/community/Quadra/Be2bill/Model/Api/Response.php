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
class Quadra_Be2bill_Model_Api_Response extends Varien_Object
{

    protected $_codeToMessages = array();

    /**
     * Overwrite data in the object.
     *
     * $key can be string or array.
     * If $key is string, the attribute value will be overwritten by $value
     *
     * If $key is an array, it will overwrite all the data in the object.
     *
     * @param string|array $key
     * @param mixed $value
     * @return Varien_Object
     */
    public function setData($key, $value = null)
    {
        $data = $key;
        if (is_array($key)) {
            $data = array();
            foreach ($key as $oldKey => $value) {
                $newKey = strtolower($oldKey);
                $data[$newKey] = $value;
            }
        }

        return parent::setData($data, $value);
    }

    public function isSuccess()
    {
        return $this->getExecCode() == "0000";
    }

    public function getExecCode()
    {
        return $this->getData('execcode');
    }

    public function getCodeToMessage($execode)
    {
        if (!count($this->_codeToMessages)) {
            $this->_codeToMessages = array(
                4001 => Mage::helper('be2bill')->__("Transaction refusée par votre banque"),
                4002 => Mage::helper('be2bill')->__("Fonds insuffisants ou plafond de paiement atteint"),
                4003 => Mage::helper('be2bill')->__("Transaction refusée par votre banque"),
                4004 => Mage::helper('be2bill')->__("Transaction refusée par votre banque"),
                4005 => Mage::helper('be2bill')->__("Transaction refusée par votre banque"),
                4006 => Mage::helper('be2bill')->__("Transaction refusée par votre banque"),
                4007 => Mage::helper('be2bill')->__("Transaction refusée par votre banque"),
                4008 => Mage::helper('be2bill')->__("Authentification 3DSecure échouée"),
                4009 => Mage::helper('be2bill')->__("Authentification 3DSecure expirée"),
                4010 => Mage::helper('be2bill')->__("Transaction refusée par votre banque"),
                4011 => Mage::helper('be2bill')->__("Transaction refusée par votre banque"),
                4012 => Mage::helper('be2bill')->__("Données de carte invalides"),
                4013 => Mage::helper('be2bill')->__("Transaction refusée par votre banque"),
                5001 => Mage::helper('be2bill')->__("Paiement échoué"),
                5002 => Mage::helper('be2bill')->__("Paiement échoué"),
                5003 => Mage::helper('be2bill')->__("Paiement échoué"),
                5004 => Mage::helper('be2bill')->__("Paiement échoué"),
                5005 => Mage::helper('be2bill')->__("Paiement échoué"),
                6001 => Mage::helper('be2bill')->__("Transaction refusée par le marchand"),
                6002 => Mage::helper('be2bill')->__("Transaction refusée par le marchand"),
                6003 => Mage::helper('be2bill')->__("Transaction refusée par le marchand"),
                6004 => Mage::helper('be2bill')->__("Transaction refusée par le marchand")
            );
        }

        $message = trim(Mage::getStoreConfig('be2bill/be2bill_errors/error_' . $execode));
        if (strlen($message)) {
            return $message;
        } elseif (isset($this->_codeToMessages[$execode])) {
            return $this->_codeToMessages[$execode];
        } else {
            return false;
        }
    }

    public function getMessage()
    {
        $execode = (int)$this->getExecCode();
        if (!$message = $this->getCodeToMessage($execode)) {
            $message = $this->getData('message');
        }

        return Mage::helper('be2bill')->__($message);
    }

    public function getIncrementId()
    {
        return $this->getData('orderid');
    }

    public function getTransactionId()
    {
        return $this->getData('transactionid');
    }

    public function getCcLast4()
    {
        return substr($this->getData('cardcode'), strlen($this->getData('cardcode')) - 4);
    }

    public function getCcType()
    {
        return $this->getData('cardtype');
    }

    public function getCcValidityDate()
    {
        return $this->getData('cardvaliditydate');
    }

    public function getCcExpMonth()
    {
        if ($this->getCcValidityDate() == "") {
            return "";
        }

        list($ccExpMonth, $ccExpYear) = explode("-", $this->getCcValidityDate());
        return $ccExpMonth;
    }

    public function getCcExpYear()
    {
        if ($this->getCcValidityDate() == "") {
            return "";
        }

        list($ccExpMonth, $ccExpYear) = explode("-", $this->getCcValidityDate());
        return $ccExpYear;
    }

    public function getCcOwner()
    {
        return $this->getData('cardfullname');
    }

    public function getCcStatusDescription()
    {
        return $this->getData('descriptor');
    }

    public function getCcNumberEnc()
    {
        return $this->getData('cardcode');
    }

    public function getOperationType()
    {
        return $this->getData('operationtype');
    }

    public function getAlias()
    {
        return $this->getData('alias');
    }

    public function getExtradata()
    {
        return $this->getData('extradata');
    }

}
