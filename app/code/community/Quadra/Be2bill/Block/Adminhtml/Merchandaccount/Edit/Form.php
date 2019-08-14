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
class Quadra_Be2bill_Block_Adminhtml_Merchandaccount_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
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
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $account = Mage::registry('current_configuration_account');
        $accountId = (int)$account->getId();
        $accounts = null;
        $modes = null;
        $options = array();
        $tabCountries = array();
        $allspecificcountries = Mage::getModel('adminhtml/system_config_source_payment_allspecificcountries')->toOptionArray();
        $logoUrl = '';
        $currency = null;

        if ($accountId > 0) { //si modification
            $currency = $account->getCurrencyIso();
            $accounts = Mage::getModel('be2bill/api_methods')->getAvailableAcountTypeByCurrency($currency);

            $accountTypeCode = $account->getB2bXmlAccountTypeCode();
            if ($account->getLogoUrl() == null || $account->getLogoUrl() == '') {
                $logoUrl = Mage::getModel('be2bill/api_methods')->getLogoUrl($accountTypeCode);
            } else {
                $logoUrl = $account->getLogoUrl();
            }

            $modes = Mage::getModel('be2bill/api_methods')->getAvailableModesByAccountType($accountTypeCode);
            // si resultat sup a 1 on supprime directlink et direct submit
            if (count($modes) > 1) {
                $i = 0;
                foreach ($modes as $tabVal) {
                    if ($tabVal['id'] == 'directlink' || $tabVal['id'] == 'direct-submit') {
                        unset($modes[$i]);
                    }
                    $i++;
                }
            }

            $options = Mage::getModel('be2bill/api_methods')->getAvailableOptionsByAccountType($accountTypeCode);

            $hasDefered = Mage::getModel('be2bill/api_methods')->hasDefered($accountTypeCode);
            $hasStandard = Mage::getModel('be2bill/api_methods')->hasStandard($accountTypeCode);
            if ($hasDefered != false) {
                array_push($options, array('option' => 'defered'));
                array_push($options, array('option' => 'delivery'));
            }

            if ($hasStandard != false) {
                array_push($options, array('option' => 'standard'));
            }

            foreach ($options as $tabOption) {
                if ('oneclick' == $tabOption['option']) { //si oneclick present on ajoute le choix : profil récurrent
                    array_push($options, array('option' => 'recurring'));
                    break;
                }
            }

            $optionsValues = Mage::helper('be2bill')->getFormattedCollectionOptionsToArray($account->getOptionsCollection());

            $colCountries = $account->getAccountCountriesCollection();
            foreach ($colCountries as $countries) {
                $tabCountries[] = strtoupper($countries->getData('country_iso'));
            }
        }

        $_countries = Mage::getModel('adminhtml/system_config_source_country')->toOptionArray();
        unset($_countries[0]);

        $fieldset = $form->addFieldset('edit_form', array('legend' => Mage::helper('be2bill')->__('Configuration générale')));

        $fieldset->addField('urlLoadAccountsType', 'hidden', array(
            'name' => 'urlLoadAccountsType',
            'value' => Mage::helper("adminhtml")->getUrl("adminhtml/merchandaccount/loadAccountsTypeByCurrency"),
        ));

        $fieldset->addField('urlLoadModes', 'hidden', array(
            'name' => 'urlLoadModes',
            'value' => Mage::helper("adminhtml")->getUrl("adminhtml/merchandaccount/loadModesTypeByAccountType"),
        ));
        
        $fieldset->addField('del_img', 'hidden', array(
        		'name' => 'del_img',
        		'value' => Mage::helper('be2bill')->__('Réinitialiser l’image par défaut (fournie par Be2Bill)'),
        ));

        $fieldset->addField('urlLoadOptions', 'hidden', array(
            'name' => 'urlLoadOptions',
            'value' => Mage::helper("adminhtml")->getUrl("adminhtml/merchandaccount/loadOptionsByAccountType"),
        ));

        $fieldset->addField('urlLoadCountries', 'hidden', array(
            'name' => 'urlLoadCountries',
            'value' => Mage::helper("adminhtml")->getUrl("adminhtml/merchandaccount/getCountriesRestriction"),
        ));

        $fieldset->addField('urlLoadLogo', 'hidden', array(
            'name' => 'urlLoadLogo',
            'value' => Mage::helper("adminhtml")->getUrl("adminhtml/merchandaccount/getLogo"),
        ));

        $fieldset->addField('id', 'hidden', array(
            'name' => 'id',
            'value' => $accountId,
        ));

        $fieldset->addField('options', 'hidden', array(
            'name' => 'options',
            'value' => implode(',', array_column($options, 'option')), //since php v5.5.0
        ));

        $fieldset->addField('is_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Statut'),
            'title' => Mage::helper('be2bill')->__('Statut'),
            'name' => 'general[active]',
            'required' => true,
            'class' => 'required-entry select',
            'value' => $account->getActive(),
            'options' => array(
                '1' => Mage::helper('be2bill')->__('Activé'),
                '0' => Mage::helper('be2bill')->__('Désactivé'),
            ),
            'tabindex' => 1,
        ));

        /*
          $values = Mage::getResourceModel('core/store_collection')->toOptionArray();
          $fieldset->addField('store', 'select', array(
          'label' => Mage::helper('be2bill')->__('Magasin'),
          'class' => 'required-entry select input-text',
          'required' => true,
          'name' => 'general[core_store_id]',
          'value' => $account->getCoreStoreId(),
          'values' => $values,
          'disabled' => false,
          'readonly' => false,
          'tabindex' => 2,
          ));
         */

        $fieldset->addField('store_id', 'select', array(
            'name' => 'general[core_store_id]',
            'label' => Mage::helper('be2bill')->__('Magasin'),
            'title' => Mage::helper('be2bill')->__('Magasin'),
            'required' => true,
            'value' => $account->getCoreStoreId(),
            'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(true, false),
            'tabindex' => 2,
        ));

        $fieldset->addField('login', 'text', array(
            'label' => Mage::helper('be2bill')->__('Identifier'),
            'title' => Mage::helper('be2bill')->__('Identifier'),
            'class' => 'required-entry input-text',
            'required' => true,
            'name' => 'general[login]',
            'value' => $account->getLogin(),
            'tabindex' => 3,
        ));

        $fieldset->addField('password', 'password', array(
            'label' => Mage::helper('be2bill')->__('Mot de passe'),
            'title' => Mage::helper('be2bill')->__('Mot de passe'),
            'class' => 'required-entry input-text',
            'required' => true,
            'name' => 'general[password]',
            'value' => $account->getPassword(),
            'tabindex' => 4,
        ));

        $fieldset->addField('logo', 'image', array(
            'label' => Mage::helper('be2bill')->__('Logo'),
            'name' => 'logo',
            'value' => $logoUrl,
            'tabindex' => 5,
        ));
        $fieldset->addField('logo_url', 'hidden', array(
            'name' => 'logo_url',
            //'value' => $logoUrl,
        ));


        $values = Mage::getModel('adminhtml/system_config_source_currency')->toOptionArray(false);
        array_unshift($values, array('value' => 0, 'label' => Mage::helper('be2bill')->__('Sélectionner la devise')));
        $fieldset->addField('currency', 'select', array(
            'label' => Mage::helper('be2bill')->__('Devise'),
            'title' => Mage::helper('be2bill')->__('Devise'),
            'class' => 'required-entry select',
            'required' => true,
            'name' => 'general[currency_iso]',
            'value' => $currency,
            'values' => $values,
            'tabindex' => 6,
        ));

        $fieldset->addField('account_type', 'select', array(
            'label' => Mage::helper('be2bill')->__('Type de compte'),
            'title' => Mage::helper('be2bill')->__('Type de compte'),
            'class' => 'required-entry select',
            'required' => true,
            'name' => 'general[b2b_xml_account_type_code]',
            'value' => $account->getB2bXmlAccountTypeCode(),
            'values' => $accounts,
            'tabindex' => 7,
        ));


        $fieldset->addField('mode', 'select', array(
            'label' => Mage::helper('be2bill')->__('Mode'),
            'title' => Mage::helper('be2bill')->__('Mode'),
            'class' => 'required-entry select',
            'required' => true,
            'name' => 'general[b2b_xml_mode_code]',
            'values' => $modes,
            'value' => $account->getB2bXmlModeCode(),
            'tabindex' => 8,
        ));


        $fieldset->addField('name', 'text', array(
            'label' => Mage::helper('be2bill')->__('Label visible sur le front office'),
            'title' => Mage::helper('be2bill')->__('Label visible sur le front office'),
            'class' => 'required-entry input-text',
            'required' => true,
            'name' => 'general[configuration_account_name]',
            'value' => $account->getConfigurationAccountName(),
            'tabindex' => 9,
        ));

        /*
          $fieldset->addField('cancel_capture_auto', 'select', array(
          'label' => Mage::helper('be2bill')->__('Annuler les commandes automatiquement'),
          'title' => Mage::helper('be2bill')->__('Annuler les commandes automatiquement'),
          'class' => 'required-entry select',
          'required' => true,
          'name' => 'general[cancel_capture_auto]',
          'value' => $account->getdata('cancel_capture_auto'),
          'options' => array(
          '1' => Mage::helper('be2bill')->__('Oui'),
          '0' => Mage::helper('be2bill')->__('Non'),
          ),
          'tabindex' => 9,
          'after_element_html' => '<p class="note"><span>' . Mage::helper('be2bill')->__('Annule les commandes dont la durée du statut \'En attente de capture be2bill\' a atteint la limite.') . '</span></p>'
          ));
         */

        $fieldset->addField('order_canceled_limited_time', 'text', array(
            'label' => Mage::helper('be2bill')->__('Annule les commandes dont le statut est "en attende" depuis : '),
            'title' => Mage::helper('be2bill')->__('Annule les commandes dont le statut est "en attende" depuis : '),
            'class' => 'required-entry input-text',
            'required' => true,
            'name' => 'general[order_canceled_limited_time]',
            'value' => $account->getdata('order_canceled_limited_time') != '' ? $account->getdata('order_canceled_limited_time') : 0,
            'tabindex' => 10,
            'after_element_html' => '<p class="note"><span>' . Mage::helper('be2bill')->__("Valeur en minutes. Mettre 0 pour désactiver l'option") . '</span></p>'
        ));


        /* Standard Payment configuration */
        $fieldset2 = $form->addFieldset('standard', array('legend' => Mage::helper('be2bill')->__('Paiement standard')));
        $fieldset2->addField('standard_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Statut'),
            'title' => Mage::helper('be2bill')->__('Statut'),
            'name' => 'option[standard][active]',
            'required' => true,
            'class' => 'required-entry select',
            'value' => isset($optionsValues['standard']['active']) ? $optionsValues['standard']['active'] : 0,
            'options' => array(
                '1' => Mage::helper('be2bill')->__('Activé'),
                '0' => Mage::helper('be2bill')->__('Désactivé'),
            ),
        ));

        $fieldset2->addField('standard_front_label', 'text', array(
            'label' => Mage::helper('be2bill')->__('Label front office'),
            'title' => Mage::helper('be2bill')->__('Label front office'),
            'class' => 'input-text',
            'name' => 'option[standard][front_label]',
            'value' => isset($optionsValues['standard']['front_label']) ? $optionsValues['standard']['front_label'] : null,
        ));

        $fieldset2->addField('standard_min', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[standard][min_amount]',
            'value' => isset($optionsValues['standard']['min_amount']) ? $optionsValues['standard']['min_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant minimum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant minimum') . '</small>',
        ));

        $fieldset2->addField('standard_max', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[standard][max_amount]',
            'value' => isset($optionsValues['standard']['max_amount']) ? $optionsValues['standard']['max_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant maximum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant maximum') . '</small>'
        ));


        /* Defered Payment configuration */
        $fieldset3 = $form->addFieldset('defered', array('legend' => Mage::helper('be2bill')->__('Paiement différé')));

        $fieldset3->addField('defered_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Statut'),
            'title' => Mage::helper('be2bill')->__('Statut'),
            'name' => 'option[defered][active]',
            'required' => true,
            'class' => 'required-entry select',
            'value' => isset($optionsValues['defered']['active']) ? $optionsValues['defered']['active'] : 0,
            'options' => array(
                '1' => Mage::helper('be2bill')->__('Activé'),
                '0' => Mage::helper('be2bill')->__('Désactivé'),
            ),
        ));

        $fieldset3->addField('defered_front_label', 'text', array(
            'label' => Mage::helper('be2bill')->__('Label front office'),
            'title' => Mage::helper('be2bill')->__('Label front office'),
            'class' => 'input-text',
            'name' => 'option[defered][front_label]',
            'value' => isset($optionsValues['defered']['front_label']) ? $optionsValues['defered']['front_label'] : null,
        ));

        $fieldset3->addField('defered_min', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[defered][min_amount]',
            'value' => isset($optionsValues['defered']['min_amount']) ? $optionsValues['defered']['min_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant minimum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant minimum') . '</small>',
        ));

        $fieldset3->addField('defered_max', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[defered][max_amount]',
            'value' => isset($optionsValues['defered']['max_amount']) ? $optionsValues['defered']['max_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant maximum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant maximum') . '</small>'
        ));


        $values = Mage::getModel('be2bill/renderer_defered')->toOptionArray();
        $fieldset3->addField('defered_values', 'select', array(
            'label' => Mage::helper('be2bill')->__('Nombre de jours'),
            'title' => Mage::helper('be2bill')->__('Nombre de jours'),
            'name' => 'option[defered][b2b_xml_option_extra]',
            'required' => true,
            'class' => 'required-entry select',
            'value' => isset($optionsValues['defered']['b2b_xml_option_extra']) ? $optionsValues['defered']['b2b_xml_option_extra'] : null,
            'values' => $values,
        ));


        /* At Delivery Payment configuration */
        $fieldset4 = $form->addFieldset('delivery', array('legend' => Mage::helper('be2bill')->__('Paiement à la livraison')));

        $fieldset4->addField('delivery_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Statut'),
            'title' => Mage::helper('be2bill')->__('Statut'),
            'name' => 'option[delivery][active]',
            'required' => true,
            'class' => 'required-entry select',
            'value' => isset($optionsValues['delivery']['active']) ? $optionsValues['delivery']['active'] : 0,
            'options' => array(
                '1' => Mage::helper('be2bill')->__('Activé'),
                '0' => Mage::helper('be2bill')->__('Désactivé'),
            ),
        ));

        $fieldset4->addField('delivery_front_label', 'text', array(
            'label' => Mage::helper('be2bill')->__('Label front office'),
            'title' => Mage::helper('be2bill')->__('Label front office'),
            'class' => 'input-text',
            'name' => 'option[delivery][front_label]',
            'value' => isset($optionsValues['delivery']['front_label']) ? $optionsValues['delivery']['front_label'] : null,
        ));

        $fieldset4->addField('delivery_min', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[delivery][min_amount]',
            'value' => isset($optionsValues['delivery']['min_amount']) ? $optionsValues['delivery']['min_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant minimum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant minimum') . '</small>',
        ));

        $fieldset4->addField('delivery_max', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[delivery][max_amount]',
            'value' => isset($optionsValues['delivery']['max_amount']) ? $optionsValues['delivery']['max_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant maximum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant maximum') . '</small>'
        ));

        if (Mage::helper('be2bill')->isMiraklInstalledAndActive()) {
            $tabValue = isset($optionsValues['delivery']['b2b_xml_option_extra']) ? $optionsValues['delivery']['b2b_xml_option_extra'] : null;

            $fieldset4->addField('mirakl_status', 'select', array(
                'label' => Mage::helper('be2bill')->__('Statut Mirakl'),
                'title' => Mage::helper('be2bill')->__('Statut Mirakl'),
                'name' => 'option[delivery][mirakl_status]',
                'required' => true,
                //'class' => 'select',
                'value' => isset($tabValue['mirakl_status']) ? $tabValue['mirakl_status'] : 'all',
                'options' => array(
                    'op_only' => Mage::helper('be2bill')->__('Désactivé pour les commandes MARKETPLACE'),
                    'mkp_only' => Mage::helper('be2bill')->__('Activé pour les commandes MARKETPLACE uniquement'),
                    'all' => Mage::helper('be2bill')->__('Activé pour TOUTES les commandes'),
                ),
            ));

            $fieldset4->addField('mkp_login', 'text', array(
                'label' => Mage::helper('be2bill')->__('Identifier / Capture Marketplace'),
                'title' => Mage::helper('be2bill')->__('Identifier / Capture Marketplace'),
                //'class' => 'input-text required-entry',
                'required' => true,
                'name' => 'option[delivery][mkp_login]',
                'value' => isset($tabValue['mkp_login']) ? $tabValue['mkp_login'] : null,
                'after_element_html' => '<br />' . Mage::helper('be2bill')->__('Identifiant permettant de capturer les montants des commandes marketplaces.<br />Les montants des commandes opérateurs seront capturés sur le compte de la configuration générale.'),
            ));

            $fieldset4->addField('mkp_password', 'password', array(
                'label' => Mage::helper('be2bill')->__('Mot de passe / Capture Marketplace'),
                'title' => Mage::helper('be2bill')->__('Mot de passe / Capture Marketplace'),
                //'class' => 'input-text required-entry',
                'required' => true,
                'name' => 'option[delivery][mkp_password]',
                'value' => isset($tabValue['mkp_password']) ? $tabValue['mkp_password'] : null,
                'after_element_html' => '<br />' . Mage::helper('be2bill')->__('Mot de passe lié à l\'identifier précédent. Ne pas renseigner si l\'identifier est non renseigné.'),
            ));
			
        }

		// permet d'ajouter dans un champ cache la liste des codes compatibles avec Mirakl.
		// ce champ doit toujours exister afin d'éviter les éventuelles erreurs JS lorsque Mirakl n'est pas disponible
        $fieldset4->addField('mkp_code_list', 'hidden', array(
            'class' => 'input-text',
            'required' => false,
            'value' => implode(',', Mage::helper('be2bill/mirakl')->getAllowedMPCode()),
        ));

        /*
        $values = Mage::getModel('adminhtml/system_config_source_order_status')->toOptionArray();
        unset($values[0]);
        $fieldset4->addField('delivery_status', 'multiselect', array(
            'label' => Mage::helper('be2bill')->__('Statut de la commande lors de la capture'),
            'title' => Mage::helper('be2bill')->__('Statut de la commande lors de la capture'),
            'name' => 'option[delivery][b2b_xml_option_extra]',
            'required' => true,
            'class' => 'required-entry multiselect',
            'value' => isset($optionsValues['delivery']['b2b_xml_option_extra']) ? $optionsValues['delivery']['b2b_xml_option_extra'] : null,
            'values' => $values,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Quand la commande est passée à un de ces statuts , la capture est exécutée automatiquement') . '</small>',
        ));
        */

        /* N times Payment configuration */
        $fieldset5 = $form->addFieldset('ntimes', array('legend' => Mage::helper('be2bill')->__('Paiement n fois')));

        $fieldset5->addField('ntimes_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Statut'),
            'title' => Mage::helper('be2bill')->__('Statut'),
            'name' => 'option[ntimes][active]',
            'required' => true,
            //'class' => 'required-entry select',
            'value' => isset($optionsValues['ntimes']['active']) ? $optionsValues['ntimes']['active'] : 0,
            'options' => array(
                '1' => Mage::helper('be2bill')->__('Activé'),
                '0' => Mage::helper('be2bill')->__('Désactivé'),
            ),
        ));

        $fieldset5->addField('ntimes_front_label', 'text', array(
            'label' => Mage::helper('be2bill')->__('Label front office'),
            'title' => Mage::helper('be2bill')->__('Label front office'),
            'class' => 'input-text',
            'name' => 'option[ntimes][front_label]',
            'value' => isset($optionsValues['ntimes']['front_label']) ? $optionsValues['ntimes']['front_label'] : null,
        ));

        $fieldset5->addField('ntimes_min', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[ntimes][min_amount]',
            'value' => isset($optionsValues['ntimes']['min_amount']) ? $optionsValues['ntimes']['min_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant minimum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant minimum') . '</small>',
        ));

        $fieldset5->addField('ntimes_max', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[ntimes][max_amount]',
            'value' => isset($optionsValues['ntimes']['max_amount']) ? $optionsValues['ntimes']['max_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant maximum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant maximum') . '</small>'
        ));

        $values = Mage::getModel('be2bill/renderer_ntimes')->toOptionArray();
        $fieldset5->addField('ntimes_values', 'select', array(
            'label' => Mage::helper('be2bill')->__('Nombre de fois'),
            'title' => Mage::helper('be2bill')->__('Nombre de fois'),
            'name' => 'option[ntimes][b2b_xml_option_extra]',
            'required' => true,
            //'class' => 'required-entry select',
            'value' => isset($optionsValues['ntimes']['b2b_xml_option_extra']) ? $optionsValues['ntimes']['b2b_xml_option_extra'] : null,
            'values' => $values,
        ));

        /* 3D Secure configuration */
        $fieldset6 = $form->addFieldset('3dsecure', array('legend' => Mage::helper('be2bill')->__('3D Secure')));
        $fieldset6->addField('secure_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Statut'),
            'title' => Mage::helper('be2bill')->__('Statut'),
            'name' => 'option[3dsecure][active]',
            'required' => true,
            //'class' => 'required-entry select',
            'value' => isset($optionsValues['3dsecure']['active']) ? $optionsValues['3dsecure']['active'] : 0,
            'options' => array(
                '0' => Mage::helper('be2bill')->__('Désactivé'),
                '1' => Mage::helper('be2bill')->__('Selective 3DS'),
                '2' => Mage::helper('be2bill')->__('Full 3DS'),
            ),
        ));

        $fieldset6->addField('secure_min', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant minimum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[3dsecure][min_amount]',
            'value' => isset($optionsValues['3dsecure']['min_amount']) ? $optionsValues['3dsecure']['min_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant minimum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant minimum') . '</small>',
        ));

        $fieldset6->addField('secure_max', 'text', array(
            'label' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'title' => Mage::helper('be2bill')->__('Montant maximum de la commande'),
            'class' => 'validate-zero-or-greater input-text',
            'required' => false,
            'name' => 'option[3dsecure][max_amount]',
            'value' => isset($optionsValues['3dsecure']['max_amount']) ? $optionsValues['3dsecure']['max_amount'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Montant maximum de la commande pour afficher la méthode de paiement') . '</small><br /><small>' . Mage::helper('be2bill')->__('Si vide, pas de montant maximum') . '</small>',
        ));


        $tabValue = isset($optionsValues['3dsecure']['b2b_xml_option_extra']) ? $optionsValues['3dsecure']['b2b_xml_option_extra'] : null;
        $val = 1;
        if (isset($tabValue['country_iso']) && count($tabValue['country_iso']) <= 0) {
            $val = 0;
        }
        $fieldset6->addField('secure_allowspecific', 'select', array(
            'label' => Mage::helper('be2bill')->__('Mode de paiement autorisé pour'),
            'title' => Mage::helper('be2bill')->__('Mode de paiement autorisé pour'),
            'name' => 'option[3dsecure][allowspecific]',
            'required' => false,
            'class' => 'select',
            'value' => $val,
            'values' => $allspecificcountries,
        ));

        $fieldset6->addField('secure_allowspecific_countries_code', 'multiselect', array(
            'label' => Mage::helper('be2bill')->__('Activer le 3D secure uniquement pour les pays :'),
            'title' => Mage::helper('be2bill')->__('Activer le 3D secure uniquement pour les pays :'),
            'name' => 'option[3dsecure][country_iso]',
            'required' => false,
            'class' => 'multiselect allowed-countries',
            'value' => isset($tabValue['country_iso']) ? $tabValue['country_iso'] : null,
            'values' => $_countries,
        ));

        $fieldset6->addField('secure_postcode', 'text', array(
            'label' => Mage::helper('be2bill')->__('Codes Postaux autorisés'),
            'title' => Mage::helper('be2bill')->__('Codes Postaux autorisés'),
            'name' => 'option[3dsecure][postcode]',
            'required' => false,
            'class' => 'text',
            'value' => isset($tabValue['postcode']) ? $tabValue['postcode'] : null,
            'after_element_html' => '<small>' . Mage::helper('be2bill')->__('Séparer par une virgule / si vide pas de restriction') . '</small>',
        ));

        $_shippingM = Mage::getModel('adminhtml/system_config_source_shipping_allmethods')->toOptionArray(true);
        $fieldset6->addField('secure_shipping_method', 'multiselect', array(
            'label' => Mage::helper('be2bill')->__('Méthodes de livraison autorisées'),
            'title' => Mage::helper('be2bill')->__('Méthodes de livraison autorisées'),
            'name' => 'option[3dsecure][shipping_method]',
            'required' => false,
            'class' => 'multiselect',
            'value' => isset($tabValue['shipping_method']) ? $tabValue['shipping_method'] : null,
            'values' => $_shippingM,
        ));

        /* On Click configuration */
        $fieldset7 = $form->addFieldset('oneclick', array('legend' => Mage::helper('be2bill')->__('Paiement en 1 clic')));

        $fieldset7->addField('oneclick_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Statut'),
            'title' => Mage::helper('be2bill')->__('Statut'),
            'name' => 'option[oneclick][active]',
            'required' => true,
            //'class' => 'required-entry select',
            'value' => isset($optionsValues['oneclick']['active']) ? $optionsValues['oneclick']['active'] : 0,
            'options' => array(
                '1' => Mage::helper('be2bill')->__('Activé'),
                '0' => Mage::helper('be2bill')->__('Désactivé'),
            ),
        ));

        $fieldset7->addField('oneclickcvv_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Saisie du cryptogramme'),
            'title' => Mage::helper('be2bill')->__('Saisie du cryptogramme'),
            'name' => 'option[oneclickcvv][active]',
            'required' => true,
            //'class' => 'required-entry select',
            'value' => isset($optionsValues['oneclickcvv']['active']) ? $optionsValues['oneclickcvv']['active'] : null,
            'options' => array(
                '1' => Mage::helper('be2bill')->__('Activé'),
                '0' => Mage::helper('be2bill')->__('Désactivé'),
            ),
        ));


        /* Recurring profil */
        $fieldset8 = $form->addFieldset('recurring', array('legend' => Mage::helper('be2bill')->__('Paiement récurrent')));

        $fieldset8->addField('recurring_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Statut'),
            'title' => Mage::helper('be2bill')->__('Statut'),
            'name' => 'option[recurring][active]',
            'required' => true,
            //'class' => 'required-entry select',
            'value' => isset($optionsValues['recurring']['active']) ? $optionsValues['recurring']['active'] : 0,
            'options' => array(
                '1' => Mage::helper('be2bill')->__('Activé'),
                '0' => Mage::helper('be2bill')->__('Désactivé'),
            ),
        ));

        /* Age verification */
        $fieldset10 = $form->addFieldset('ageverification', array('legend' => Mage::helper('be2bill')->__('Vérification de l\'age')));

        $fieldset10->addField('ageverification_active', 'select', array(
            'label' => Mage::helper('be2bill')->__('Statut'),
            'title' => Mage::helper('be2bill')->__('Statut'),
            'name' => 'option[ageverification][active]',
            'required' => true,
            //'class' => 'required-entry select',
            'value' => isset($optionsValues['ageverification']['active']) ? $optionsValues['ageverification']['active'] : 1,
            'options' => array(
                '1' => Mage::helper('be2bill')->__('Activé'),
                '0' => Mage::helper('be2bill')->__('Désactivé'),
            ),
        ));


        /* Allowed Countries */
        $fieldset11 = $form->addFieldset('allowed_countries', array('legend' => Mage::helper('be2bill')->__('Pays admis')));

        $val = 1;
        if (count($tabCountries) <= 0) {
            $val = 0;
        }

        $fieldset11->addField('allowspecific', 'select', array(
            'label' => Mage::helper('be2bill')->__('Mode de paiement autorisé pour'),
            'title' => Mage::helper('be2bill')->__('Mode de paiement autorisé pour'),
            'name' => 'countries[allowspecific]',
            'required' => false,
            'class' => 'select',
            'value' => $val,
            'values' => $allspecificcountries,
        ));

        $fieldset11->addField('allowspecific_countries_code', 'multiselect', array(
            'label' => Mage::helper('be2bill')->__('Mode de paiement autorisé pour les pays spécifiques'),
            'title' => Mage::helper('be2bill')->__('Mode de paiement autorisé pour les pays spécifiques'),
            'name' => 'countries[country_iso]',
            'required' => true,
            'class' => 'multiselect allowed-countries',
            'value' => $tabCountries,
            'values' => $_countries,
        ));

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

}

