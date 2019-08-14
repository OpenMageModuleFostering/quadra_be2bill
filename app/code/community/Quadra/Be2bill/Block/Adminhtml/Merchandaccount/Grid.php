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
class Quadra_Be2bill_Block_Adminhtml_Merchandaccount_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    protected $_defaultCountry;

    public function __construct()
    {
        parent::__construct();

        $this->_defaultCountry = substr(Mage::app()->getLocale()->getLocaleCode(), 3, 4);

        $this->setId('merchandaccountGrid');
        $this->setDefaultSort('id_b2b_merchand_configuration_account');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Prepare grid collection object
     *
     * @return Quadra_Be2bill_Block_Adminhtml_Merchandaccount_Grid
     */
    protected function _prepareCollection()
    {

        $collection = Mage::getModel('be2bill/merchandconfigurationaccount')->getCollection();

        $collection->getSelect()->joinLeft(
                array('a' => Mage::getSingleton('core/resource')->getTableName('b2b_xml_modes_lang')),
                'main_table.b2b_xml_mode_code = a.b2b_xml_mode_code AND ( a.lang_iso = "' . $this->_defaultCountry . '" ) ',
                array('mode_lang_name')
        );

        /*
        $collection->getSelect()->joinLeft(
                array('b' => Mage::getSingleton('core/resource')->getTableName('b2b_xml_account_types')),
                'b.b2b_xml_account_type_code = main_table.b2b_xml_account_type_code ',
                null
        );

        $collection->getSelect()->joinLeft(
                array('c' => Mage::getSingleton('core/resource')->getTableName('b2b_xml_account_type_lang')),
                'b.b2b_xml_account_type_code = c.b2b_xml_account_type_code and ( c.lang_iso = "' . $this->_defaultCountry . '" ) ',
                array('account_type_lang_name')
        );
        */

        $collection->getSelect()->joinLeft(
                array('e' => Mage::getSingleton('core/resource')->getTableName('core_store')),
                'main_table.core_store_id = e.store_id ',
                array('*')
        );

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid Columns object
     * @return $this
     */
    protected function _prepareColumns()
    {
        $helper = Mage::helper('be2bill');

        $this->addColumn('store_name', array(
            'header' => Mage::helper('be2bill')->__('Magasin'),
            'index' => 'core_store_id',
            'type' => 'store',
            'store_all' => true,
            'store_view' => true,
            'sortable' => true,
        ));


        $this->addColumn('account_type', array(
            'header' => $helper->__('Type de compte'),
            'index' => 'b2b_xml_account_type_code'
        ));


        $this->addColumn('account_name', array(
            'header' => $helper->__('Libellé'),
            'index' => 'configuration_account_name'
        ));

        $this->addColumn('mode', array(
            'header' => $helper->__('Mode'),
            'index' => 'b2b_xml_mode_code',
            'filter' => false,
            'sortable' => false,
            'renderer' => 'Quadra_Be2bill_Block_Adminhtml_Merchandaccount_Grid_Renderer_Mode'
        ));

        $this->addColumn('currency_iso', array(
            'header' => $helper->__('Devise'),
            'index' => 'currency_iso'
        ));

        $this->addColumn('active', array(
            'header' => $helper->__('Statut'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'active',
            'type' => 'options',
            'options' => array(
                1 => $helper->__('Activé'),
                0 => $helper->__('Désactivé')
            ),
        ));

        return parent::_prepareColumns();
    }

    /**
     * Ajoute dans la liste des actions de la grille
     * les actions : activer / désactiver / supprimer
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id_b2b_merchand_configuration_account');
        $this->getMassactionBlock()->setFormFieldName('merchandaccount');

        $this->getMassactionBlock()
                ->addItem('enabled', array(
                    'label' => Mage::helper('be2bill')->__('Activé'),
                    'url' => $this->getUrl('*/*/massEnable'),
                    'confirm' => Mage::helper('be2bill')->__('Êtes-vous sûr ?')
                ))
                ->addItem('disabled', array(
                    'label' => Mage::helper('be2bill')->__('Désactivé'),
                    'url' => $this->getUrl('*/*/massDisable'),
                    'confirm' => Mage::helper('be2bill')->__('Êtes-vous sûr ?')
                ))
                ->addItem('delete', array(
                    'label' => Mage::helper('be2bill')->__('Suppression'),
                    'url' => $this->getUrl('*/*/massDelete'),
                    'confirm' => Mage::helper('be2bill')->__('Êtes-vous sûr ?')
        ));

        return $this;
    }

    /**
     * @return Url de la grille
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * @return Url Edit
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}
