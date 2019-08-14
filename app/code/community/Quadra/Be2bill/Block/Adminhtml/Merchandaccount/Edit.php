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
class Quadra_Be2bill_Block_Adminhtml_Merchandaccount_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'be2bill';
        $this->_controller = 'adminhtml_merchandaccount';
        $this->_mode = 'edit';

        if (!Mage::registry('current_configuration_account')->getId() > 0) {
            $this->_removeButton('remove');
        }
		
		$this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);
		$this->_formScripts[] = " function saveAndContinueEdit(){
            editForm.submit($('edit_form').action+'back/edit/');
        }";

 		$this->_removeButton('reset');
    }

    /**
     *
     * @see Mage_Adminhtml_Block_Widget_Container::getHeaderText()
     * @return le titre de l'entete de la page
     */
    public function getHeaderText()
    {
        if (Mage::registry('current_configuration_account') && Mage::registry('current_configuration_account')->getId()) {
            return Mage::helper('be2bill')->__('Edition du compte');
        } else {
            return Mage::helper('be2bill')->__('Nouveau compte');
        }
    }

}
