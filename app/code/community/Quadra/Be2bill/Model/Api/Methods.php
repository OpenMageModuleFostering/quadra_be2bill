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
class Quadra_Be2bill_Model_Api_Methods extends Mage_Core_Model_Abstract
{

    const TYPE_DEFERED = 'defered';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_ONECLICK = 'oneclick';
    const TYPE_ONECLICKCVV = 'oneclickcvv';
    const TYPE_NTIMES = 'ntimes';
    const TYPE_AGEVERIFICATION = 'ageverification';
    const TYPE_DISPLAYCREATEALIAS = 'displaycreatealias';
    const TYPE_3DSECURE = '3dsecure';
    const TYPE_REFUND = 'refund';
    const TYPE_REFUND_PARTIAL = 'partial_refund';

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
    protected $_resource;
    protected $_readConnection;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('be2bill/api_methods');

        $this->_resource = Mage::getSingleton('core/resource');
        $this->_readConnection = $this->_resource->getConnection('core_read');

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
     * Check if account type can do refund operation
     *
     * @return array if true
     */
    public function hasRefund($accountTypeCode)
    {
        if ($accountTypeCode) {
            $sql = '
                SELECT b2b_xml_operation_code
                FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ap
                LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetOperation . '` ao
                    ON ao.`id_b2b_xml_account_type_parameter_set` = ap.`id_b2b_xml_account_type_parameter_set`
                WHERE b2b_xml_account_type_code = "' . $accountTypeCode . '"
                    AND ao.b2b_xml_operation_code = "' . self::TYPE_REFUND . '"';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Check if account type can do refund operation
     *
     * @return array if true
     */
    public function hasRefundPartial($accountTypeCode)
    {
        if ($accountTypeCode) {
            $sql = '
                SELECT b2b_xml_operation_code
                FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ap
                LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetOperation . '` ao
                    ON ao.`id_b2b_xml_account_type_parameter_set` = ap.`id_b2b_xml_account_type_parameter_set`
                WHERE b2b_xml_account_type_code = "' . $accountTypeCode . '"
                    AND ao.b2b_xml_operation_code = "' . self::TYPE_REFUND_PARTIAL . '"';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Get all available account type for a currency
     *
     * @param string $currencyIso Currency iso
     * @return array Account type list
     */
    public function getAvailableAcountTypeByCurrency($currencyIso)
    {
        if ($currencyIso) {

            $langIso = substr(Mage::app()->getLocale()->getLocaleCode(), 3, 4);

            $sql = '
                SELECT ac.b2b_xml_account_type_code as value , acl.account_type_lang_name as label
                FROM `' . $this->_b2bXmlAccountTypeCurrency . '` ac
                LEFT JOIN `' . $this->_b2bXmlAccountTypeLang . '` acl ON acl.b2b_xml_account_type_code = ac.b2b_xml_account_type_code
                WHERE ac.currency_iso IN ("' . $currencyIso . '", "all")
                    AND (acl.lang_iso = "' . strtolower($langIso) . '" OR acl.lang_iso = "en")
                GROUP BY ac.b2b_xml_account_type_code';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Get all available Modes type for a account Type Code
     *
     * @param string $accountTypeCode
     * @return array Modes type list
     */
    public function getAvailableModesByAccountType($accountTypeCode)
    {
        if ($accountTypeCode) {

            $langIso = substr(Mage::app()->getLocale()->getLocaleCode(), 3, 4);

            $sql = '
                SELECT apm.b2b_xml_mode_code as id , apm.b2b_xml_mode_code as value, aml.mode_lang_name as label
                FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ap
                LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetMode . '` apm
                    ON apm.id_b2b_xml_account_type_parameter_set = ap.id_b2b_xml_account_type_parameter_set
                LEFT JOIN `' . $this->_b2bXmlModesLang . '` aml
                    ON aml.b2b_xml_mode_code = apm.b2b_xml_mode_code
                LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetOperation . '` apo
                    ON apo.id_b2b_xml_account_type_parameter_set = ap.id_b2b_xml_account_type_parameter_set
                WHERE ap.b2b_xml_account_type_code = "' . $accountTypeCode . '"
                    AND (aml.lang_iso = "' . $langIso . '" OR aml.lang_iso = "en")
                    AND apo.b2b_xml_operation_code IN ("payment" , "authorization")
                GROUP BY apm.b2b_xml_mode_code
                ORDER BY aml.mode_lang_name';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Get all available Options type for a account Type Code
     *
     * @param string $accountTypeCode
     * @return array Options type list
     */
    public function getAvailableOptionsByAccountType($accountTypeCode)
    {
        if ($accountTypeCode) {
            $sql = '
                SELECT apo.b2b_xml_option as `option`
                FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ap
                INNER JOIN `' . $this->_b2bXmlAccountTypeParameterSetOptions . '` apo
                    ON apo.id_b2b_xml_account_type_parameter_set = ap.id_b2b_xml_account_type_parameter_set
                WHERE ap.b2b_xml_account_type_code = "' . $accountTypeCode . '"
                GROUP BY apo.b2b_xml_option';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Paiement différé possible ?
     *
     * @param string $accountTypeCode
     * @return array sql result
     */
    public function hasDefered($accountTypeCode)
    {
        if ($accountTypeCode) {
            $sql = '
                SELECT b2b_xml_operation_code
                FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ap
                INNER JOIN `' . $this->_b2bXmlAccountTypeParameterSetOperation . '` ao
                    ON ao.`id_b2b_xml_account_type_parameter_set` = ap.`id_b2b_xml_account_type_parameter_set`
                    AND ao.b2b_xml_operation_code = "authorization"
                WHERE b2b_xml_account_type_code = "' . $accountTypeCode . '"';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Paiement standard possible ?
     *
     * @param string $accountTypeCode
     * @return array sql result
     */
    public function hasStandard($accountTypeCode)
    {
        if ($accountTypeCode) {
            $sql = '
                SELECT b2b_xml_operation_code
                FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ap
                INNER JOIN `' . $this->_b2bXmlAccountTypeParameterSetOperation . '` ao
                    ON ao.`id_b2b_xml_account_type_parameter_set` = ap.`id_b2b_xml_account_type_parameter_set`
                    AND ao.b2b_xml_operation_code = "payment"
                WHERE b2b_xml_account_type_code = "' . $accountTypeCode . '"';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * A t-il aucune restriction sur les pays de facturation ?
     *
     * @param string $accountTypeCode
     * @return array list country iso
     */
    public function hasNotCountriesRestriction($accountTypeCode)
    {
        if ($accountTypeCode) {
            $sql = '
                SELECT country_iso
                FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ap
                LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetCountries . '` ac
                    ON ac.`id_b2b_xml_account_type_parameter_set` = ap.`id_b2b_xml_account_type_parameter_set`
                WHERE b2b_xml_account_type_code = "' . $accountTypeCode . '"
                    AND ac.country_iso = "al"
                GROUP BY country_iso';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Retourne une liste de pays en fonction du code du type de compte
     *
     * @param string $accountTypeCode
     * @return array list country iso
     */
    public function getCountriesRestriction($accountTypeCode)
    {
        if ($accountTypeCode) {
            $sql = '
                SELECT ac.country_iso as label
                FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ap
                LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetCountries . '` ac
                    ON ac.`id_b2b_xml_account_type_parameter_set` = ap.`id_b2b_xml_account_type_parameter_set`
                WHERE ap.b2b_xml_account_type_code = "' . $accountTypeCode . '"
                    AND ac.country_iso <> "al"
                GROUP BY ac.country_iso';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Retourne l'url du logo par défaut du xml
     *
     * @param string $accountTypeCode
     * @return array result sql
     */
    public function getLogoUrl($accountTypeCode)
    {
        if ($accountTypeCode) {
            $sql = '
                SELECT b2b_xml_account_type_logo
                FROM `' . $this->_b2bXmlAccountTypes . '`
                WHERE b2b_xml_account_type_code = "' . $accountTypeCode . '"';

            return $this->_readConnection->fetchOne($sql);
        }
        return false;
    }

    /**
     * Retourne les paramètres du compte et du type d'operation
     *
     * @param string $accountTypeCode
     * @return array result sql
     */
    public function getAccountTypeParameters($accountTypeCode, $operation = 'payment' , $countryId)
    {
        if ($accountTypeCode) {
            $sql = '
                SELECT psp.*,ps.b2b_xml_account_type_parameter_set_version
                FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ps
                LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetOperation . '` pso
                    ON ps.id_b2b_xml_account_type_parameter_set = pso.id_b2b_xml_account_type_parameter_set
                LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetParameters . '` psp
                    ON psp.id_b2b_xml_account_type_parameter_set = ps.id_b2b_xml_account_type_parameter_set
				LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetCountries . '` psc 
					ON psc.id_b2b_xml_account_type_parameter_set = ps.id_b2b_xml_account_type_parameter_set 		
                WHERE ps.b2b_xml_account_type_code = "' . $accountTypeCode . '"
                    AND pso.b2b_xml_operation_code = "' . $operation . '"
                    AND ( psc.country_iso =	"'.$countryId.'" OR psc.country_iso = "al" )
                    ';
            
            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }
    
    /**
     * Est ce que le compte possède le parametre identificationdocid ?
     *
     * @param string $accountTypeCode
     * @param string $operation
     * @param string $countryId
     * @return boolean 
     */
    public function hasIdenDocId($accountTypeCode, $operation, $countryId)
    {
    	$name = 'IDENTIFICATIONDOCID';
    	$sql = '
            SELECT psp.id_b2b_xml_account_type_parameter_set
            FROM `' . $this->_b2bXmlAccountTypeParameterSet . '` ps
            LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetOperation . '` pso
                ON ps.id_b2b_xml_account_type_parameter_set = pso.id_b2b_xml_account_type_parameter_set
            LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetParameters . '` psp
                ON psp.id_b2b_xml_account_type_parameter_set = ps.id_b2b_xml_account_type_parameter_set
			LEFT JOIN `' . $this->_b2bXmlAccountTypeParameterSetCountries . '` psc
				ON psc.id_b2b_xml_account_type_parameter_set = ps.id_b2b_xml_account_type_parameter_set
            WHERE ps.b2b_xml_account_type_code = "' . $accountTypeCode . '"
                AND pso.b2b_xml_operation_code = "' . $operation . '"
                AND ( psc.country_iso =	"'.$countryId.'" OR psc.country_iso = "al" )
                AND psp.b2b_xml_parameter_name = "'.$name.'"
                    ';

    		return $this->_readConnection->fetchOne($sql);
    }
    
    /**
     * Retourne les paramètres de l'option
     *
     * @param string $codeOption
     * @return array result sql
     */
    public function getOptionParameters($codeOption)
    {
        if ($codeOption) {
            $sql = '
                SELECT *
                FROM `' . $this->_b2bXmlOptions . '`
                WHERE b2b_xml_option_code = "' . $codeOption . '"';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Get Extra Option by Account id
     *
     * @param int $idAccount
     * @return array result sql
     */
    public function getExtraOptions($idAccount)
    {
        if ($idAccount) {
            $sql = '
                SELECT *
                FROM `' . $this->_b2bMerchandConfigurationAccountOptions . '`
                WHERE active in (1,2) AND b2b_xml_option IN("' . self::TYPE_3DSECURE . '","' . self::TYPE_AGEVERIFICATION . '","' . self::TYPE_DISPLAYCREATEALIAS . '")
                    AND id_b2b_merchand_configuration_account = ' . (int)$idAccount;

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Get Extra Option for Mirakl by Account id
     *
     * @param int $idAccount
     * @return array result sql
     */
    public function getMiraklExtraOptions($idAccount)
    {
        if ($idAccount) {
            $sql = '
                SELECT *
                FROM `' . $this->_b2bMerchandConfigurationAccountOptions . '`
                WHERE active in (1,2) AND b2b_xml_option IN("' . self::TYPE_DELIVERY . '")
                    AND id_b2b_merchand_configuration_account = ' . (int)$idAccount;

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

    /**
     * Get Label Mode by Code and lang iso
     *
     * @param string $code
     * @param string $langIso
     * @return boolean false ||  array result sql
     */
    public function getModeLangByCodeIso($code, $langIso)
    {
        if ($code) {
            $sql = '
                SELECT `mode_lang_name`, `lang_iso`
                FROM `' . $this->_b2bXmlModesLang . '`
                WHERE ( lang_iso = "' . $langIso . '" OR lang_iso = "en" )
                    AND b2b_xml_mode_code = "' . $code . '"';

            return $this->_readConnection->fetchAll($sql);
        }
        return false;
    }

}
