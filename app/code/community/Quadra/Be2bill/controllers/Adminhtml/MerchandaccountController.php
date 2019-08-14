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
class Quadra_Be2bill_Adminhtml_MerchandaccountController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Initialisation
     * @param string $idFieldName
     * @return Quadra_Be2bill_Adminhtml_MerchandaccountController
     */
    protected function _initAccount($idFieldName = 'id')
    {
        $this->_title($this->__('Configuration du compte'))->_title($this->__('Configuration du compte'));

        $accountId = (int)$this->getRequest()->getParam($idFieldName);
        $model = Mage::getModel('be2bill/merchandconfigurationaccount');

        if ($accountId) {
            $model->load($accountId);
        }

        Mage::register('current_configuration_account', $model);

        return $this;
    }

    /**
     * View manage accounts list
     */
    public function indexAction()
    {
        $this->_title($this->__('Gestion des comptes'))->_title($this->__('Be2bill : Configuration des comptes'));

        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }
        $this->loadLayout();

        //Add breadcrumb item
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Gestion des comptes'), Mage::helper('adminhtml')->__('Gestion des comptes'));

        //Set active menu item
        $this->_setActiveMenu('be2bill');

        //Append block to content
        $this->_addContent(
                $this->getLayout()->createBlock('be2bill/adminhtml_merchandaccount', 'account')
        );

        $this->renderLayout();
    }

    /**
     * JSON Grid Action
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
                $this->getLayout()->createBlock('be2bill/adminhtml_merchandaccount_grid')->toHtml()
        );
    }

    /**
     * Import Xml dynamicPayments into the Database
     */
    public function importXmltoDbAction()
    {
        try {
            if (Mage::getModel('be2bill/api_xml')->insertXmlToDb()) {
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('be2bill')->__('Import réalisé avec succès'));
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('be2bill')->__("Echec de l'import"));
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/index');
    }

    /**
     * Configuration Account edit action
     */
    public function editAction()
    {
        $this->loadLayout();
        $this->_initAccount('id');
        $confAccount = Mage::registry('current_configuration_account');

        $this->_setActiveMenu('be2bill/manage_payment');
        $this->_addBreadcrumb(Mage::helper('be2bill')->__('Configuration du compte'), Mage::helper('be2bill')->__('Configuration du compte'), $this->getUrl('*/*'));

        if ($this->getRequest()->getParam('id')) {
            $this->_addBreadcrumb(Mage::helper('be2bill')->__('Edition du compte'), Mage::helper('be2bill')->__('Edition du compte'));
        } else {
            $this->_addBreadcrumb(Mage::helper('be2bill')->__('Nouveau compte'), Mage::helper('be2bill')->__('Nouveau compte'));
        }

        $this->_title($confAccount->getId() ? $confAccount->getId() : $this->__('Nouveau compte'));

        $this->_addContent($this->getLayout()->createBlock('be2bill/adminhtml_merchandaccount_edit', 'merchandaccount_edit')
                        ->setEditMode((bool)$this->getRequest()->getParam('id')));

        $this->renderLayout();
    }

    /**
     * Create new paymnt account action
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Save la configuration du compte marchant
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        if ($data) {
            $this->_initAccount('id');
            $account = Mage::registry('current_configuration_account');

            try {
                /*
                 * Gestion des donnée générales
                 */

                //gestion du label
                if ($data['general']['configuration_account_name'] == '') {
                    $data['general']['configuration_account_name'] = $data['b2b_xml_account_type_value'];
                }

                $account->addData($data['general']);

                /*
                 * Gestion du Logo
                 */
                if (isset($data['logo']['delete']) && $data['logo']['delete'] == 1) {
                	$pathMag = $this->_uploadImage2(Mage::getModel('be2bill/api_methods')->getLogoUrl($account['b2b_xml_account_type_code']));
                    $account->setData('logo_url', $pathMag); 
                } else if (isset($_FILES['logo']['name']) and ( file_exists($_FILES['logo']['tmp_name']))) {
                    $logo_path = $this->_uploadImage('logo');
                    $account->setData('logo_url', $logo_path);
                } else if ($data['logo_url'] != '') {         
                    $pathMag = $this->_uploadImage2($data['logo_url']);
                    $account->setData('logo_url', $pathMag);
                }
                $account->save();

                /*
                 * Gestion des pays autorisés
                 */
                // etape 1 : on supprime tout les pays associés au compte (reset)
                $account->deleteAccountCountriesCollection();

                // etape 2 : ajout des pays
                //si tout les pays
                if ($data['countries']['allowspecific'] == 0) {
                    $result = $this->getCountriesRestrictionAction($data['general']['b2b_xml_account_type_code']);
                    if ($result['found'] == 'no-restriction') {
                        $data['countries']['country_iso'] = array_column(Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(), 'value');
                        unset($data['countries']['country_iso'][0]);
                    } else if ($result['found'] == 'restriction') {
                        $data['countries']['country_iso'] = array_column($result['countries'], 'label');
                    }
                }

                foreach ($data['countries']['country_iso'] as $country) {
                    $countries = Mage::getModel('be2bill/merchandconfigurationaccountcountries');
                    $countries->setData('id_b2b_merchand_configuration_account', $account->getId());
                    $countries->setData('country_iso', strtolower($country));
                    $countries->save();
                }

                /*
                 * Gestion des options
                 */
                // etape 1 : on supprime tout les options associés au compte (reset)
                $account->deleteOptionsCollection();
                // etape 2 : ajout des options
                foreach ($data['option'] as $code => $tab) {
                    if (!array_key_exists('b2b_xml_option_extra', $tab)) {
                        $tab['b2b_xml_option_extra'] = null;
                    }
                    if (!array_key_exists('front_label', $tab)) {
                        $tab['front_label'] = null;
                    }

                    $option = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                    $option->setData('b2b_xml_option', $code)
                            ->setData('id_b2b_merchand_configuration_account', $account->getId())
                            ->setData('min_amount', isset($tab['min_amount']) && $tab['min_amount'] != null ? $tab['min_amount'] : null )
                            ->setData('max_amount', isset($tab['max_amount']) && $tab['max_amount'] != null ? $tab['max_amount'] : null)
                            ->setData('b2b_xml_option_extra', serialize($tab['b2b_xml_option_extra']))
                            ->setData('active', $tab['active'])
                            ->setData('front_label', $tab['front_label']);

                    if ($code == 'delivery' && Mage::helper('be2bill')->isMiraklInstalledAndActive()) {
                        $optionExtra = array(
                            'mirakl_status' => $tab['mirakl_status'],
                            'mkp_login' => $tab['mkp_login'],
                            'mkp_password' => $tab['mkp_password'],
                        );
                        $option->setData('b2b_xml_option_extra', serialize($optionExtra));
                    } elseif ($code == '3dsecure') {
                        //pays admis
                        if ($tab['allowspecific'] == 0) {
                            $tab['country_iso'] = array_column(Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(), 'value');
                            unset($tab['country_iso'][0]);
                        }
                        if (!array_key_exists('country_iso', $tab)) {
                            $tab['country_iso'] = null;
                        }

                        $optionExtra = array('country_iso' => $tab['country_iso'], 'postcode' => $tab['postcode'], 'shipping_method' => $tab['shipping_method']);
                        $option->setData('b2b_xml_option_extra', serialize($optionExtra));
                    }
                    $option->save();

                }

				// verifier l'existence de l'option displaycreatealias dans b2b_xml_account_type_parameter_set_options
                if(in_array('displaycreatealias', explode(',', $data['options']))){
                    $option1 = Mage::getModel('be2bill/merchandconfigurationaccountoptions');
                    $option1->setData('b2b_xml_option', 'displaycreatealias')
                            ->setData('id_b2b_merchand_configuration_account', $account->getId())
                            ->setData('active', 1)
                            ->save();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('be2bill')->__('Compte sauvegardé'));
            } catch (Mage_Core_Exception $e) {
                echo $this->_getSession()->addError($e->getMessage());
                //$this->_getSession()->setConfigurationAccountData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/merchandaccount/edit', array('id' => $account->getId())));
            } catch (Exception $e) {
                $this->_getSession()->addException($e, Mage::helper('be2bill')->__('Une erreur est survenue durant l\'enregistrement du compte'));
                //$this->_getSession()->setConfigurationAccountData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/merchandaccount/edit', array('id' => $account->getId())));
                return;
            }
        }

 		if ($this->getRequest()->getParam('back')) {
        	$this->_redirect('*/*/edit', array('id' => $account->getId()));
        } else {
            $this->_redirect('*/*/');
        //$this->getResponse()->setRedirect($this->getUrl('*/merchandaccount'));
        }
    }

    /**
     * Upload l'image passé en paramètre
     * @param string $name
     * @return string|NULL
     */
    protected function _uploadImage($name)
    {
        try {
            $uploader = new Varien_File_Uploader($name);
            $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'gif', 'png', 'svg')); // or pdf or anything
            $uploader->setAllowRenameFiles(false);

            $uploader->setFilesDispersion(false);
            $path = Mage::getBaseDir('media') . DS . 'be2bill' . DS . 'images';
            $uploader->save($path, $_FILES[$name]['name']);

            return 'be2bill' . DS . 'images' . DS . $_FILES[$name]['name'];
        } catch (Exception $e) {
            Mage::logException($e);
            return null;
        }
    }
    
    /**
     * Upload l'image passé en paramètre via l'url de l'image
     * @param string $url
     * @return string|NULL
     */
    protected function _uploadImage2($url)
    {
    	try {
    		$tab = explode('/', $url) ;
    		$path = Mage::getBaseDir('media') . DS . 'be2bill' . DS . 'images'. DS .$tab[count($tab)-1];
    		
    		if (file_exists($path) && is_file($path)){
    			@chmod($path, 0777); // NT ?
    			unlink($path);	
    		}
    				
    		$img = file_get_contents($url);
    		$result = file_put_contents($path, $img);
    		if ($result === false || $result == 0){
    			 $this->_getSession()->addNotice( Mage::helper('be2bill')->__('Une erreur est survenue durant le téléchargement du logo. Veuillez réessayer ultérieurement'));
    			return null;
    		}
    		@chmod($path, 0777);
    		return 'be2bill' . DS . 'images'. DS .$tab[count($tab)-1];
    	} catch (Exception $e) {
    		Mage::logException($e);
    		return null;
    	}
    }

    /**
     * Delete Account action
     */
    public function deleteAction()
    {
        $this->_initAccount();
        $account = Mage::registry('current_configuration_account');
        if ($account->getId()) {
            try {
                $account->load($account->getId());
                $account->deleteAccountCountriesCollection();
                $account->deleteOptionsCollection();
                $account->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('be2bill')->__('Le compte a bien été supprimé'));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/merchandaccount');
    }

    /**
     * Retourne une collection de type du compte en fonction de la devise
     * @param ajax string devise
     * @param ajax int id
     * @return ajax response
     */
    public function loadAccountsTypeByCurrencyAction()
    {
        $currency = $this->getRequest()->getParam('value', false);
        $id = $this->getRequest()->getParam('id', false);

        $result = Mage::getModel('be2bill/api_methods')->getAvailableAcountTypeByCurrency($currency);
        $return = array('id' => $id, 'result' => $result);

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($return));
    }

    /**
     * Retourne une collection de mode disponible en fonction du type du compte
     * @param ajax string value
     * @param ajax int id
     * @return ajax response
     */
    public function loadModesTypeByAccountTypeAction()
    {
        $accountType = $this->getRequest()->getParam('value', false);
        $id = $this->getRequest()->getParam('id', false);

        $result = Mage::getModel('be2bill/api_methods')->getAvailableModesByAccountType($accountType);

        // si resultat sup a 1 on supprime directlink et direct submit
        if (count($result) > 1) {
            $i = 0;
            foreach ($result as $tabVal) {
                if ($tabVal['id'] == 'directlink' || $tabVal['id'] == 'direct-submit') {
                    unset($result[$i]);
                }
                $i++;
            }
        }

        $return = array('id' => $id, 'result' => array_values($result));
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($return));
    }

    /**
     * Retourne une collection d'options disponible en fonction du type du compte
     * @param ajax string value
     * @return ajax response
     */
    public function loadOptionsByAccountTypeAction()
    {
        $accountType = $this->getRequest()->getParam('value', false);

        $options = Mage::getModel('be2bill/api_methods')->getAvailableOptionsByAccountType($accountType);
        $hasDefered = Mage::getModel('be2bill/api_methods')->hasDefered($accountType);
        $hasStandard = Mage::getModel('be2bill/api_methods')->hasStandard($accountType);

        if ($hasDefered != false) { // si authorization alors on ajoute les options (paiements à la livraison et paiement différé)
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

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($options));
    }

    /**
     * Retourne un array avec une liste de pays
     * ou no restiction si le compte n'a pas de restriction pays
     * @return array
     */
    public function getCountriesRestrictionAction($accountType = null)
    {
        $_ajax = false;
        if ($accountType == null) {
            $_ajax = true;
            $accountType = $this->getRequest()->getParam('value', false);
        }
        $result = array();
        if (Mage::getModel('be2bill/api_methods')->hasNotCountriesRestriction($accountType)) {
            $result['found'] = 'no-restriction';
        } elseif ($countries = Mage::getModel('be2bill/api_methods')->getCountriesRestriction($accountType)) {
            $result['found'] = 'restriction';
            $result['countries'] = $countries;
        } else {
            $result['found'] = false;
        }
        if ($_ajax) {
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            return $result;
        }
    }

    /**
     *
     * AJAX retourne un tableau avec les informations sur les logo du compte selectionné
     * @return array
     */
    public function getLogoAction()
    {
        $accountType = $this->getRequest()->getParam('value', false);

        $result = null;
        $result = Mage::getModel('be2bill/api_methods')->getLogoUrl($accountType);

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * MASS ACTION : Suppression de compte(s)
     */
    public function massDeleteAction()
    {
        $accountIds = $this->getRequest()->getParam('merchandaccount');

        if (!is_array($accountIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('be2bill')->__('Aucun compte sélectionné'));
        } else {
            try {
                $account = Mage::getModel('be2bill/merchandconfigurationaccount');
                foreach ($accountIds as $id) {
                    $account->load($id);
                    $account->deleteAccountCountriesCollection();
                    $account->deleteOptionsCollection();
                    $account->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('be2bill')->__('%d enregistrement(s) supprimé(s)', count($accountIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * MASS ACTION : Active le(s) compte(s)
     */
    public function massEnableAction()
    {
        $accountIds = $this->getRequest()->getParam('merchandaccount');

        if (!is_array($accountIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('be2bill')->__('Aucun compte sélectionné'));
        } else {
            try {
                $account = Mage::getModel('be2bill/merchandconfigurationaccount');
                foreach ($accountIds as $id) {
                    $account->load($id)->setActive(1)->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('be2bill')->__('%d compte(s) activé(s)', count($accountIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * MASS ACTION : Désactive le(s) compte(s)
     */
    public function massDisableAction()
    {
        $accountIds = $this->getRequest()->getParam('merchandaccount');

        if (!is_array($accountIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('be2bill')->__('Aucun compte sélectionné'));
        } else {
            try {
                $account = Mage::getModel('be2bill/merchandconfigurationaccount');
                foreach ($accountIds as $id) {
                    $account->load($id)->setActive(0)->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('be2bill')->__('%d compte(s) désactivé(s)', count($accountIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

}
