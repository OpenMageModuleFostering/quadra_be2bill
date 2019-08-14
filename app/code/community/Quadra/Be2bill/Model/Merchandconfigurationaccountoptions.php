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
class Quadra_Be2bill_Model_Merchandconfigurationaccountoptions extends Mage_Core_Model_Abstract
{

    /**
     * Initialize
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('be2bill/merchandconfigurationaccountoptions');
    }

    /**
     * Complète le tableau de paramètres à envoyer à Be2bill
     *
     * @param string $codeOption
     * @param array $globalParams
     * @param array $addInfo
     * @return string
     */
    public function setOptionsParameters($codeOption, $globalParams, $addInfo)
    {
        //$id_account = $addInfo['account'];
        $cardCvv = (isset($addInfo['cvv_oneclick'])) ? $addInfo['cvv_oneclick'] : null;

        $option_params = Mage::getModel('be2bill/api_methods')->getOptionParameters($codeOption);
        foreach ($option_params as $params) {
            if ($params['b2b_xml_parameter_type'] == 'REQUIRED') {

                // todo all set all value possible
                switch ($params['b2b_xml_parameter_name']) {
                    case '3DSECURE' :
                        $globalParams['3DSECURE'] = 'YES';
                        break;
                    case '3DSECUREDISPLAYMODE':
                        $globalParams['3DSECUREDISPLAYMODE'] = 'MAIN';
                        break;
                    case 'AMOUNTS':
                        $globalParams['AMOUNTS'] = array();
                        $datesOfPayment = Mage::helper('be2bill')->getSchedule($globalParams['AMOUNT'], $addInfo['ntimes']);
                        foreach ($datesOfPayment as $date => $amount) {
                            $globalParams['AMOUNTS'][$date] = $amount;
                        }
                    case 'ALIAS':
                        if ($addInfo['alias'] != null) {
                            $globalParams['ALIAS'] = $addInfo['alias'];
                        }
                        break;
                    case 'ALIASMODE':
                        if ($addInfo['use_oneclick'] == 'yes') {
                            $globalParams['ALIASMODE'] = 'oneclick';
                        }
                        break;
                    case 'CLIENTIP':
                        $globalParams['CLIENTIP'] = $_SERVER['REMOTE_ADDR'];
                        break;
                    case 'CLIENTREFERRER':
                        $globalParams['CLIENTREFERRER'] = Mage::helper('core/http')->getRequestUri() != '' ? Mage::helper('core/http')->getRequestUri() : 'Unknow';
                        break;
                    case 'CLIENTUSERAGENT':
                        $globalParams['CLIENTUSERAGENT'] = Mage::helper('core/http')->getHttpUserAgent() != '' ? Mage::helper('core/http')->getHttpUserAgent() : 'Server';
                        break;
                    case 'CARDCVV':
                        if ($cardCvv != null) {
                            $globalParams['CARDCVV'] = $cardCvv;
                        }
                        break;
                    case 'CREATEALIAS':
                        $globalParams['CREATEALIAS'] = 'YES';
                    case 'DISPLAYCREATEALIAS':
                        $globalParams['DISPLAYCREATEALIAS'] = 'YES';
                        break;
                    case 'AGEVERIFICATION':
                        $globalParams['AGEVERIFICATION'] = 'YES';
                        break;
                }
            }
        }

        // reloop for unset specific params
        foreach ($option_params as $params) {
            if ($params['b2b_xml_parameter_type'] == 'ABSENT') {
                unset($globalParams[$params['b2b_xml_parameter_name']]);
            }
        }

        return $globalParams;
    }

}
