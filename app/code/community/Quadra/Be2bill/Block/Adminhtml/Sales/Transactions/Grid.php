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
class Quadra_Be2bill_Block_Adminhtml_Sales_Transactions_Grid extends Mage_Adminhtml_Block_Sales_Transactions_Grid
{

    /**
     * Add columns to grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
       $this->addColumn('transaction_id', array(
            'header'    => Mage::helper('sales')->__('ID #'),
            'index'     => 'transaction_id',
            'type'      => 'number'
        ));

        $this->addColumn('increment_id', array(
            'header'    => Mage::helper('sales')->__('Order ID'),
            'index'     => 'increment_id',
            'type'      => 'text'
        ));

        $this->addColumn('txn_id', array(
            'header'    => Mage::helper('sales')->__('Transaction ID'),
            'index'     => 'txn_id',
            'type'      => 'text'
        ));

        $this->addColumn('parent_txn_id', array(
            'header'    => Mage::helper('sales')->__('Parent Transaction ID'),
            'index'     => 'parent_txn_id',
            'type'      => 'text'
        ));

        $this->addColumn('method', array(
            'header'    => Mage::helper('sales')->__('Payment Method Name'),
            'index'     => 'method',
            'type'      => 'options',
            'options'       => Mage::helper('payment')->getPaymentMethodList(true),
            'option_groups' => Mage::helper('payment')->getPaymentMethodList(true, true, true),
        ));

        $this->addColumn('txn_type', array(
            'header'    => Mage::helper('sales')->__('Transaction Type'),
            'index'     => 'txn_type',
            'type'      => 'options',
            'options'   => Mage::getSingleton('sales/order_payment_transaction')->getTransactionTypes()
        ));
		
        $this->addColumn('txn_amount', array(
            'header'    => Mage::helper('be2bill')->__('Transaction Amount'),
            'index'     => 'txn_amount',
            'renderer'	=> 'Quadra_Be2bill_Block_Adminhtml_Sales_Transactions_Renderer_Txnamount',
            'sortable'	=> false,
            'filter'	=> false,
            'align' 	=> 'right',
        ));

        $this->addColumn('is_closed', array(
            'header'    => Mage::helper('sales')->__('Is Closed'),
            'index'     => 'is_closed',
            'width'     => 1,
            'type'      => 'options',
            'align'     => 'center',
            'options'   => array(
                1  => Mage::helper('sales')->__('Yes'),
                0  => Mage::helper('sales')->__('No'),
            )
        ));

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('sales')->__('Created At'),
            'index'     => 'created_at',
            'width'     => 1,
            'type'      => 'datetime',
            'align'     => 'center',
            'default'   => $this->__('N/A'),
            'html_decorators' => array('nobr')
        ));
		
        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }
}
