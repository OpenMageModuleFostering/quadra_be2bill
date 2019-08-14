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
class Quadra_Be2bill_Helper_Ip extends Mage_Core_Helper_Http
{

    const XML_NODE_BE2BILL_REMOTE_ADDR_HEADERS = 'global/be2bill/remote_addr_headers';

    /**
     * Retrieve Remote Addresses Additional check Headers
     *
     * @return array
     */
    public function getBe2billRemoteAddrHeaders()
    {
        $headers = array();
        $element = Mage::getConfig()->getNode(self::XML_NODE_BE2BILL_REMOTE_ADDR_HEADERS);
        if ($element instanceof Mage_Core_Model_Config_Element) {
            foreach ($element->children() as $node) {
                $headers[] = (string)$node;
            }
        }

        return $headers;
    }

    /**
     * Verifie l'ip si Ok
     * @param string $ip
     * @param string $mask
     * @return boolean
     */
    public function checkIfRemoteIpIsInRange($ip, $mask)
    {
        $this->checkMask($mask);
        //TODO check if IP format is valid

        $allow = false;

        $headers = $this->getBe2billRemoteAddrHeaders();

        $firstIp2Long = $this->getFirtAddress($ip, $mask, true);
        $lastIp2Long = $this->getLastAddress($ip, $mask, true);

        foreach ($headers as $var) {
            if ($this->_getRequest()->getServer($var, false)) {
                $remoteAdr2Long = ip2long($_SERVER[$var]);
                if (($remoteAdr2Long >= $firstIp2Long) && ($remoteAdr2Long <= $lastIp2Long)) {
                    $allow = true;
                    break;
                }
            }
        }

        return $allow;
    }

    /**
     *
     * @param string $mask
     */
    protected function checkMask($mask)
    {
        if (($mask < 1) || ($mask > 32)) {
            Mage::throwException("Mask {$mask} is not valid!");
        }
    }

    /**
     *
     * @param string $ip
     * @param string $mask
     * @return string
     */
    protected function getRoute($ip, $mask)
    {
        $route = ip2long($ip) & ip2long($this->_getMaskString($mask)); // Ajoute l'IP et le masque en binaire
        return long2ip($route); // Convertit l'adresse inetaddr en IP
    }

    /**
     *
     * @param string $ip
     * @param string $mask
     * @param string $returnIpToLong
     * @return string
     */
    protected function getFirtAddress($ip, $mask, $returnIpToLong = false)
    {
        $offset = 0;
        $firstIp = "N/A";
        if ($mask != 32) {
            $offset = 1;
        }

        if ($mask == 31) {
            return $firstIp;
        }

        $firstIp = ip2long($this->getRoute($ip, $mask)) + $offset;
        $firstIp = long2ip($firstIp);

        return $returnIpToLong ? ip2long($firstIp) : $firstIp;
    }

    /**
     *
     * @param string $ip
     * @param string $mask
     * @param string $returnIpToLong
     * @return string
     */
    protected function getLastAddress($ip, $mask, $returnIpToLong = false)
    {
        $offset = -1;
        $lastIp = "N/A";

        if ($mask != 32) {
            $offset = 0;
        }

        if ($mask == 31) {
            return $lastIp;
        }

        $lastIp = ip2long($this->getRoute($ip, $mask)) + $this->getCountHost($mask) + $offset;
        $lastIp = long2ip($lastIp);

        return $returnIpToLong ? ip2long($lastIp) : $lastIp;
    }

    /**
     *
     * @param string $mask
     * @return number
     */
    protected function getCountHost($mask)
    {
        if ($mask == 32) {
            $cntHost = 1;
        } else {
            $cntHost = pow(2, 32 - $mask) - 2;
        }

        return $cntHost;
    }

    /**
     *
     * @param string $mask
     * @return string
     */
    protected function _getMaskString($mask)
    {
        $maskString = "255.255.255.255";

        switch ($mask) {
            case 1:
                $maskString = "128.0.0.0";
                break;
            case 2:
                $maskString = "192.0.0.0";
                break;
            case 3:
                $maskString = "224.0.0.0";
                break;
            case 4:
                $maskString = "240.0.0.0";
                break;
            case 5:
                $maskString = "248.0.0.0";
                break;
            case 6:
                $maskString = "252.0.0.0";
                break;
            case 7:
                $maskString = "254.0.0.0";
                break;
            case 8:
                $maskString = "255.0.0.0";
                break;
            case 9:
                $maskString = "255.128.0.0";
                break;
            case 10:
                $maskString = "255.192.0.0";
                break;
            case 11:
                $maskString = "255.224.0.0";
                break;
            case 12:
                $maskString = "255.240.0.0";
                break;
            case 13:
                $maskString = "255.248.0.0";
                break;
            case 14:
                $maskString = "255.252.0.0";
                break;
            case 15:
                $maskString = "255.254.0.0";
                break;
            case 16:
                $maskString = "255.255.0.0";
                break;
            case 17:
                $maskString = "255.255.128.0";
                break;
            case 18:
                $maskString = "255.255.192.0";
                break;
            case 19:
                $maskString = "255.255.224.0";
                break;
            case 20:
                $maskString = "255.255.240.0";
                break;
            case 21:
                $maskString = "255.255.248.0";
                break;
            case 22:
                $maskString = "255.255.252.0";
                break;
            case 23:
                $maskString = "255.255.254.0";
                break;
            case 24:
                $maskString = "255.255.255.0";
                break;
            case 25:
                $maskString = "255.255.255.128";
                break;
            case 26:
                $maskString = "255.255.255.192";
                break;
            case 27:
                $maskString = "255.255.255.224";
                break;
            case 28:
                $maskString = "255.255.255.240";
                break;
            case 29:
                $maskString = "255.255.255.248";
                break;
            case 30:
                $maskString = "255.255.255.252";
                break;
            case 31:
                $maskString = "255.255.255.254";
                break;
            case 32:
                $maskString = "255.255.255.255";
                break;
            default :
                $maskString = "255.255.255.255";
                break;
        }

        return $maskString;
    }

}
