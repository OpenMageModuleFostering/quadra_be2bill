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
class Quadra_Be2bill_Block_Adminhtml_Merchandaccount extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    /**
     * Ajoute un bouton pour importer l'xml des comptes
     */
    public function __construct()
    {
        $this->_blockGroup = 'be2bill';
        $this->_controller = 'adminhtml_merchandaccount';
        $this->_headerText = Mage::helper('be2bill')->__('Gestion des comptes');
        $this->_addButtonLabel = Mage::helper('be2bill')->__('Créer un nouveau compte');

        $this->_addButton('add_new', array(
            'label' => Mage::helper('be2bill')->__('Import des paiements dynamiques'),
            'onclick' => "setLocation('{$this->getUrl('*/*/importXmltoDb')}')",
            'class' => 'add'
        ));

        $this->_addButton('transfert', array(
            'label' => Mage::helper('be2bill')->__('Transfert commandes V1 vers V2'),
            'onclick' => "setLocation('{$this->getUrl('*/transfert/edit')}')",
            'class' => 'add'
        ));
            
        parent::__construct();
    }

}
