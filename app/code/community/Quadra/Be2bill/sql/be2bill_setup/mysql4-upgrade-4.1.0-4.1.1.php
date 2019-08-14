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
$installer = $this;


$statusTable = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');
$statusLabelTable = $installer->getTable('sales/order_status_label');

$installer->startSetup();

$currentVersion = Mage::getVersion();
$exist = Mage::getModel('sales/order_status')->getCollection()->addFieldToFilter('status', 'pending_be2bill')->getSize();

if (version_compare($currentVersion, '1.4.2') == 1 && $exist < 1) {
    $status = 'pending_be2bill';
    $label = 'Pending Capture Be2bill';
    $code = "pending_payment";

    //Insert new Status in DB
    $data[0] = array(
        'status' => $status,
        'label' => $label,
    );

    $installer->getConnection()->insertArray($statusTable, array('status', 'label'), $data);
    //Insert relation between state and status
    $data[0] = array(
        'status' => $status,
        'state' => $code,
        'is_default' => 0,
    );

    $installer->getConnection()->insertArray(
            $statusStateTable, array('status', 'state', 'is_default'), $data
    );
}

$installer->endSetup();
