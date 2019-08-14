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
	SET FOREIGN_KEY_CHECKS=0;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_modes_lang')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_modes_lang')}` (
	`b2b_xml_mode_code` VARCHAR(255) NOT NULL,
	`lang_iso` VARCHAR(2) NULL,
	`mode_lang_name` VARCHAR(255) NULL,
	INDEX `idx_b2b_xml_modes_lang` (`b2b_xml_mode_code` ASC))
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_operation_lang')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_operation_lang')}` (
	`b2b_xml_operation_code` VARCHAR(255) NOT NULL,
	`lang_iso` VARCHAR(2) NULL,
	`operation_lang_name` VARCHAR(255) NULL,
	INDEX `idx_b2b_xml_operation_lang` (`b2b_xml_operation_code` ASC))
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_options')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_options')}` (
	`b2b_xml_option_code` VARCHAR(255) NOT NULL,
	`b2b_xml_parameter_name` VARCHAR(255) NOT NULL,
	`b2b_xml_parameter_type` ENUM('REQUIRED', 'ABSENT') NULL,
	`b2b_xml_parameter_value` ENUM('YES', 'NO', 'REQUIRED') NULL,
	INDEX `idx_b2b_xml_options` (`b2b_xml_option_code` ASC))
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_options_lang')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_options_lang')}` (
	`b2b_xml_option_code` VARCHAR(255) NOT NULL,
	`lang_iso` VARCHAR(2) NULL,
	`option_lang_name` VARCHAR(255) NULL,
	INDEX `idx_b2b_xml_options_lang` (`b2b_xml_option_code` ASC),
	CONSTRAINT `fk_options_lang_options`
	FOREIGN KEY (`b2b_xml_option_code`)
	REFERENCES `{$this->getTable('b2b_xml_options')}` (`b2b_xml_option_code`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_account_types')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_account_types')}` (
	`b2b_xml_account_type_code` VARCHAR(255) NULL,
	`b2b_xml_account_type_logo` VARCHAR(255) NULL,
	PRIMARY KEY (`b2b_xml_account_type_code`))
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_account_type_lang')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_account_type_lang')}` (
	`b2b_xml_account_type_code` VARCHAR(255) NOT NULL,
	`lang_iso` VARCHAR(2) NULL,
	`account_type_lang_name` VARCHAR(255) NULL,
	INDEX `idx_b2b_xml_account_type_lang` (`b2b_xml_account_type_code` ASC),
	CONSTRAINT `fk_account_type_lang_account_type_code`
	FOREIGN KEY (`b2b_xml_account_type_code`)
	REFERENCES `{$this->getTable('b2b_xml_account_types')}` (`b2b_xml_account_type_code`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_account_type_currency')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_account_type_currency')}` (
	`b2b_xml_account_type_code` VARCHAR(255) NOT NULL,
	`currency_iso` VARCHAR(3) NOT NULL,
	INDEX `idx_b2b_xml_account_type_currency` (`b2b_xml_account_type_code` ASC),
	CONSTRAINT `fk_account_type_currency_account_types`
	FOREIGN KEY (`b2b_xml_account_type_code`)
	REFERENCES `{$this->getTable('b2b_xml_account_types')}` (`b2b_xml_account_type_code`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_account_type_parameter_set')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_account_type_parameter_set')}` (
	`id_b2b_xml_account_type_parameter_set` INT NOT NULL AUTO_INCREMENT,
	`b2b_xml_account_type_parameter_set_version` VARCHAR(255) NULL,
	`b2b_xml_account_type_parameter_set_logo` VARCHAR(255) NULL,
	`b2b_xml_account_type_code` VARCHAR(255) NOT NULL,
	PRIMARY KEY (`id_b2b_xml_account_type_parameter_set`),
	CONSTRAINT `fk_account_type_parameter_set_account_types`
	FOREIGN KEY (`b2b_xml_account_type_code`)
	REFERENCES `{$this->getTable('b2b_xml_account_types')}` (`b2b_xml_account_type_code`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_account_type_parameter_set_countries')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_account_type_parameter_set_countries')}` (
	`id_b2b_xml_account_type_parameter_set` INT NOT NULL,
	`country_iso` VARCHAR(2) NOT NULL,
	INDEX `idx_b2b_xml_account_type_countries` (`id_b2b_xml_account_type_parameter_set` ASC),
	CONSTRAINT `fk_account_type_parameter_set_countries_acnt_type_parameter_set`
	FOREIGN KEY (`id_b2b_xml_account_type_parameter_set`)
	REFERENCES `{$this->getTable('b2b_xml_account_type_parameter_set')}` (`id_b2b_xml_account_type_parameter_set`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_account_type_parameter_set_mode')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_account_type_parameter_set_mode')}` (
	`id_b2b_xml_account_type_parameter_set` INT NOT NULL,
	`b2b_xml_mode_code` VARCHAR(255) NOT NULL,
	INDEX `idx_b2b_xml_account_type_parameter_set_mode` (`id_b2b_xml_account_type_parameter_set` ASC),
	CONSTRAINT `fk_account_type_parameter_set_mode_modes_lang`
	FOREIGN KEY (`b2b_xml_mode_code`)
	REFERENCES `{$this->getTable('b2b_xml_modes_lang')}` (`b2b_xml_mode_code`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION,
	CONSTRAINT `fk_account_type_parameter_set_mode_account_type_parameter_set`
	FOREIGN KEY (`id_b2b_xml_account_type_parameter_set`)
	REFERENCES `{$this->getTable('b2b_xml_account_type_parameter_set')}` (`id_b2b_xml_account_type_parameter_set`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_account_type_parameter_set_operation')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_account_type_parameter_set_operation')}` (
	`id_b2b_xml_account_type_parameter_set` INT NOT NULL,
	`b2b_xml_operation_code` VARCHAR(255) NOT NULL,
	INDEX `fk_b2b_xml_account_type_parameter_set_operation_b2b_xml_acc_idx` (`id_b2b_xml_account_type_parameter_set` ASC),
	CONSTRAINT `fk_account_type_parameter_set_operation_accnt_tpe_param_set`
	FOREIGN KEY (`id_b2b_xml_account_type_parameter_set`)
	REFERENCES `{$this->getTable('b2b_xml_account_type_parameter_set')}` (`id_b2b_xml_account_type_parameter_set`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION,
	CONSTRAINT `fk_account_type_parameter_set_operation_operation_lang`
	FOREIGN KEY (`b2b_xml_operation_code`)
	REFERENCES `{$this->getTable('b2b_xml_operation_lang')}` (`b2b_xml_operation_code`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_account_type_parameter_set_parameters')}`;
	CREATE TABLE `{$this->getTable('b2b_xml_account_type_parameter_set_parameters')}` (
	`id_b2b_xml_account_type_parameter_set` INT NOT NULL,
	`b2b_xml_parameter_name` VARCHAR(255) NOT NULL,
	`b2b_xml_parameter_type` ENUM('REQUIRED', 'ABSENT') NULL,
	`b2b_xml_parameter_value` ENUM('YES', 'NO', 'REQUIRED') NULL,
	INDEX `idx_b2b_xml_account_type_parameter_set_parameters` (`id_b2b_xml_account_type_parameter_set` ASC),
	CONSTRAINT `fk_account_type_parameter_set_parameters_accnt_tpe_param_set`
	FOREIGN KEY (`id_b2b_xml_account_type_parameter_set`)
	REFERENCES `{$this->getTable('b2b_xml_account_type_parameter_set')}` (`id_b2b_xml_account_type_parameter_set`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_xml_account_type_parameter_set_options')}`;
	CREATE TABLE IF NOT EXISTS `{$this->getTable('b2b_xml_account_type_parameter_set_options')}` (
	`id_b2b_xml_account_type_parameter_set` INT NOT NULL,
	`b2b_xml_option` VARCHAR(255) NOT NULL,
	INDEX `idx_b2b_xml_account_type_parameter_set_options` (`id_b2b_xml_account_type_parameter_set` ASC),
	CONSTRAINT `fk_account_type_parameter_set_options_account_type_parameter_set`
	FOREIGN KEY (`id_b2b_xml_account_type_parameter_set`)
	REFERENCES `{$this->getTable('b2b_xml_account_type_parameter_set')}` (`id_b2b_xml_account_type_parameter_set`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION,
	CONSTRAINT `fk_account_type_parameter_set_options_options`
	FOREIGN KEY (`b2b_xml_option`)
	REFERENCES `{$this->getTable('b2b_xml_options')}` (`b2b_xml_option_code`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_merchand_configuration_account')}`;
	CREATE TABLE `{$this->getTable('b2b_merchand_configuration_account')}` (
	`id_b2b_merchand_configuration_account` INT NOT NULL AUTO_INCREMENT,
	`b2b_xml_account_type_code` VARCHAR(255) NOT NULL,
	`b2b_xml_mode_code` VARCHAR(255) NOT NULL,
	`currency_iso` VARCHAR(3) NOT NULL,
	`login` VARCHAR(255) NULL,
	`password` VARCHAR(255) NULL,
	`logo_url` VARCHAR(255) NULL,
	`configuration_account_name` VARCHAR(255) NULL DEFAULT NULL,
	`active` TINYINT(1) NULL,
	`core_store_id` SMALLINT(5) unsigned NOT NULL,
	PRIMARY KEY (`id_b2b_merchand_configuration_account`),
	CONSTRAINT `fk_merchand_configuration_account_account_type_code`
	FOREIGN KEY (`b2b_xml_account_type_code`)
	REFERENCES `{$this->getTable('b2b_xml_account_types')}` (`b2b_xml_account_type_code`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION,
	CONSTRAINT `fk_merchand_configuration_account_modes_lang`
	FOREIGN KEY (`b2b_xml_mode_code`)
	REFERENCES `{$this->getTable('b2b_xml_modes_lang')}` (`b2b_xml_mode_code`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION,
	CONSTRAINT `fk_merchand_configuration_account_core_store`
	FOREIGN KEY (`core_store_id`)
	REFERENCES `{$this->getTable('core_store')}` (`store_id`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_merchand_configuration_account_options')}`;
	CREATE TABLE `{$this->getTable('b2b_merchand_configuration_account_options')}` (
	`id_b2b_merchand_configuration_account_options` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_b2b_merchand_configuration_account` INT NOT NULL,
	`b2b_xml_option` VARCHAR(255) NOT NULL,
	`min_amount` decimal(13,6) NULL DEFAULT NULL,
	`max_amount` decimal(13,6) NULL DEFAULT NULL,
	`b2b_xml_option_extra` VARCHAR(255) NOT NULL,
	`active` TINYINT(1) NOT NULL,
	PRIMARY KEY (`id_b2b_merchand_configuration_account_options`),
	CONSTRAINT `fk_merchand_configuration_account_options_merchand_conf_accnt`
	FOREIGN KEY (`id_b2b_merchand_configuration_account`)
	REFERENCES `{$this->getTable('b2b_merchand_configuration_account')}` (`id_b2b_merchand_configuration_account`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_merchand_configuration_account_countries')}`;
	CREATE TABLE `{$this->getTable('b2b_merchand_configuration_account_countries')}` (
	`id_b2b_merchand_configuration_account_countries` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_b2b_merchand_configuration_account` INT NOT NULL,
	`country_iso` VARCHAR(2) NULL,
	PRIMARY KEY (`id_b2b_merchand_configuration_account_countries`),
	CONSTRAINT `fk_merchand_configuration_account_countries_merchand_conf_accnt`
	FOREIGN KEY (`id_b2b_merchand_configuration_account`)
	REFERENCES `{$this->getTable('b2b_merchand_configuration_account')}` (`id_b2b_merchand_configuration_account`)
	ON DELETE NO ACTION
	ON UPDATE NO ACTION)
	ENGINE = InnoDB;

	DROP TABLE IF EXISTS `{$this->getTable('b2b_alias')}`;
	CREATE TABLE `{$this->getTable('b2b_alias')}` (
	`id_b2b_alias` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`id_customer` INT(10) UNSIGNED NOT NULL,
	`id_merchand_account` INT(10) NOT NULL,
	`alias` VARCHAR(255) NOT NULL,
	`card_type` VARCHAR(255) NOT NULL,
	`card_number` VARCHAR(255) NOT NULL,
	`date_add` DATETIME NOT NULL,
	`date_end` DATETIME NOT NULL,
	PRIMARY KEY (`id_b2b_alias`),
	CONSTRAINT `fk_alias_customer_entity`
	FOREIGN KEY (`id_customer`)
	REFERENCES `{$this->getTable('customer_entity')}` (`entity_id`)
	ON DELETE CASCADE
	ON UPDATE CASCADE,
	CONSTRAINT `fk_alias_merchand_configuration_account`
	FOREIGN KEY (`id_merchand_account`)
	REFERENCES `{$this->getTable('b2b_merchand_configuration_account')}` (`id_b2b_merchand_configuration_account`)
	ON DELETE CASCADE
	ON UPDATE CASCADE)
	ENGINE = InnoDB;

	SET FOREIGN_KEY_CHECKS=1;
");

$installer->endSetup();

