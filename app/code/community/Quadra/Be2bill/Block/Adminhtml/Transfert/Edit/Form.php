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

/**
 * Quadra-Informatique
 * @category    Quadra
 * @package     Quadra_Be2bill
 */
class Quadra_Be2bill_Block_Adminhtml_Transfert_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    /**
     * Prepare form before rendering HTML
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save'),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));
        $form->setUseContainer(true);

        $fieldset = $form->addFieldset('edit_form', array('legend' => Mage::helper('be2bill')->__('Transfert')));

        $fieldset->addField('cb_visa', 'text', array(
            'label' => Mage::helper('be2bill')->__('CB Visa'),
            'title' => Mage::helper('be2bill')->__('CB Visa'),
            'class' => 'input-text',
            'required' => false,
            'name' => 'cb_visa',
            'after_element_html' => '<br /><small>' . Mage::helper('be2bill')->__('Id du compte') . '</small>',
            'tabindex' => 1,
        ));

        $fieldset->addField('amex', 'text', array(
            'label' => Mage::helper('be2bill')->__('Amex'),
            'title' => Mage::helper('be2bill')->__('Amex'),
            'class' => 'input-text',
            'required' => false,
            'name' => 'amex',
            'after_element_html' => '<br /><small>' . Mage::helper('be2bill')->__('Id du compte') . '</small>',
            'tabindex' => 1,
        ));

        $fieldset->addField('paypal', 'text', array(
            'label' => Mage::helper('be2bill')->__('Paypal'),
            'title' => Mage::helper('be2bill')->__('Paypal'),
            'class' => 'input-text',
            'required' => false,
            'name' => 'paypal',
            'after_element_html' => '<br /><small>' . Mage::helper('be2bill')->__('Id du compte') . '</small>',
            'tabindex' => 1,
        ));

        $this->setForm($form);
        return parent::_prepareForm();
    }

}
