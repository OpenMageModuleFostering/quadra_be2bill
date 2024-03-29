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
$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE `{$this->getTable('b2b_merchand_configuration_account')}` ADD `cancel_capture_auto` TINYINT NULL DEFAULT NULL ;
    ALTER TABLE `{$this->getTable('b2b_merchand_configuration_account')}` ADD `order_canceled_limited_time` INT NULL DEFAULT '0';
");

$installer->endSetup();
