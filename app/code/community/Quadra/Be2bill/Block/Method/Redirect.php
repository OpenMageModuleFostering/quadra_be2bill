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
class Quadra_Be2bill_Block_Method_Redirect extends Mage_Core_Block_Abstract
{

    protected $methodName = '';

    /**
     * Return l'html du formulaire avec tout les paramètres du moyen de paiement générique selectionné
     * par le client (utile pour be2bill)
     * @return string
     */
    protected function _toHtml()
    { 	
        $method = Mage::getSingleton("be2bill/method_paymentMethods");
		$action = $method->getRedirectUrl();
		
		if($action == null) {
			echo Mage::helper('be2bill')->__('Impossible de se connecter aux serveurs de Be2bill');
		}
		else {
			$form = new Varien_Data_Form();
			
			$form->setAction($action)
			->setId('be2bill_checkout')
			->setName('be2bill_checkout')->setMethod('POST')
			->setUseContainer(true);
			
			$method->getCheckoutFormFields();
			$nb = 0;
			foreach ($method->getCheckoutFormFields() as $field => $value) {
				if (is_array($value)) {
					foreach ($value as $key => $val) {
						if(is_array($val)){
							foreach ($val as $key2 => $val2) {
								$form->addField($field . "[" . $key . "]" . "[" . $key2 . "]", 'hidden', array('name' => $field . "[" . $key . "]". "[" . $key2 . "]", 'value' => $val2));
							}
						}
						else{
							$form->addField($field . "[" . $key . "]", 'hidden', array('name' => $field . "[" . $key . "]", 'value' => $val));
						}
						 
					}
				} else {
					$form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
				}
			}
			
			$html = '<html><body>';
			$html .= $this->__('Vous allez être redirigé vers le serveur de paiement dans quelques secondes...');
			$html .= $form->toHtml();
			$html .= '<script type="text/javascript">document.getElementById("be2bill_checkout").submit();</script>';
			$html .= '</body></html>';
			
			return $html;
		}
        
    }

}
