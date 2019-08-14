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
class Quadra_Be2bill_Block_Adminhtml_Merchandaccount_Grid_Renderer_Mode extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Renders grid column mode
     * @param   Varien_Object $row
     * @return string (label tranduit du mode pour le paiement)
     */
    public function render(Varien_Object $row)
    {
        $code = $row->getData($this->getColumn()->getIndex());
        $langIso = substr(Mage::app()->getLocale()->getLocaleCode(), 3, 4);

        $result = Mage::getModel('be2bill/api_methods')->getModeLangByCodeIso($code, $langIso);
        return $result[0]['mode_lang_name'];
    }

}
