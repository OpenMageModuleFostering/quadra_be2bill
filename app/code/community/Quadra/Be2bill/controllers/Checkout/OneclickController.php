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
 * @copyright 1997-2015 Quadra Informatique
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
class Quadra_Be2bill_Checkout_OneclickController extends Mage_Core_Controller_Front_Action
{

    /**
     * Action list where need check enabled cookie
     *
     * @var array
     */
    protected $_cookieCheckActions = array('add');
    protected $_merchandAccountId;
    protected $_paymentMethod;
	protected $_alias;
	

    /**
     * Retrieve shopping cart model object
     *
     * @return Mage_Checkout_Model_Cart
     */
    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    /**
     * Get checkout session model instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current active quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    /**
     * Set back redirect url to response
     *
     * @return Mage_Checkout_CartController
     */
    protected function _goBack()
    {
        $redirectUrl = $this->getRequest()->getServer('HTTP_REFERER');
        $this->getResponse()->setRedirect($redirectUrl);
        return $this;
    }

    /**
     * Initialize product instance from request data
     *
     * @return Mage_Catalog_Model_Product || false
     */
    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($productId);
            if ($product->getId()) {
                return $product;
            }
        }
        return false;
    }

    /**
     * Initialise la commande
     * @param string $alias
     * @param int $merchandAcc
     * @return Mage_Core_Model_Abstract
     */
    protected function _initOrder($alias, $merchandAcc, $shippingMethodCode)
    {

        $cart = $this->_getCart();
        if ($cart->getQuote()->getItemsCount()) {
            $cart->init();
            $cart->save();
        }

        $checkout = Mage::getSingleton('checkout/type_onepage');
        $checkout->initCheckout();
        $checkout->getQuote()->collectTotals()->save();

        // set addresses
        $addressId = $this->getRequest()->getParam('shipping_address_id', 0);
		
        if ($addressId > 0) {
            $checkout->saveBilling(array(), $addressId);
            $checkout->saveShipping(array(), $addressId);
        }

        // get shipping address
        $shippingAddress = $checkout->getQuote()->getShippingAddress();
		
        $checkout->saveShippingMethod($shippingMethodCode);
        $checkout->getQuote()
                ->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();

        $info = $merchandAcc->getData('id_b2b_merchand_configuration_account') . '.' . $merchandAcc->getB2bXmlAccountTypeCode() . '.' . $this->_paymentMethod;
        
        $addInfo = array('oneclick' => 'use_oneclick');

        $paymentData = array(
            'method' => 'be2bill',
            'be2bill_method' => $info,
            $info => $addInfo,
            'be2bill_method_label' => 'Paiement OneClick'
        );
 
        $checkout->savePayment($paymentData);

        return $checkout;
    }

    /**
     * Create order
     * @param string $alias
     * @param int $merchandAcc
     * @return mixed
     */
    protected function _createOrder($alias, $merchandAcc, $shippingMethodCode)
    {
        $checkout = $this->_initOrder($alias, $merchandAcc, $shippingMethodCode);
        if ($checkout != null) {
            try {
                $checkout->saveOrder();
                $redirectUrl = Mage::getUrl('be2bill/payments/oneclick', array('_secure' => true));
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::helper('checkout')->sendPaymentFailedEmail($checkout->getQuote(), $e->getMessage());
                Mage::getSingleton('catalog/session')->addError($e->getMessage());
                $redirectUrl = $this->getRequest()->getServer('HTTP_REFERER');
            }
            return $redirectUrl;
        }
        return $this->getRequest()->getServer('HTTP_REFERER');
    }

	/**
	 * retourne l'alias, l'id du compte et le code operation
	 * @param sting $aAliasParam
	 * @return void
	 */
	protected function splitAliasDatas($sAliasParam){
		
		$aAliasParam = explode('|', $sAliasParam);
		
		$this->_alias = $aAliasParam[0];
		$this->_merchandAccountId = $aAliasParam[1];
		$this->_paymentMethod = $aAliasParam[2];
		
	}
	
	/**
	 * retourne la méthode et montant de livraison Mirakl
	 * @param sting $aAliasParam
	 * @return array
	 */
	protected function splitShippingDatas($sShippingParam){
		
		$aShippingParam = explode('|', $sShippingParam);
		
		$aTmp['shipping_method_code'] = $aShippingParam[0];
		$aTmp['shipping_method_amount'] = $aShippingParam[1];
		$aTmp['shipping_method_label'] = $aShippingParam[2];
		
		return $aTmp;
	}

    /**
     * Order a product in one click
     * @return redirection
     */
    public function orderProductAction()
    {
        $this->_getSession()->clear();
        $cart = $this->_getCart();
        $params = $this->getRequest()->getParams();
		
		// creation de la quote
        $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore()->getId())->save();
		
        // split alias
        $this->splitAliasDatas($params['alias_code']);
        
        if ($this->_alias == null) {
            Mage::getSingleton('catalog/session')->addError(Mage::helper('be2bill')->__('Veuillez réessayer ultérieurement.'));
            $this->_goBack();
            return;
        }
		
		// recuperer moyen de paiement
        $merchandAcc = Mage::getModel('be2bill/merchandconfigurationaccount')->load($this->_merchandAccountId);

        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                        array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');
			
			// si mirakl actif et produit mkp ?
			if (Mage::helper('be2bill')->isMiraklInstalledAndActive() && $product->getMiraklOfferId()) {
				
				/*
				 * Vérifier le stock 
				 */
				$aOffersWithQty = array($product->getMiraklOfferId() => $params['qty']);
			 	$oShippingFees = Mage::helper('mirakl_api/shipping')->getShippingFees(
	           		Mage::getModel('customer/address')->load(Mage::getSingleton('customer/session')->getCustomer()->getDefaultShipping())->getData('country_id'),
	           		$aOffersWithQty
		        );
				
				if($params['qty'] > $oShippingFees[0]->getData('offers')[0]->getData('quantity')){
					Mage::throwException(Mage::helper('cataloginventory')->__('Not all products are available in the requested quantity'));
	                $this->_goBack();
	                return;
				}
				
				// creation de la quote item
				$quoteItem = Mage::getModel('sales/quote_item')
					->setProduct($product)
					->setQty($params['qty']);
					
				// ajout du produit à la quote
				$quote->addItem($quoteItem);
				
				// mise à jour des frais de livraison
				$aMiraklShipping = $this->splitShippingDatas($params['shipping_method_code']);
				
				$quote
					->setData('mirakl_base_shipping_fee', $aMiraklShipping['shipping_method_amount'])
					->setData('mirakl_shipping_fee', $aMiraklShipping['shipping_method_amount'])
					->setBaseShippingAmount($aMiraklShipping['shipping_method_amount'])
					->setShippingAmount($aMiraklShipping['shipping_method_amount'])
					->save();
				
				// recuperation de l'objet adresse pour definir la livraison magento
				$address = $quote->getShippingAddress();
				
				$address
					->setShippingAmount($aMiraklShipping['shipping_method_amount'])
					->setBaseShippingAmount($aMiraklShipping['shipping_method_amount'])
					->setShippingMethod('freeshipping_freeshipping')
					->setShippingCode('freeshipping')
					->setCollectShippingRates(true)
					->save();
					
				$rate = Mage::getModel('sales/quote_address_rate')
					->setAddressId($address->getId())
					->setCarrier('freeshipping')
					->setCarrierTitle('Mirakl')
					->setCode('freeshipping_freeshipping')
					->setMethod('freeshipping')
					->setPrice($aMiraklShipping['shipping_method_amount'])
					->setMethodTitle('Mirakl')
					->save();
					
				$shippingMethodCode = 'freeshipping_freeshipping';
					 
				$quote->setBaseShippingAmount($aMiraklShipping['shipping_method_amount']);
				$quote->setShippingAmount($aMiraklShipping['shipping_method_amount']);
				
				// recuperation de l'objet adresse pour definir la livraison magento par defaut
				$address = $quote->getShippingAddress();
				$address
					->setShippingMethod('freeshipping_freeshipping')
					->setCollectShippingRates(true)
					->save();
				
				$quote->collectTotals();
        		$quote->save();
			}
			else{
				// verification de la disponibilité du produit
	            if (!$product) {
	                $this->_goBack();
	                return;
	            }
				
				$shippingMethodCode = $params['shipping_method_code'];
				
				// creation de la quote item
				$quoteItem = Mage::getModel('sales/quote_item')
					->setProduct($product)
					->setQty($params['qty']);
					
				// ajout du produit à la quote
				$quote->addItem($quoteItem);
				
				$quote->collectTotals();
        		$quote->save();
			}

	        $cart->setQuote($quote);
			
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();
            $this->_getSession()->setCartWasUpdated(true);
			
        	if (Mage::helper('be2bill')->isMiraklInstalledAndActive() && $product->getMiraklOfferId()) {
				// recuperation du last quote item pour mettre à jour livraison
				$items = $quote->getAllItems();
				$lastItem = null;
				$max = 0;
		        foreach ($items as $item){
		            if ($item->getId() > $max) {
		                $max = $item->getId();
		                $lastItem = $item;
		            }
		        }
				
				if($lastItem){
					// mise à jour specifique a mirakl
	        		$product = $lastItem->getProduct();
					
					$lastItem->setData('mirakl_offer_id', $product->getData('mirakl_offer_id'));
					$lastItem->setData('mirakl_shop_id', $product->getData('mirakl_shop_id'));
					$lastItem->setData('mirakl_shop_name', $product->getData('mirakl_shop_id'));
					$lastItem->setData('mirakl_shipping_type', $aMiraklShipping['shipping_method_code']);
					$lastItem->setData('mirakl_shipping_type_label', $aMiraklShipping['shipping_method_label']);
					$lastItem->setData('mirakl_base_shipping_fee', $aMiraklShipping['shipping_method_amount']);
					$lastItem->setData('mirakl_shipping_fee', $aMiraklShipping['shipping_method_amount']);
					$lastItem->save();
				}
			}

            /*
             * @todo remove wishlist observer processAddToCart
             */
            Mage::dispatchEvent('checkout_cart_add_product_complete', array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()));

			// conditions pour affichage de la page intermediaire (échancier + CVV)
            if ($this->_paymentMethod == 'ntimes' || $merchandAcc->useOneClickCVV()) {
                $checkout = $this->_initOrder($this->_alias, $merchandAcc, $shippingMethodCode);
                $this->getResponse()->setRedirect(Mage::getUrl('be2bill/checkout_oneclick/info', array('s_m' => $params['shipping_method_code'], 'quote_id' => $checkout->getQuote()->getId(), '_secure' => true)));
            }
            else {
                $redirectUrl = $this->_createOrder($this->_alias, $merchandAcc, $shippingMethodCode);
                $this->getResponse()->setRedirect($redirectUrl);
            }
			
        } catch (Mage_Core_Exception $e) {
            if ($this->_getSession()->getUseNotice(true)) {
                $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
            }
            else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->_getSession()->addError(Mage::helper('core')->escapeHtml($message));
                }
            }

            $url = $this->_getSession()->getRedirectUrl(true);
            if ($url) {
                $this->getResponse()->setRedirect($url);
            } else {
                $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
            }
        } catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('Cannot add the item to shopping cart.'));
            Mage::logException($e);
            $this->_goBack();
        }
    }

    public function infoAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }

    public function infoPostAction()
    {
        $checkout = Mage::getSingleton('checkout/type_onepage');
        $params = $this->getRequest()->getParams();

        if(isset($params['payment']['cvv_oneclick']) && $params['payment']['cvv_oneclick'] != ''){
        	$checkout->getQuote()->getPayment()->setAdditionalInformation('cvv_oneclick',trim($params['payment']['cvv_oneclick']) );
        }
        else{
        	$checkout->getQuote()->getPayment()->setAdditionalInformation('cvv_oneclick',null );
        }

        $checkout->getQuote()
                ->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();

        try {
            $checkout->saveOrder();
            $paymentMethod = explode('_', $checkout->getQuote()->getPayment()->getMethod());
            $redirectUrl = Mage::getUrl('be2bill/payments/oneclick', array('_secure' => true));
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($checkout->getQuote(), $e->getMessage());
            Mage::getSingleton('catalog/session')->addError($e->getMessage());
            $redirectUrl = $this->getRequest()->getServer('HTTP_REFERER');
        }

        $this->getResponse()->setRedirect($redirectUrl);
    }

    /**
     * Sauvegarde la méthode de livraison
     */
    public function saveShippingMethodAction()
    {
        $shippingMethodCode = $this->getRequest()->getParam('estimate_method');

        $checkout = Mage::getSingleton('checkout/type_onepage');
		
		$quote = $checkout->getQuote();
		
		if (Mage::helper('be2bill')->isMiraklInstalledAndActive() && $quote->getAllItems()[0]->getMiraklOfferId()){
			// mise à jour des frais de livraison
			$aMiraklShipping = $this->splitShippingDatas($shippingMethodCode);
			$shippingMethodCode = 'freeshipping_freeshipping';
			
			// QUOTE
			$quote
				->setData('mirakl_base_shipping_fee', $aMiraklShipping['shipping_method_amount'])
				->setData('mirakl_shipping_fee', $aMiraklShipping['shipping_method_amount'])
				->setBaseShippingAmount($aMiraklShipping['shipping_method_amount'])
				->setShippingAmount($aMiraklShipping['shipping_method_amount'])
				->save();
				
			// ADDRESS
			// recuperation de l'objet adresse pour definir la livraison magento
			$address = $quote->getShippingAddress();
			$address
				->setShippingAmount($aMiraklShipping['shipping_method_amount'])
				->setBaseShippingAmount($aMiraklShipping['shipping_method_amount'])
				->setShippingMethod('freeshipping_freeshipping')
				->setShippingCode('freeshipping')
				->setCollectShippingRates(true)
				->save();
			
			// RATE
			$rate = Mage::getModel('sales/quote_address_rate')
				->setAddressId($address->getId())
				->setCarrier('freeshipping')
				->setCarrierTitle('Mirakl')
				->setCode('freeshipping_freeshipping')
				->setMethod('freeshipping')
				->setPrice($aMiraklShipping['shipping_method_amount'])
				->setMethodTitle('Mirakl')
				->save();
				
			// recuperation de l'objet adresse pour definir la livraison magento par defaut
			$quote->collectTotals();
    		$quote->save();
		}
		
        $checkout->saveShippingMethod($shippingMethodCode);
        $checkout->setQuote($quote)
				->getQuote()
                ->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();
				
        $block = $this->getLayout()->createBlock('be2bill/checkout_oneclick_info', 'be2bill.checkout.oneclick.info.review')
                ->setTemplate('be2bill/checkout/oneclick/info/review.phtml');
        $block->addItemRender('simple', 'checkout/cart_item_renderer', 'be2bill/checkout/cart/item/default.phtml');
        $block->addItemRender('grouped', 'checkout/cart_item_renderer_grouped', 'be2bill/checkout/cart/item/default.phtml');
        $block->addItemRender('configurable', 'checkout/cart_item_renderer_configurable', 'be2bill/checkout/cart/item/default.phtml');

        $totals = $this->getLayout()->createBlock('checkout/cart_totals', 'checkout.cart.totals')
                ->setTemplate('be2bill/checkout/cart/totals.phtml');

        $block->append($totals, 'totals');

        return $this->getResponse()->setBody($block->toHtml());
    }

	/**
	 * methode ajax permettant de retourner la mise à jour des frais de livraison
	 * /be2bill/checkout_oneclick/updateShippingFee
	 */
	public function updateShippingFeeAction(){
		
		$result['html'] = Mage::helper('be2bill/data')->getShippingMethodsSelect(
			$this->getRequest()->getParam('productId'),
			$this->getRequest()->getParam('addressId'),
			$this->getRequest()->getParam('qty')
		);
		
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	}

}
