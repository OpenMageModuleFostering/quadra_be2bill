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
class Quadra_Be2bill_Helper_Data extends Mage_Core_Helper_Data
{

    protected $_method = 'be2bill';

    /**
     * Retourne le code de la méthode de paiement (générique)
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Formate la collection d'options dans un tableau
     * @param $collection (merchand option)
     * @return array
     */
    public function getFormattedCollectionOptionsToArray($collection)
    {
        $result = array();
        foreach ($collection as $option) {
            $result[$option->getData('b2b_xml_option')] = array(
                'min_amount' => $option->getData('min_amount'),
                'max_amount' => $option->getData('max_amount'),
                'option_name' => $option->getData('option_name'),
                'b2b_xml_option_extra' => unserialize($option->getData('b2b_xml_option_extra')),
                'active' => (int)$option->getData('active'),
                'front_label' => $option->getData('front_label')
            );
        }
        return $result;
    }

    /**
     * Verifie que la date de la carte n'est pas périmée
     * @param string $alias
     * @return boolean
     */
    public function checkIfCcExpDateIsValid($alias)
    {
        $expDate = new Zend_Date($alias->getData('date_end'));

        $expYear = $expDate->getYear()->toString("YY");
        $expMonth = $expDate->getMonth()->toString("MM");

        $today = new Zend_Date(Mage::app()->getLocale()->storeTimeStamp());

        $currentYear = (int)$today->getYear()->toString("YY");
        $currentMonth = (int)$today->getMonth()->toString("MM");

        if ($currentYear > (int)$expYear) {
            return false;
        }

        if ($currentYear == (int)$expYear && $currentMonth > (int)$expMonth) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return boolean
     */
    public function isBe2billServer()
    {
        $allowedRangeIps = explode(",", Mage::getStoreConfig('payment/be2bill/allow_range_ips'));
        /* @var $_helperIp Quadra_Be2bill_Helper_Ip */
        $_helperIp = Mage::helper('be2bill/ip');
        foreach ($allowedRangeIps as $range) {
            list($ip, $mask) = explode("/", $range);
            if ($_helperIp->checkIfRemoteIpIsInRange($ip, $mask) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Formate le montant pour be2bill (centimes)
     *
     * @param float $amount
     * @return number
     */
    public function formatAmount($amount)
    {
        return round($amount, 2) * 100;
    }

    /**
     * Get template for button in order review page if be2bill method was selected
     *
     * @param string $name template name
     * @param string $block buttons block name
     * @return string
     */
    public function getReviewButtonTemplate($name, $block)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();

        if ($quote) {
            $payment = $quote->getPayment();
            if ($payment && $payment->getAdditionalInformation('mode') == 'form-iframe' && $payment->getAdditionalInformation('use_oneclick') == 'no') {
                return $name;
            }
        }

        if ($blockObject = Mage::getSingleton('core/layout')->getBlock($block)) {
            return $blockObject->getTemplate();
        }

        return '';
    }

    /**
     * Réaffecte la commande non valide à un nouveau panier
     * @param int $incrementId (id order)
     */
    public function reAddToCart($incrementId)
    {
        $cart = Mage::getSingleton('checkout/cart');
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);

        if ($order->getId()) {
            $items = $order->getItemsCollection();
            foreach ($items as $item) {
                try {
                    $cart->addOrderItem($item);
                } catch (Mage_Core_Exception $e) {
                    if (Mage::getSingleton('checkout/session')->getUseNotice(true)) {
                        Mage::getSingleton('checkout/session')->addNotice($e->getMessage());
                    } else {
                        Mage::getSingleton('checkout/session')->addError($e->getMessage());
                    }
                } catch (Exception $e) {
                    Mage::getSingleton('checkout/session')->addException($e, Mage::helper('checkout')->__('Cannot add the item to shopping cart.'));
                }
            }
        }

        $cart->save();
    }

    /**
     * @desc methode permettant de calculer la liste des echeances de paiement, ainsi que le montant de chacune des echeances
     * @param float $fAmount montant de la commande en centime de devise
     * @param integer $iNTimes nombre d'echeance totale
     * @param Date $startDate Date de dévut de l'échéancier
     * @return array liste des echeances
     */
    public function getSchedule($fAmount /* en centime de devise */, $iNTimes, $startDate = null)
    {

        $oToday = new DateTime();
        if ($startDate != null) {
            $oToday = $startDate;
        }

        /*
         * CALCUL DU MONTANT DES ECHEANCES
         */
        // division du montant total par le nombre d'echeance
        $fSplitedAmount = $fAmount / $iNTimes;

        // si montant non entier
        if (!is_int($fSplitedAmount)) {
            // 1ere echeance
            $fFirstAmount = (int)$fSplitedAmount + $iNTimes * ($fSplitedAmount - (int)$fSplitedAmount);
            // toutes les autres echeances
            $iNextAmounts = (int)$fSplitedAmount;
        } else {
            $fFirstAmount = $iNextAmounts = $fSplitedAmount;
        }

        /*
         * CALCUL DES DATES DES ECHEANCES
         */
        for ($i = 0; $i < $iNTimes; $i++) {
            switch ($i) {
                case 0:
                    // date du jour
                    $aSchedule[$oToday->format('Y-m-d')] = $fFirstAmount;
                    break;
                case 1:
                case 2:
                    // date du jour
                    $sYear = $oToday->format('Y');
                    $sMonth = $oToday->format('n');
                    $sDay = $oToday->format('d');

                    // calcul
                    $sYear += floor($i / 12);
                    $i = $i % 12;
                    $sMonth += $i;
                    if ($sMonth > 12) {
                        $sYear ++;
                        $sMonth = $sMonth % 12;
                        if ($sMonth === 0) {
                            $sMonth = 12;
                        }
                    }

                    if (!checkdate($sMonth, $sDay, $sYear)) {
                        $oNewDate = DateTime::createFromFormat('Y-n-j', $sYear . '-' . $sMonth . '-1');
                        $oNewDate->modify('last day of');
                    } else {
                        $oNewDate = DateTime::createFromFormat('Y-n-d', $sYear . '-' . $sMonth . '-' . $sDay);
                    }
                    $oNewDate->setTime($oToday->format('H'), $oToday->format('i'), $oToday->format('s'));

                    $aSchedule[$oNewDate->format('Y-m-d')] = $iNextAmounts;
                    break;
                case 3:
                    // date du jour
                    $aSchedule[$oToday->modify('+89 day')->format('Y-m-d')] = $iNextAmounts;
                    break;
            }
        }

        return $aSchedule;
    }

    /**
     * Verifie si la soumission du P R est possible
     * @param Mage_Sales_Model_Recurring_Profile $profile
     * @return boolean
     */
    public function isRecurringTosubmit(Mage_Sales_Model_Recurring_Profile $profile)
    {
        $orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('entity_id', array('in' => $profile->getChildOrderIds()));
        $startDate = new Zend_Date($profile->getStartDatetime());
        $todayDate = new Zend_Date();

        if ($startDate->compare($todayDate) <= 0 && $orders->count() < 1) {
            return true;
        }

        if ($orders->count() > 0) {
            $currentNbCycle = (int)$orders->count();
            $maxCycles = (int)$profile->getPeriodMaxCycles();
            $periodFrequency = (int)$profile->getPeriodFrequency();

            if (!empty($maxCycles) && $currentNbCycle == ($periodFrequency * $maxCycles)) {
                return false;
            }

            $lastOrder = $orders->getLastItem();
            $lastDate = new Zend_Date($lastOrder->getCreatedAt());

            switch ($profile->getPeriodUnit()) {
                case Mage_Sales_Model_Recurring_Profile::PERIOD_UNIT_MONTH:
                    if ($lastDate->addMonth($periodFrequency)->getDate()->compare($todayDate->getDate()) <= 0) {
                        return true;
                    }
                    break;
                case Mage_Sales_Model_Recurring_Profile::PERIOD_UNIT_DAY:
                    if ($lastDate->addDay($periodFrequency)->getDate()->compare($todayDate->getDate()) <= 0) {
                        return true;
                    }
                    break;
                case Mage_Sales_Model_Recurring_Profile::PERIOD_UNIT_SEMI_MONTH:
                    if ($lastDate->addMonth(0.5 * $periodFrequency)->getDate()->compare($todayDate->getDate()) <= 0) {
                        return true;
                    }
                    break;
                case Mage_Sales_Model_Recurring_Profile::PERIOD_UNIT_WEEK:
                    if ($lastDate->addWeek($periodFrequency)->getDate()->compare($todayDate->getDate()) <= 0) {
                        return true;
                    }
                    break;
                case Mage_Sales_Model_Recurring_Profile::PERIOD_UNIT_YEAR:
                    if ($lastDate->addYear($periodFrequency)->getDate()->compare($todayDate->getDate()) <= 0) {
                        return true;
                    }
                    break;
                default:
                    break;
            }
        }

        return false;
    }

    public function isMiraklInstalledAndActive()
    {
        return Mage::helper('core')->isModuleEnabled('Mirakl_Core');
    }

	public function getShippingMethodsSelect($iProductId, $iAddressId, $iQty){
		
		$oProduct = Mage::getModel('catalog/product')->load($iProductId);
		$oShippingAddress = Mage::getModel('customer/address')->load($iAddressId);
		
		// si mirakl actif et produit marketplace ?
		if (Mage::helper('be2bill')->isMiraklInstalledAndActive() && $oProduct->getMiraklOfferId()){
				
			// charger les datas
			$aOffersWithQty = array($oProduct->getMiraklOfferId() => $iQty);
			$aShopsWithShippingType = array($oProduct->getMiraklShopId() => 'STD');

		 	$oShippingFees = Mage::helper('mirakl_api/shipping')->getShippingFees(
           		Mage::getModel('customer/address')->load(Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping())->getData('country_id'),
           		$aOffersWithQty,
           		$aShopsWithShippingType
	        );
			
			// si produit simple
			// pas d'one click pour les produits groupés
			if(!$oShippingFees[0]){
				return false;
			}
			
			if($oShippingFees[0]->getData('error_code'))
				return false;
			
			$_shippingTypes = $oShippingFees[0]->getData('offers')[0]->getData('shipping_types');
			
			$options = array();
			foreach ($_shippingTypes as $_shippingType){
				$options[] = array('value' => $_shippingType['code'].'|'.$_shippingType['line_only_shipping_price'].'|'.$_shippingType['label'], 'label' => $_shippingType['label'] . ' - ' . Mage::helper('core')->currency($_shippingType['line_only_shipping_price'], true, false));
			}
		}
		// si produit operateur
		else{
			// creation d'une NOUVELLE QUOTE
			$oTmpQuote = Mage::getModel('sales/quote');
			
			// assignation du client et du store
			$oTmpQuote
				->assignCustomer(Mage::getSingleton('customer/session')->getCustomer())
				->setStoreId(Mage::app()->getStore()->getId());
				
			$oTmpQuote->save();
			
			// creation de la quote item
			$quoteItem = Mage::getModel('sales/quote_item')
				->setProduct($oProduct)
				->setQty($iQty);
			
			// ajout du produit à la quote
			$oTmpQuote->addItem($quoteItem);
			
			// ajout de l'adresse par defaut de livraison
			// affectation des donnée pour création de la quote
		    $aShippingAddress = array(
		        'firstname' => $oShippingAddress->getData('firstname'),
		        'lastname' => $oShippingAddress->getData('lastname'),
		        'street' => $oShippingAddress->getData('street'),
		        'city' => $oShippingAddress->getData('city'),
		        'postcode'=> $oShippingAddress->getData('postcode'),
		        'telephone' => $oShippingAddress->getData('telephone'),
		        'country_id' => $oShippingAddress->getData('country_id'),
		        'region_id' => $oShippingAddress->getData('region_id')
		    );
		   
		    $shippingAddress = $oTmpQuote->getShippingAddress()->addData($aShippingAddress)->save();
			$shippingAddress->setCollectShippingRates(true)->collectShippingRates();
			$groupedRates = $shippingAddress->getShippingRatesCollection();
			
			$options = array();
			// boucle sur les methodes de livraison
			foreach ($groupedRates as $carrierCode => $rates ) {
				
				$carrierName = $carrierCode;
				if (!is_null(Mage::getStoreConfig('carriers/'.$carrierCode.'/title'))) {
					$carrierName = Mage::getStoreConfig('carriers/'.$carrierCode.'/title');
				}
				
				// affectation de la methode de livraison à la liste d'option
				$options[] = array('value' => $rates->getData('code'), 'label' => $rates->getData('carrier_title') . ' - ' . Mage::helper('core')->currency($rates->getData('price'), true, false));
	        }
			
			// suppression de la quote
			$oTmpQuote->delete();
			unset($oTmpQuote);
		}
		
		// création du select
        $select =  Mage::app()->getLayout()->createBlock('core/html_select')
	        ->setName('shipping_method_code')
	        ->setId('shipping-method-select')
	        ->setClass('select required')
	        ->setValue()
	        ->setOptions($options);
		
		unset($options);
        return $select->getHtml();
	}

}
