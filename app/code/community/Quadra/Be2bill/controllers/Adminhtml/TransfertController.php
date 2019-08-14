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
class Quadra_Be2bill_Adminhtml_TransfertController extends Mage_Adminhtml_Controller_Action
{

    const OPERATION_TYPE_PAYMENT        = 'payment';
    const OPERATION_TYPE_AUTH           = 'authorization';
    const OPERATION_TYPE_CAPTURE        = 'capture';
    const OPERATION_TYPE_REFUND         = 'refund';
    const OPERATION_TYPE_ONECLICK       = 'oneclick';
    const OPERATION_TYPE_SUBSCRIPTION   = 'subscription';
    const ACTION_ORDER                  = 'order';
    const ACTION_AUTHORIZE              = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE      = 'authorize_capture';
    const CB_VISA_MASTERCARD            = 'CB/Visa/MasterCard';
    const AMERICAN_EXPRESS              = 'American Express';
    const PAYPAL                        = 'PayPal';
    
    protected $_resource;
    protected $_sales_flat_order;
    protected $_sales_flat_order_payment;
    protected $_sales_flat_quote;
    protected $_sales_flat_quote_payment;
    
    protected function _construct()
    {
    	parent::_construct();
    	$this->_resource = Mage::getSingleton('core/resource');   	
    	$this->_sales_flat_order = $this->_resource->getTableName('sales_flat_order');
    	$this->_sales_flat_order_payment = $this->_resource->getTableName('sales_flat_order_payment');
    	$this->_sales_flat_quote = $this->_resource->getTableName('sales_flat_quote');
    	$this->_sales_flat_quote_payment = $this->_resource->getTableName('sales_flat_quote_payment');

    }
    
    /**
     * Configuration Account edit action
     */
    public function editAction()
    {
        $this->loadLayout();

        $this->_setActiveMenu('be2bill/manage_payment');
        $this->_addBreadcrumb(Mage::helper('be2bill')->__('Transfert des commandes de l\'Api V1 vers V2'), Mage::helper('be2bill')->__('Transfert des commandes de l\'Api V1 vers V2'), $this->getUrl('*/*'));
        $this->_title($this->__('Transfert des commandes de l\'Api V1 vers V2'));

        $block = $this->getLayout()->createBlock('be2bill/adminhtml_transfert_edit', 'transfert_edit');
        $this->_addContent($block);
        $this->renderLayout();
    }

    /**
     * Save la configuration du compte marchant
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        $stores = Mage::app()->getStores();
        $tabIdentifierCBVisa = $tabIdentifierAmex = $tabIdentifierPaypal = $tabActiveIframe = array();
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $writeConnection = $resource->getConnection('core_write');

        //Etape 1 : on recupere un tableau des stores / identifier be2Bill
        foreach ($stores as $store) {
            $tabIdentifierCBVisa[$store->getId()] = Mage::getStoreConfig('be2bill/be2bill_api/identifier', $store->getId());
            $tabIdentifierAmex[$store->getId()] = Mage::getStoreConfig('be2bill/be2bill_amex_api/identifier', $store->getId());
            $tabIdentifierPaypal[$store->getId()] = Mage::getStoreConfig('be2bill/be2bill_paypal_api/identifier', $store->getId());
            //iframe activé?
            $tabActiveIframe[$store->getId()] = Mage::getStoreConfig('be2bill/be2bill_checkout_config/active_iframe', $store->getId());
        }

        $tabIdentifierCBVisa = array_filter($tabIdentifierCBVisa);
        $tabIdentifierAmex = array_filter($tabIdentifierAmex);
        $tabIdentifierPaypal = array_filter($tabIdentifierPaypal);

        //Donnée saisie par le marchand ?
        if ($data['cb_visa'] == '' && $data['amex'] == '' && $data['paypal'] == '') {
            Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('be2bill')->__('Aucun compte saisie'));
            $this->getResponse()->setRedirect($this->getUrl('*/transfert/edit/'));
            return;
        }

        try {
            /*
             * CB VISA MS
             */
            foreach ($tabIdentifierCBVisa as $storeId => $identifier) {

                if ($data['cb_visa'] != null && $data['cb_visa'] == $identifier) {

                    $account = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($data['cb_visa'], $storeId);
                    if ($account->getId() != null) {
                        continue; //echo 'Compte existant';
                    }

                    $passwordCBVisa = Mage::getStoreConfig('be2bill/be2bill_api/password', $storeId);
                    $activeCBVisa = Mage::getStoreConfig('payment/be2bill_standard/active', $storeId);
                    $titleCBVisa = Mage::getStoreConfig('payment/be2bill_standard/title', $storeId);
                    $paymentActionCBVisa = Mage::getStoreConfig('payment/be2bill_standard/payment_action', $storeId);
                    $cancelCaptureAutoCBVisa = Mage::getStoreConfig('payment/be2bill_standard/cancel_capture_auto', $storeId);
                    $orderCanceledLimitedTimeCBVisa = Mage::getStoreConfig('payment/be2bill_standard/order_canceled_limited_time', $storeId);
                    $useOneClickCBVisa = Mage::getStoreConfig('payment/be2bill_standard/allow_use_oneclick', $storeId);
                    $useCvvCBVisa = Mage::getStoreConfig('payment/be2bill_standard/use_cvv_oneclick', $storeId);
                    $allowRecurringProfileCBVisa = Mage::getStoreConfig('payment/be2bill_standard/allow_recurring_profile', $storeId);
                    $minOrderTotalCBVisa = Mage::getStoreConfig('payment/be2bill_standard/min_order_total', $storeId);
                    $maxOrderTotalCBVisa = Mage::getStoreConfig('payment/be2bill_standard/max_order_total', $storeId);
                    //$allowspecificCBVisa = Mage::getStoreConfig('payment/be2bill_standard/allowspecific', $storeId);
                    //$specificcountryCBVisa = Mage::getStoreConfig('payment/be2bill_standard/specificcountry', $storeId);
                    $use3dSecureCBVisa = Mage::getStoreConfig('payment/be2bill_standard/use_3dsecure', $storeId);

                    //SEVERAL
                    $activeSeveral = Mage::getStoreConfig('payment/be2bill_several/active', $storeId);
                    $titleSeveral = Mage::getStoreConfig('payment/be2bill_several/title', $storeId);
                    $nTimesSeveral = Mage::getStoreConfig('payment/be2bill_several/n_times', $storeId);
                    $minOrderTotalSeveral = Mage::getStoreConfig('payment/be2bill_several/min_order_total', $storeId);
                    $maxOrderTotalSeveral = Mage::getStoreConfig('payment/be2bill_several/max_order_total', $storeId);


                    //Création du compte
                    $account->setB2bXmlAccountTypeCode(self::CB_VISA_MASTERCARD);
                    if ($tabActiveIframe[$storeId] == 1) {
                        $account->setB2bXmlModeCode('form-iframe');
                    } else {
                        $account->setB2bXmlModeCode('form');
                    }
                    $account->setCurrencyIso('EUR');
                    $account->setLogin($data['cb_visa']);
                    $account->setPassword($passwordCBVisa);
                    $account->setConfigurationAccountName($titleCBVisa);
                    $account->setActive($activeCBVisa);
                    $account->setCoreStoreId($storeId);
                    $account->setCancelCaptureAuto($cancelCaptureAutoCBVisa);
                    $account->setOrderCanceledLimitedTime($orderCanceledLimitedTimeCBVisa);

                    $account->save();

                    //Activation de l'option paiement standard
                    $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                    $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                            ->setData('b2b_xml_option', 'standard')
                            ->setData('min_amount', $minOrderTotalCBVisa)
                            ->setData('max_amount', $maxOrderTotalCBVisa)
                            ->setData('active', 1)
                            ->setData('front_label', null)
                            ->save();

                    //Activation de l'option paiement différé
                    if ($paymentActionCBVisa == 'authorize') {
                        $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                        $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                                ->setData('b2b_xml_option', 'defered')
                                ->setData('min_amount', $minOrderTotalCBVisa)
                                ->setData('max_amount', $maxOrderTotalCBVisa)
                                ->setData('b2b_xml_option_extra', serialize(5))
                                ->setData('active', 1)
                                ->save();
                    }

                    //one Click
                    if ($useOneClickCBVisa == 1) {
                        $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                        $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                                ->setData('b2b_xml_option', 'oneclick')
                                ->setData('active', 1)
                                ->save();
                    }

                    //OneClick CVV
                    if ($useCvvCBVisa == 1) {
                        $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                        $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                                ->setData('b2b_xml_option', 'oneclickcvv')
                                ->setData('active', 1)
                                ->save();
                    }

                    //recurring
                    if ($allowRecurringProfileCBVisa == 1) {
                        $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                        $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                                ->setData('b2b_xml_option', 'recurring')
                                ->setData('active', 1)
                                ->save();
                    }

                    //3d secure
                    if ($use3dSecureCBVisa == 1) {
                        $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                        $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                                ->setData('b2b_xml_option', '3dsecure')
                                ->setData('active', 1)
                                ->save();
                    }

                    //n times
                    if ($activeSeveral == 1) {
                        $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                        $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                                ->setData('b2b_xml_option', 'ntimes')
                                ->setData('active', 1)
                                ->setData('front_label', $titleSeveral)
                                ->setData('b2b_xml_option_extra', serialize($nTimesSeveral))
                                ->setData('min_amount', $minOrderTotalSeveral != '' ? $minOrderTotalSeveral : null)
                                ->setData('max_amount', $maxOrderTotalSeveral != '' ? $maxOrderTotalSeveral : null)
                                ->save();
                    }

                    //tout les pays pas défaut
                    $data['countries']['country_iso'] = array_column(Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(), 'value');
                    unset($data['countries']['country_iso'][0]);
                    foreach ($data['countries']['country_iso'] as $country) {
                        $countries = Mage::getModel('be2bill/merchandconfigurationaccountcountries');
                        $countries->setData('id_b2b_merchand_configuration_account', $account->getId());
                        $countries->setData('country_iso', strtolower($country));
                        $countries->save();
                    }
                }
            }


            /*
             * AMEX
             */
            foreach ($tabIdentifierAmex as $storeId => $identifier) {
                if ($data['amex'] != null && $data['amex'] == $identifier) {
                    $account = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($data['amex'], $storeId);
                    if ($account->getId() != null) {
                        continue; //echo 'Compte existant';
                    }

                    $passwordAmex = Mage::getStoreConfig('be2bill/be2bill_amex_api/password', $storeId);
                    $activeAmex = Mage::getStoreConfig('payment/be2bill_amex/active', $storeId);
                    $titleAmex = Mage::getStoreConfig('payment/be2bill_amex/title', $storeId);
                    $paymentActionAmex = Mage::getStoreConfig('payment/be2bill_amex/payment_action', $storeId);
                    $cancelCaptureAutoAmex = Mage::getStoreConfig('payment/be2bill_amex/cancel_capture_auto', $storeId);
                    $orderCanceledLimitedTimeAmex = Mage::getStoreConfig('payment/be2bill_amex/order_canceled_limited_time', $storeId);
                    $minOrderTotalAmex = Mage::getStoreConfig('payment/be2bill_amex/min_order_total', $storeId);
                    $maxOrderTotalAmex = Mage::getStoreConfig('payment/be2bill_amex/max_order_total', $storeId);
                    $allowspecificAmex = Mage::getStoreConfig('payment/be2bill_amex/allowspecific', $storeId);
                    $specificcountryAmex = Mage::getStoreConfig('payment/be2bill_amex/specificcountry', $storeId);

                    //Création du compte
                    $account->setB2bXmlAccountTypeCode(self::AMERICAN_EXPRESS);
                    if ($tabActiveIframe[$storeId] == 1) {
                        $account->setB2bXmlModeCode('form-iframe');
                    } else {
                        $account->setB2bXmlModeCode('form');
                    }
                    $account->setCurrencyIso('EUR');
                    $account->setLogin($data['amex']);
                    $account->setPassword($passwordAmex);
                    $account->setConfigurationAccountName($titleAmex);
                    $account->setActive($activeAmex);
                    $account->setCoreStoreId($storeId);
                    $account->setCancelCaptureAuto($cancelCaptureAutoAmex);
                    $account->setOrderCanceledLimitedTime($orderCanceledLimitedTimeAmex);
                    $account->save();

                    //Activation de l'option paiement standard
                    $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                    $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                            ->setData('b2b_xml_option', 'standard')
                            ->setData('min_amount', $minOrderTotalAmex)
                            ->setData('max_amount', $maxOrderTotalAmex)
                            ->setData('active', 1)
                            ->setData('front_label', null)
                            ->save();

                    //Activation de l'option paiement différé
                    if ($paymentActionAmex == 'authorize') {
                        $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                        $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                                ->setData('b2b_xml_option', 'defered')
                                ->setData('min_amount', $minOrderTotalCBVisa)
                                ->setData('max_amount', $maxOrderTotalCBVisa)
                                ->setData('b2b_xml_option_extra', serialize(5))
                                ->setData('active', 1)
                                ->save();
                    }

                    //tout les pays pas défaut
                    $data['countries']['country_iso'] = array_column(Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(), 'value');
                    unset($data['countries']['country_iso'][0]);
                    foreach ($data['countries']['country_iso'] as $country) {
                        $countries = Mage::getModel('be2bill/merchandconfigurationaccountcountries');
                        $countries->setData('id_b2b_merchand_configuration_account', $account->getId());
                        $countries->setData('country_iso', strtolower($country));
                        $countries->save();
                    }
                }
            }
            /*
             * Paypal
             */
            foreach ($tabIdentifierPaypal as $storeId => $identifier) {
                if ($data['paypal'] != null && $data['paypal'] == $identifier) {
                    $account = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($data['paypal'], $storeId);
                    if ($account->getId() != null) {
                        continue; //echo 'Compte existant';
                    }

                    $passwordPaypal = Mage::getStoreConfig('be2bill/be2bill_paypal_api/password');
                    $activePaypal = Mage::getStoreConfig('payment/be2bill_paypal/active');
                    $titlePaypal = Mage::getStoreConfig('payment/be2bill_paypal/title');
                    $paymentActionPaypal = Mage::getStoreConfig('payment/be2bill_paypal/payment_action');
                    $cancelCaptureAutoPaypal = Mage::getStoreConfig('payment/be2bill_paypal/cancel_capture_auto');
                    $orderCanceledLimitedTimePaypal = Mage::getStoreConfig('payment/be2bill_paypal/order_canceled_limited_time');
                    $minOrderTotalPaypal = Mage::getStoreConfig('payment/be2bill_paypal/min_order_total');
                    $maxOrderTotalPaypal = Mage::getStoreConfig('payment/be2bill_paypal/max_order_total');
                    $allowspecificPaypal = Mage::getStoreConfig('payment/be2bill_paypal/allowspecific');
                    $specificcountryPaypal = Mage::getStoreConfig('payment/be2bill_paypal/specificcountry');

                    //Création du compte
                    $account->setB2bXmlAccountTypeCode(self::PAYPAL);
                    $account->setB2bXmlModeCode('directlink');
                    $account->setCurrencyIso('EUR');
                    $account->setLogin($data['paypal']);
                    $account->setPassword($passwordPaypal);
                    $account->setConfigurationAccountName($titlePaypal);
                    $account->setActive($activePaypal);
                    $account->setCoreStoreId($storeId);
                    $account->setCancelCaptureAuto($cancelCaptureAutoPaypal);
                    $account->setOrderCanceledLimitedTime($orderCanceledLimitedTimePaypal);
                    $account->save();

                    //Activation de l'option paiement standard
                    $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                    $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                            ->setData('b2b_xml_option', 'standard')
                            ->setData('min_amount', $minOrderTotalPaypal)
                            ->setData('max_amount', $maxOrderTotalPaypal)
                            ->setData('active', 1)
                            ->setData('front_label', null)
                            ->save();

                    //Activation de l'option paiement différé
                    if ($paymentActionPaypal == 'authorize') {
                        $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                        $option->setData('id_b2b_merchand_configuration_account', $account->getId())
                                ->setData('b2b_xml_option', 'defered')
                                ->setData('min_amount', $minOrderTotalPaypal)
                                ->setData('max_amount', $maxOrderTotalPaypal)
                                ->setData('b2b_xml_option_extra', serialize(5))
                                ->setData('active', 1)
                                ->save();
                    }

                    //tout les pays pas défaut
                    $data['countries']['country_iso'] = array_column(Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(), 'value');
                    unset($data['countries']['country_iso'][0]);
                    foreach ($data['countries']['country_iso'] as $country) {
                        $countries = Mage::getModel('be2bill/merchandconfigurationaccountcountries');
                        $countries->setData('id_b2b_merchand_configuration_account', $account->getId());
                        $countries->setData('country_iso', strtolower($country));
                        $countries->save();
                    }
                }
            }


            /*
             * Etape 2-1 Migration des anciennes commandes Cb visa action = Paiment
             */

            //A) on récupére les commandes (order) cb visa
            $sql = '
                SELECT `sfo`.`entity_id`, `sfo`.`quote_id` , `sfo`.`store_id`, `sfo`.`customer_id`, `sfop`.`additional_information`
                FROM `'.$this->_sales_flat_order.'` sfo , `'.$this->_sales_flat_order_payment.'` sfop
                WHERE `sfo`.`entity_id` = `sfop`.`parent_id`
                    AND `method` = "be2bill_standard"
                    AND `amount_authorized` is NULL ';

            $result = $readConnection->fetchAll($sql);
            $tabOrderCbPai = array();
            foreach ($result as $data) {
                $tabOrderCbPai[$data['store_id']][] = array('id' => $data['entity_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id'], 'quote' => $data['quote_id']);
            }


            //Pour chaque store
            foreach ($tabOrderCbPai as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierCBVisa[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);

                //On met a jour les tables de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_PAYMENT;
                    $addInfo['action'] = self::ACTION_AUTHORIZE_CAPTURE;
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfos['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_order_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'parent_id =' . $tabInfo['id']);

                    if ($tabInfo['quote'] != null && $tabInfo['quote'] != '') { // quote_id == null == commande récurrente
                        $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'quote_id = ' . $tabInfo['quote']);
                    }
                }
            }

            /*
             * Etape  2-2 Migration des anciennes commandes Cb visa action = AUT
             */

            //A)  on récupère les quotes cb visa
            $sql = '
                SELECT `sfo`.`entity_id`, `sfo`.`quote_id` , `sfo`.`store_id`, `sfo`.`customer_id`, `sfop`.`additional_information`
                FROM `'.$this->_sales_flat_order.'` sfo , `'.$this->_sales_flat_order_payment.'` sfop
                WHERE `sfo`.`entity_id` = `sfop`.`parent_id`
                    AND `method` = "be2bill_standard"
                    AND `amount_authorized` is not NULL ';

            $result = $readConnection->fetchAll($sql);
            $tabOrderCbAut = array();
            foreach ($result as $data) {
                $tabOrderCbAut[$data['store_id']][] = array('id' => $data['entity_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id'], 'quote' => $data['quote_id']);
            }

            //Pour chaque store
            foreach ($tabOrderCbAut as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierCBVisa[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);

                //On met a jour la table de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_AUTH;
                    $addInfo['action'] = self::ACTION_AUTHORIZE;
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    $addInfo['delivery'] = true;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfo['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_order_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'parent_id =' . $tabInfo['id']);

                    if ($tabInfo['quote'] != null && $tabInfo['quote'] != '') { // quote_id == null == commande récurrente
                        $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'quote_id = ' . $tabInfo['quote']);
                    }
                }
            }

            /*
             * Etape 2-3 Migration des quotes cb visa non traité ... (paiement récurrent)
             */

            $sql = '
                SELECT `sfqp`.`payment_id`, `sfq`.`store_id`, `sfq`.`customer_id`, `sfqp`.`additional_information`
                FROM `'.$this->_sales_flat_quote.'` sfq , `'.$this->_sales_flat_quote_payment.'` sfqp
                WHERE `sfq`.`entity_id` = `sfqp`.`quote_id`
                    AND `method` = "be2bill_standard"
                    AND `sfqp`.`additional_information` not like "%be2bill_method_label%" ';

            $result = $readConnection->fetchAll($sql);
            $tabQuoteCb = array();
            foreach ($result as $data) {
                $tabQuoteCb[$data['store_id']][] = array('id' => $data['payment_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id']);
            }

            //Pour chaque store
            foreach ($tabQuoteCb as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierCBVisa[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);

                //On met a jour la table de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_PAYMENT;
                    $addInfo['action'] = self::ACTION_AUTHORIZE_CAPTURE;
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfo['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'payment_id = ' . $tabInfo['id']);
                }
            }

            /*
             * Fin Etape 2
             */

            /*
             * Etape 3-1 AMEX
             */

            //A) on récupére les commandes (order) cb visa
            $sql = '
                SELECT `sfo`.`entity_id`, `sfo`.`quote_id` , `sfo`.`store_id`, `sfo`.`customer_id`, `sfop`.`additional_information`
                FROM `'.$this->_sales_flat_order.'` sfo , `'.$this->_sales_flat_order_payment.'` sfop
                WHERE `sfo`.`entity_id` = `sfop`.`parent_id`
                    and `method` = "be2bill_amex"
                    and `amount_authorized` is NULL ';

            $result = $readConnection->fetchAll($sql);
            $tabOrderCbPai = array();
            foreach ($result as $data) {
                $tabOrderCbPai[$data['store_id']][] = array('id' => $data['entity_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id'], 'quote' => $data['quote_id']);
            }

            //Pour chaque store
            foreach ($tabOrderCbPai as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierAmex[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);

                //On met a jour les tables de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_PAYMENT;
                    $addInfo['action'] = self::ACTION_AUTHORIZE_CAPTURE;
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfos['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_order_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'parent_id =' . $tabInfo['id']);


                    if ($tabInfo['quote'] != null && $tabInfo['quote'] != '') { // quote_id == null == commande récurrente
                        $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'quote_id = ' . $tabInfo['quote']);
                    }
                }
            }

            /*
             * Etape  3-2 Migration des anciennes commandes Cb visa action = AUT
             */

            //A)  on récupère les quotes cb visa
            $sql = '
                SELECT `sfo`.`entity_id`, `sfo`.`quote_id` , `sfo`.`store_id`, `sfo`.`customer_id`, `sfop`.`additional_information`
                FROM `'.$this->_sales_flat_order.'` sfo , `'.$this->_sales_flat_order_payment.'` sfop
                WHERE `sfo`.`entity_id` = `sfop`.`parent_id`
                    and `method` = "be2bill_amex"
                    and `amount_authorized` is not NULL ';

            $result = $readConnection->fetchAll($sql);
            $tabOrderCbAut = array();
            foreach ($result as $data) {
                $tabOrderCbAut[$data['store_id']][] = array('id' => $data['entity_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id'], 'quote' => $data['quote_id']);
            }

            //Pour chaque store
            foreach ($tabOrderCbAut as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierAmex[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);
                echo 'id : ' . $merchandAccount->getId();

                //On met a jour la table de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_AUTH;
                    $addInfo['action'] = self::ACTION_AUTHORIZE;
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    $addInfo['delivery'] = true;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfo['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_order_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'parent_id =' . $tabInfo['id']);

                    if ($tabInfo['quote'] != null && $tabInfo['quote'] != '') { // quote_id == null == commande récurrente
                        $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'quote_id = ' . $tabInfo['quote']);
                    }
                }
            }

            /*
             * Etape 3-3 Migration des quotes cb visa non traité ... (paiement récurrent)
             */

            $sql = '
                SELECT `sfqp`.`payment_id`, `sfq`.`store_id`, `sfq`.`customer_id`, `sfqp`.`additional_information`
                FROM `'.$this->_sales_flat_quote.'` sfq , `'.$this->_sales_flat_quote_payment.'` sfqp
                WHERE `sfq`.`entity_id` = `sfqp`.`quote_id`
                    and `method` = "be2bill_amex"
                    and `sfqp`.`additional_information` not like "%be2bill_method_label%" ';

            $result = $readConnection->fetchAll($sql);
            $tabQuoteCb = array();
            foreach ($result as $data) {
                $tabQuoteCb[$data['store_id']][] = array('id' => $data['payment_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id']);
            }

            //Pour chaque store
            foreach ($tabQuoteCb as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierAmex[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);

                //On met a jour la table de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_PAYMENT;
                    $addInfo['action'] = self::ACTION_AUTHORIZE_CAPTURE;
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfo['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'payment_id = ' . $tabInfo['id']);
                }
            }


            /*
             * Fin Etape 3
             */

            /*
             * Etape 4-1 PAYPAL
             */

            //A)on récupére les commandes (order) cb visa
            $sql = '
                SELECT `sfo`.`entity_id`, `sfo`.`quote_id` , `sfo`.`store_id`, `sfo`.`customer_id`, `sfop`.`additional_information`
                FROM `'.$this->_sales_flat_order.'` sfo , `'.$this->_sales_flat_order_payment.'` sfop
                WHERE `sfo`.`entity_id` = `sfop`.`parent_id`
                    and `method` = "be2bill_paypal"
                    and `amount_authorized` is NULL ';

            $result = $readConnection->fetchAll($sql);
            $tabOrderCbPai = array();
            foreach ($result as $data) {
                $tabOrderCbPai[$data['store_id']][] = array('id' => $data['entity_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id'], 'quote' => $data['quote_id']);
            }

            //Pour chaque store
            foreach ($tabOrderCbPai as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierPaypal[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);
                echo 'id : ' . $merchandAccount->getId();

                //On met a jour les tables de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_PAYMENT;
                    $addInfo['action'] = self::ACTION_AUTHORIZE_CAPTURE;
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfos['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_order_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'parent_id =' . $tabInfo['id']);

                    if ($tabInfo['quote'] != null && $tabInfo['quote'] != '') { // quote_id == null == commande récurrente
                        $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'quote_id = ' . $tabInfo['quote']);
                    }
                }
            }

            /*
             * Etape 4-2 Migration des anciennes commandes Cb visa action = AUT
             */

            //A)on récupère les quotes cb visa
            $sql = '
                SELECT `sfo`.`entity_id`, `sfo`.`quote_id` , `sfo`.`store_id`, `sfo`.`customer_id`, `sfop`.`additional_information`
                FROM `'.$this->_sales_flat_order.'` sfo , `'.$this->_sales_flat_order_payment.'` sfop
                WHERE `sfo`.`entity_id` = `sfop`.`parent_id`
                    and `method` = "be2bill_paypal"
                    and `amount_authorized` is not NULL ';

            $result = $readConnection->fetchAll($sql);
            $tabOrderCbAut = array();
            foreach ($result as $data) {
                $tabOrderCbAut[$data['store_id']][] = array('id' => $data['entity_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id'], 'quote' => $data['quote_id']);
            }

            //Pour chaque store
            foreach ($tabOrderCbAut as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierPaypal[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);
                echo 'id : ' . $merchandAccount->getId();

                //On met a jour la table de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_AUTH;
                    $addInfo['action'] = self::ACTION_AUTHORIZE;
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    $addInfo['delivery'] = true;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfo['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_order_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'parent_id =' . $tabInfo['id']);


                    if ($tabInfo['quote'] != null && $tabInfo['quote'] != '') { // quote_id == null == commande récurrente
                        $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'quote_id = ' . $tabInfo['quote']);
                    }
                }
            }

            /*
             * Etape 4-3 Migration des quotes cb visa non traité ... (paiement récurrent)
             */

            $sql = '
                SELECT `sfqp`.`payment_id`, `sfq`.`store_id`, `sfq`.`customer_id`, `sfqp`.`additional_information`
                FROM `'.$this->_sales_flat_quote.'` sfq , `'.$this->_sales_flat_quote_payment.'` sfqp
                WHERE `sfq`.`entity_id` = `sfqp`.`quote_id`
                    and `method` = "be2bill_paypal"
                    and `sfqp`.`additional_information` not like "%be2bill_method_label%" ';

            $result = $readConnection->fetchAll($sql);
            $tabQuoteCb = array();
            foreach ($result as $data) {
                $tabQuoteCb[$data['store_id']][] = array('id' => $data['payment_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id']);
            }

            //Pour chaque store
            foreach ($tabQuoteCb as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierPaypal[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);
                echo 'id : ' . $merchandAccount->getId();

                //On met a jour la table de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_PAYMENT;
                    $addInfo['action'] = self::ACTION_AUTHORIZE_CAPTURE;
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfo['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'payment_id = ' . $tabInfo['id']);
                }
            }
            /*
             * Fin Etape 4
             */

            /*
             * Etape 5 N fois
             */

            //A) on récupére les commandes (order) cb visa
            $sql = '
                SELECT `sfo`.`entity_id`, `sfo`.`quote_id` , `sfo`.`store_id`, `sfo`.`customer_id`, `sfop`.`additional_information`
                FROM `'.$this->_sales_flat_order.'` sfo , `'.$this->_sales_flat_order_payment.'` sfop
                WHERE `sfo`.`entity_id` = `sfop`.`parent_id`
                    and `method` = "be2bill_several"';


            $result = $readConnection->fetchAll($sql);
            $tabOrderCbPai = array();
            foreach ($result as $data) {
                $tabOrderCbPai[$data['store_id']][] = array('id' => $data['entity_id'], 'add' => $data['additional_information'], 'customer' => $data['customer_id'], 'quote' => $data['quote_id']);
            }

            //Pour chaque store
            foreach ($tabOrderCbPai as $storeId => $tabInfos) {
                //on recupere le marchandAccount
                $identifier = $tabIdentifierCBVisa[$storeId];
                $merchandAccount = Mage::getModel('be2bill/merchandconfigurationaccount')->loadByIdentifier($identifier, $storeId);
                echo 'id : ' . $merchandAccount->getId();

                //On met a jour les tables de paiement
                foreach ($tabInfos as $tabInfo) {
                    $addInfo = unserialize($tabInfo['add']);

                    $addInfo['account_id'] = $merchandAccount->getId();
                    $addInfo['account'] = $merchandAccount->getData('b2b_xml_account_type_code');
                    $addInfo['options'] = 'standard';
                    $addInfo['be2bill_method_label'] = $merchandAccount->getData('configuration_account_name');
                    $addInfo['operation'] = self::OPERATION_TYPE_PAYMENT;
                    $addInfo['action'] = self::ACTION_AUTHORIZE_CAPTURE;
                    $addInfo['ntimes'] = $merchandAccount->getNtimes();
                    $addInfo['mode'] = $merchandAccount->getData('b2b_xml_mode_code');
                    $addInfo['alias'] = null;
                    if ($addInfo['use_oneclick'] == 'yes') {
                        $customer = Mage::getModel('customer/customer')->load($tabInfos['customer']);
                        if ($customer->getId() != '') {
                            $addInfo['alias'] = $customer->getData('be2bill_alias');
                        }
                    }
                    $seriaAddInfo = serialize($addInfo);

                    $writeConnection->update($resource->getTableName('sales_flat_order_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'parent_id =' . $tabInfo['id']);

                    if ($tabInfo['quote'] != null && $tabInfo['quote'] != '') { // quote_id == null == commande récurrente
                        $writeConnection->update($resource->getTableName('sales_flat_quote_payment'), array('method' => 'be2bill', 'additional_information' => $seriaAddInfo), 'quote_id = ' . $tabInfo['quote']);
                    }
                }
            }

            /*
             * Fin Etape 5
             */
        } catch (Mage_Core_Exception $e) {
            echo $this->_getSession()->addError($e->getMessage());
            $this->getResponse()->setRedirect($this->getUrl('*/merchandaccount/'));
        } catch (Exception $e) {
            $this->_getSession()->addException($e, Mage::helper('be2bill')->__('Une erreur est survenue'));
            $this->getResponse()->setRedirect($this->getUrl('*/merchandaccount/'));
            return;
        }

        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('be2bill')->__('Transfert réalisé'));
        $this->getResponse()->setRedirect($this->getUrl('*/merchandaccount/'));
    }

}
