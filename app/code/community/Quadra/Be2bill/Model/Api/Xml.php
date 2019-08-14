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
class Quadra_Be2bill_Model_Api_Xml extends Mage_Core_Model_Abstract
{

    const XML_B2B_DIR = 'be2bill';
    const XML_FILE_NAME = 'dynamicPayments.xml';
    const XML_PATH_ADMIN_BE2BILL_VERSION = 'be2bill/be2bill_api/version';
    const XML_PATH_ADMIN_BE2BILL_URL_XML = 'be2bill/be2bill_api/url_xml';

    protected $_b2bXmlModesLang;
    protected $_b2bXmlOperationLang;
    protected $_b2bXmlOptions;
    protected $_b2bXmlOptionsLang;
    protected $_b2bXmlAccountTypes;
    protected $_b2bXmlAccountTypeLang;
    protected $_b2bXmlAccountTypeCurrency;
    protected $_b2bXmlAccountTypeParameterSet;
    protected $_b2bXmlAccountTypeParameterSetCountries;
    protected $_b2bXmlAccountTypeParameterSetMode;
    protected $_b2bXmlAccountTypeParameterSetOperation;
    protected $_b2bXmlAccountTypeParameterSetParameters;
    protected $_b2bXmlAccountTypeParameterSetOptions;
    protected $_b2bMerchandConfigurationAccount;
    protected $_b2bMerchandConfigurationAccountOptions;
    protected $_b2bMerchandConfigurationAccountCountries;
    protected $_filePath;
    protected $_resource;
    protected $_writeConnection;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('be2bill/api_xml');

        $this->_resource = Mage::getSingleton('core/resource');
        $this->_writeConnection = $this->_resource->getConnection('core_write');

        //$this->_filePath = Mage::getBaseDir('media') . DS . self::XML_B2B_DIR  . DS . 'xml' . DS . self::XML_FILE_NAME ;
        $this->_filePath = Mage::getStoreConfig(self::XML_PATH_ADMIN_BE2BILL_URL_XML);

        $this->_b2bXmlModesLang = $this->_resource->getTableName('b2b_xml_modes_lang');
        $this->_b2bXmlOperationLang = $this->_resource->getTableName('b2b_xml_operation_lang');
        $this->_b2bXmlOptions = $this->_resource->getTableName('b2b_xml_options');
        $this->_b2bXmlOptionsLang = $this->_resource->getTableName('b2b_xml_options_lang');
        $this->_b2bXmlAccountTypes = $this->_resource->getTableName('b2b_xml_account_types');
        $this->_b2bXmlAccountTypeLang = $this->_resource->getTableName('b2b_xml_account_type_lang');
        $this->_b2bXmlAccountTypeCurrency = $this->_resource->getTableName('b2b_xml_account_type_currency');
        $this->_b2bXmlAccountTypeParameterSet = $this->_resource->getTableName('b2b_xml_account_type_parameter_set');
        $this->_b2bXmlAccountTypeParameterSetCountries = $this->_resource->getTableName('b2b_xml_account_type_parameter_set_countries');
        $this->_b2bXmlAccountTypeParameterSetMode = $this->_resource->getTableName('b2b_xml_account_type_parameter_set_mode');
        $this->_b2bXmlAccountTypeParameterSetOperation = $this->_resource->getTableName('b2b_xml_account_type_parameter_set_operation');
        $this->_b2bXmlAccountTypeParameterSetParameters = $this->_resource->getTableName('b2b_xml_account_type_parameter_set_parameters');
        $this->_b2bXmlAccountTypeParameterSetOptions = $this->_resource->getTableName('b2b_xml_account_type_parameter_set_options');
        $this->_b2bMerchandConfigurationAccount = $this->_resource->getTableName('b2b_merchand_configuration_account');
        $this->_b2bMerchandConfigurationAccountOptions = $this->_resource->getTableName('b2b_merchand_configuration_account_options');
        $this->_b2bMerchandConfigurationAccountCountries = $this->_resource->getTableName('b2b_merchand_configuration_account_countries');
    }

    /**
     * Lecture et insertion du fichier XMl dans les tables Be2Bill de la BDD
     * @return boolean
     */
    public function insertXmlToDb()
    {
        $xml_payments = simplexml_load_file($this->_filePath);
        if ($xml_payments != null) {

            //Test Si l'Xml a déjà été traité
            if (!$this->paymentsNeedUpgrade($xml_payments->version)) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('be2bill')->__("Xml déjà importé en version " . $xml_payments->version));
                return false;
            }

            $this->resetBe2billXmlTables();

            //Maj de la version du XML dans l'admin System > Configuration > Be2bill > Configuration gènérale > Information module > Version
            Mage::getModel('core/config')->saveConfig(self::XML_PATH_ADMIN_BE2BILL_VERSION, $xml_payments->version);

            $sql = 'SET FOREIGN_KEY_CHECKS=0;';
            $this->_writeConnection->query($sql);

            // installing modes
            $modes = $xml_payments->modes;
            foreach ($modes->mode as $mode) {
                $code = $mode->code;
                foreach ($mode->labels->children() as $label) {
                    $name = (string)$label;
                    $lang_iso = (string)$label['language'];

                    // insert in db for each language
                    $sql = '
                        INSERT INTO `' . $this->_b2bXmlModesLang . '`
                            (`b2b_xml_mode_code`,`lang_iso`,`mode_lang_name`)
                        VALUES ("' . $code . '","' . $lang_iso . '","' . $name . '")';

                    $this->_writeConnection->query($sql);
                }
            }

            // installing operations
            $operations = $xml_payments->operations;
            foreach ($operations->operation as $operation) {
                $code = $operation->code;
                foreach ($operation->labels->children() as $label) {
                    $name = (string)$label;
                    $lang_iso = (string)$label['language'];

                    // insert in db for each language
                    $sql = '
                        INSERT INTO `' . $this->_b2bXmlOperationLang . '`
                            (`b2b_xml_operation_code`,`lang_iso`,`operation_lang_name`)
                        VALUES ("' . $code . '","' . $lang_iso . '","' . $name . '")';

                    $this->_writeConnection->query($sql);
                }
            }

            // installing options
            $options = $xml_payments->options;
            foreach ($options->option as $option) {
                $code = $option->code;
                foreach ($option->labels->children() as $label) {
                    $name = (string)$label;
                    $lang_iso = (string)$label['language'];

                    // insert in db for each language
                    $sql = '
                        INSERT INTO `' . $this->_b2bXmlOptionsLang . '`
                            (`b2b_xml_option_code`,`lang_iso`,`option_lang_name`)
                        VALUES ("' . $code . '","' . $lang_iso . '","' . $name . '")';

                    $this->_writeConnection->query($sql);
                }

                // setting options parameters
                $parameters = $option->parameters;
                foreach ($parameters->parameter as $param) {
                    foreach ($param->children() as $parameter) {
                        $parameter_name = $parameter->getname();
                        if ($parameter_name != 'code') {
                            $type = strtoupper((string)$parameter_name);
                            $value = strtoupper((string)$parameter);
                        } else {
                            $code_parameter = (string)$parameter;
                        }
                    }
                    $sql = '
                        INSERT INTO `' . $this->_b2bXmlOptions . '`
                            (`b2b_xml_option_code`,`b2b_xml_parameter_name`, `b2b_xml_parameter_type`,`b2b_xml_parameter_value`)
                        VALUES ("' . $code . '","' . $code_parameter . '","' . $type . '","' . $value . '")';

                    $this->_writeConnection->query($sql);
                }
            }

            // installing account_types
            $account_types = $xml_payments->account_types;
            foreach ($account_types->account_type as $account_type) {
                $code = $account_type->code;
                $logo_url = $account_type->logo_url;

                // insert base struture
                $sql = '
                    INSERT INTO `' . $this->_b2bXmlAccountTypes . '`
                        (`b2b_xml_account_type_code`,`b2b_xml_account_type_logo`)
                    VALUES ("' . $code . '","' . $logo_url . '")';

                $this->_writeConnection->query($sql);

                // insert language
                foreach ($account_type->labels->children() as $label) {
                    $name = (string)$label;
                    $lang_iso = (string)$label['language'];

                    // insert in db for each language
                    $sql = '
                        INSERT INTO `' . $this->_b2bXmlAccountTypeLang . '`
                            (`b2b_xml_account_type_code`,`lang_iso`,`account_type_lang_name`)
                        VALUES ("' . $code . '","' . $lang_iso . '","' . $name . '")';

                    $this->_writeConnection->query($sql);
                }

                // insert currencies allowed
                foreach ($account_type->currencies->children() as $currencie) {
                    $currency_iso = (string)$currencie;
                    // insert in db for each currency
                    $sql = '
                        INSERT INTO `' . $this->_b2bXmlAccountTypeCurrency . '`
                            (`b2b_xml_account_type_code`,`currency_iso`)
                        VALUES ("' . $code . '","' . $currency_iso . '")';

                    $this->_writeConnection->query($sql);
                }

                // setting options parameters
                $parameter_sets = $account_type->parameter_sets;
                foreach ($parameter_sets->parameter_set as $parameter_set) {
                    $api_version = $parameter_set->be2bill_api_version;
                    $logo_url = $parameter_set->logo_url;
                    $sql = '
                        INSERT INTO `' . $this->_b2bXmlAccountTypeParameterSet . '`
                            (`b2b_xml_account_type_parameter_set_version`,`b2b_xml_account_type_parameter_set_logo`,`b2b_xml_account_type_code`)
                        VALUES ("' . $api_version . '","' . $logo_url . '","' . $code . '")';

                    $this->_writeConnection->query($sql);
                    // get parameter_set id
                    $id_parameter_set = $this->_writeConnection->lastInsertId();

                    // setting parameter set options
                    $options = $parameter_set->options;
                    foreach ($options->children() as $option) {
                        $parameter_option = (string)$option;
                        $sql = '
                            INSERT INTO `' . $this->_b2bXmlAccountTypeParameterSetOptions . '`
                                (`id_b2b_xml_account_type_parameter_set`, `b2b_xml_option`)
                            VALUES (' . (int)$id_parameter_set . ',"' . $parameter_option . '")';

                        $this->_writeConnection->query($sql);
                    }

                    // setting parameter set operations
                    $operations = $parameter_set->operations;
                    foreach ($operations->children() as $operation) {
                        $parameter_operation = (string)$operation;
                        $sql = '
                            INSERT INTO `' . $this->_b2bXmlAccountTypeParameterSetOperation . '`
                                (`id_b2b_xml_account_type_parameter_set`,`b2b_xml_operation_code`)
                            VALUES (' . (int)$id_parameter_set . ',"' . $parameter_operation . '")';

                        $this->_writeConnection->query($sql);
                    }

                    // insert mode allowed
                    $modes = $parameter_set->modes;
                    foreach ($modes->children() as $mode) {
                        $mode = (string)$mode;
                        // insert in db for each mode
                        $sql = '
                            INSERT INTO `' . $this->_b2bXmlAccountTypeParameterSetMode . '`
                                (`id_b2b_xml_account_type_parameter_set`,`b2b_xml_mode_code`)
                            VALUES (' . (int)$id_parameter_set . ',"' . $mode . '")';

                        $this->_writeConnection->query($sql);
                    }

                    // setting allowed countries for parameter set
                    $allowed_countries = $parameter_set->countries;
                    foreach ($allowed_countries->children() as $allowed_country) {
                        $country = (string)$allowed_country;
                        $sql = '
                            INSERT INTO `' . $this->_b2bXmlAccountTypeParameterSetCountries . '`
                                (`id_b2b_xml_account_type_parameter_set`, `country_iso`)
                            VALUES (' . (int)$id_parameter_set . ',"' . $country . '")';

                        $this->_writeConnection->query($sql);
                    }

                    // setting options parameters
                    $parameters = $parameter_set->parameters;
                    foreach ($parameters->parameter as $param) {
                        foreach ($param->children() as $parameter) {
                        	$merchantHash = 'YES';
                            $parameter_name = $parameter->getname();
                            if ($parameter_name == 'merchant_hash') {
                            	$merchantHash = strtoupper((string)$parameter);
                            }   	
                        	else if ($parameter_name != 'code') {
                                $type = strtoupper((string)$parameter_name);
                                $value = strtoupper((string)$parameter);
                            } else {
                                $code_parameter = (string)$parameter;
                            }
                        }
                        $sql = '
                            INSERT INTO `' . $this->_b2bXmlAccountTypeParameterSetParameters . '`
                                (`id_b2b_xml_account_type_parameter_set`, `b2b_xml_parameter_name`,`b2b_xml_parameter_type`,`b2b_xml_parameter_value`,`b2b_xml_parameter_merchant_hash`)
                            VALUES (' . (int)$id_parameter_set . ',"' . $code_parameter . '","' . $type . '","' . $value . '","' . $merchantHash. '")';

                        $this->_writeConnection->query($sql);
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function uninstallBe2billXmlTables()
    {
        $sql = '
            SET FOREIGN_KEY_CHECKS=0;

            DROP TABLE IF EXISTS `' . $this->_b2bXmlModesLang . '`,
                    `' . $this->_b2bXmlOperationLang . '`,
                    `' . $this->_b2bXmlOptions . '`,
                    `' . $this->_b2bXmlOptionsLang . '`,
                    `' . $this->_b2bXmlAccountTypes . '`,
                    `' . $this->_b2bXmlAccountTypeLang . '`,
                    `' . $this->_b2bXmlAccountTypeCurrency . '`,
                    `' . $this->_b2bXmlAccountTypeParameterSet . '`,
                    `' . $this->_b2bXmlAccountTypeParameterSetCountries . '`,
                    `' . $this->_b2bXmlAccountTypeParameterSetMode . '`,
                    `' . $this->_b2bXmlAccountTypeParameterSetOperation . '`,
                    `' . $this->_b2bXmlAccountTypeParameterSetParameters . '`,
                    `' . $this->_b2bXmlAccountTypeParameterSetOptions . '`,
                    `' . $this->_b2bMerchandConfigurationAccount . '`,
                    `' . $this->_b2bMerchandConfigurationAccountOptions . '`,
                    `' . $this->_b2bMerchandConfigurationAccountCountries . '`;

            SET FOREIGN_KEY_CHECKS=1;';

        return $this->_writeConnection->query($sql);
    }

    public function resetBe2billXmlTables()
    {
        $sql = '
            SET FOREIGN_KEY_CHECKS=0;

            TRUNCATE `' . $this->_b2bXmlModesLang . '`;
            TRUNCATE `' . $this->_b2bXmlOperationLang . '`;
            TRUNCATE `' . $this->_b2bXmlOptions . '`;
            TRUNCATE `' . $this->_b2bXmlOptionsLang . '`;
            TRUNCATE `' . $this->_b2bXmlAccountTypes . '`;
            TRUNCATE `' . $this->_b2bXmlAccountTypeLang . '`;
            TRUNCATE `' . $this->_b2bXmlAccountTypeCurrency . '`;
            TRUNCATE `' . $this->_b2bXmlAccountTypeParameterSet . '`;
            TRUNCATE `' . $this->_b2bXmlAccountTypeParameterSetCountries . '`;
            TRUNCATE `' . $this->_b2bXmlAccountTypeParameterSetMode . '`;
            TRUNCATE `' . $this->_b2bXmlAccountTypeParameterSetOperation . '`;
            TRUNCATE `' . $this->_b2bXmlAccountTypeParameterSetParameters . '`;
            TRUNCATE `' . $this->_b2bXmlAccountTypeParameterSetOptions . '`;


            SET FOREIGN_KEY_CHECKS=1;';

        return $this->_writeConnection->query($sql);
    }

    /*
      TRUNCATE `'.$this->_b2bMerchandConfigurationAccount.'`;
      TRUNCATE `'.$this->_b2bMerchandConfigurationAccountOptions.'`;
      TRUNCATE `'.$this->_b2bMerchandConfigurationAccountCountries.'`;
     */

    /**
     * methode permettant de valider ou non l'import d'un nouveau XML
     * @param string version du XML en attente d'import
     * @return boolean
     */
    public function paymentsNeedUpgrade($version)
    {
        // recuperation de la version XML en cours
        $currentVersion = Mage::getStoreConfig(self::XML_PATH_ADMIN_BE2BILL_VERSION);

        // est-ce le premier import ?
        if ($currentVersion == '') {
            return true;
        }

        // verification pas de changement de version
        // return true pour relancer tout de meme l'import
        if ($currentVersion === (string)$version) {
            return true;
        }

        $aCurrentVersion = explode('.', $currentVersion);
        $aVersion = explode('.', $version);

        // X1 = X2
        if ($aCurrentVersion[0] === $aVersion[0]) {
            // Y1 < Y2
            if ($aCurrentVersion[1] < $aVersion[1]) {
                return true;
            }
            // Y1 = Y2
            else if ($aCurrentVersion[1] === $aVersion[1]) {
                // Z1 < Z2
                if ($aCurrentVersion[2] < $aVersion[2]) {
                    return true;
                }
                // Z1 = Z2 || Z1 > Z2
                else {
                    Mage::getSingleton('adminhtml/session')->addError(
                            Mage::helper('be2bill')->__('La modification de version ' . $currentVersion . ' vers ' . $version . ' ne peut être effectuée')
                    );
                    return false;
                }
            }
            // Y1 > Y2
            else {
                Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('be2bill')->__('La modification de version ' . $currentVersion . ' vers ' . $version . ' ne peut être effectuée')
                );
                return false;
            }
        }
        // si numero de version X differente
        else {
            // version X courante < version X nouveau XML
            if ($aCurrentVersion[0] < $aVersion[0]) {
                Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('be2bill')->__('La mise à jour du XML de la version ' . $aCurrentVersion[0] . '.Y.Z vers ' . $aVersion[0] . '.Y.Z nécessite une mise à jour applicative du module Be2Bill')
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('be2bill')->__('Le XML de version ' . $aVersion[0] . '.Y.Z est antérieure à celle que vous utilisez actuellement ' . $aCurrentVersion[0] . '.Y.Z')
                );
            }
            return false;
        }

        if ($version == $currentVersion) {
            return false;
        }
        return true;
    }

}
