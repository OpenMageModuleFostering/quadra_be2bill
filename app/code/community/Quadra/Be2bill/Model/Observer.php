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
class Quadra_Be2bill_Model_Observer
{

    /**
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Payment_Model_Observer
     */
    public function checkPaymentMethod($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if ($quote) {
            $result = $observer->getEvent()->getResult();
            $methodInstance = $observer->getEvent()->getMethodInstance();
            if ($methodInstance->getCode() == 'be2bill') {
                $currency = $quote->getData('quote_currency_code');
                $country = $quote->getBillingAddress()->getData('country_id');
                $storeId = $quote->getData('store_id');
                $res = Mage::getModel('be2bill/merchandconfigurationaccount')->getAvailablePayments($currency, $country, $storeId);
                if ($res->getSize() > 0) {
                    $result->isAvailable = true;
                } else {
                    $result->isAvailable = false;
                }
            }
        }
        return $this;
    }

    /**
     *
     * @param int $fromDay
     * @param int $toDay
     * @return Mage_Sales_Model_Mysql4_Order_Collection
     */
    protected function getOrdersBySubDay($fromDay, $toDay)
    {
        $from = new Zend_Date(Mage::app()->getLocale()->storeTimeStamp());
        $from->subDay($fromDay);

        $to = new Zend_Date(Mage::app()->getLocale()->storeTimeStamp());
        $to->subDay($toDay);

        $statues = explode(",", Mage::getStoreConfig('payment/be2bill/statues_order_to_clean'));
        //print_r($statues);
        $orders = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('status', array("in" => $statues))
                ->addFieldTofilter('created_at', array("gteq" => $from->toString(Zend_Date::YEAR . "-" . Zend_Date::MONTH . "-" . Zend_Date::DAY . " " . Zend_Date::TIMES)))
                ->addFieldTofilter('created_at', array("lteq" => $to->toString(Zend_Date::YEAR . "-" . Zend_Date::MONTH . "-" . Zend_Date::DAY . " " . Zend_Date::TIMES)));

        return $orders;
    }

    /**
     * @deprecated deprecated since version 1.0.0
     * @return Quadra_Be2bill_Model_Observer
     */
    public function addAdminNotification()
    {
        $limit_day = (int)Mage::getStoreConfig('be2bill/be2bill_api/auth_validity_day');

        if (!$limit_day) {
            return $this;
        }

        $orders = $this->getOrdersBySubDay(5, 2);
        if ($orders->count()) {
            $inbox = Mage::getModel('adminnotification/inbox');
            $today = new Zend_Date(Mage::app()->getLocale()->storeTimeStamp());
            $formatDate = Zend_Date::YEAR . "-" . Zend_Date::MONTH . "-" . Zend_Date::DAY . " " . Zend_Date::TIMES;
            $ordersData[] = array(
                'severity' => 2,
                'date_added' => $today->toString($formatDate),
                'title' => Mage::helper('be2bill')->__("Vous avec %s commande(s) à capturer", $orders->count()),
                'description' => Mage::helper('be2bill')->__("Il y a des commandes en 'attende de capture be2bill' depuis %s jours", 5),
                'url' => Mage::getUrl('adminhtml/sales_order/'),
                'internal' => 1,
            );
            $inbox->parse(array_reverse($ordersData));
        }

        $orders = $this->getOrdersBySubDay(2, 1);
        if ($orders->count()) {
            $inbox = Mage::getModel('adminnotification/inbox');
            $today = new Zend_Date(Mage::app()->getLocale()->storeTimeStamp());
            $formatDate = Zend_Date::YEAR . "-" . Zend_Date::MONTH . "-" . Zend_Date::DAY . " " . Zend_Date::TIMES;
            $ordersData[] = array(
                'severity' => 1,
                'date_added' => $today->toString($formatDate),
                'title' => Mage::helper('be2bill')->__("Vous avec %s commande(s) à capturer", $orders->count()),
                'description' => Mage::helper('be2bill')->__("Il y a des commandes en 'attende de capture be2bill' depuis %s jours", 6),
                'url' => Mage::getUrl('adminhtml/sales_order/'),
                'internal' => 1,
            );
            $inbox->parse(array_reverse($ordersData));
        }
        return $this;
    }

    /**
     * Cancel orders in pending Be2bill because capture is limited to 10 days
     * @return Quadra_Be2bill_Model_Observer
     */
    public function cleanOrdersInPendingBe2bill()
    {
        $limit_day = Mage::getStoreConfig('payment/be2bill/auth_validity_day');

        if (!$limit_day) {
            return $this;
        }

        $orders = $this->getOrdersBySubDay(20, 10);

        foreach ($orders as $order) {
            try {
                //@var $order Mage_Sales_Model_Order
                if ($order->canCancel()) {
                    $order->cancel();
                    $order->addStatusToHistory(
                            $order->getStatus(),
                            // keep order status/state
                            Mage::helper('be2bill')->__("Commande annulée par le cron be2bill")
                    );
                } else {
                    $order->addStatusToHistory(
                            $order->getStatus(),
                            // keep order status/state
                            Mage::helper('be2bill')->__("L'annulation de la commande par le cron Be2bill a échoué")
                    );
                }
                $order->save();
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, "be2bill_error_cron.log");
                Mage::throwException($e->getMessage());
            }
        }
        return $this;
    }

    /**
     * Cancel orders stayed in pending because customer not validated be2bill form
     *
     */
    public function cancelOrdersInPending()
    {
        //Etape 1 : on recupere pour chaque comptes le nombre de jours pour l'annulation
        $col = Mage::getModel('be2bill/merchandconfigurationaccount')->getCollection();
        $tabLimitedTime = array();
        foreach ($col as $obj) {
            $tabLimitedTime[$obj->getData('id_b2b_merchand_configuration_account')] = $obj->getData('order_canceled_limited_time') != null ? $obj->getData('order_canceled_limited_time') : 0;
        }

        //Etape 2
        $collection = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('main_table.state', Mage_Sales_Model_Order::STATE_NEW)
                ->addFieldToFilter('op.method', 'be2bill');
        $select = $collection->getSelect();
        $select->joinLeft(array(
            'op' => Mage::getModel('sales/order_payment')->getResource()->getTable('sales/order_payment')), 'op.parent_id = main_table.entity_id', array('method', 'additional_information')
        );

        Mage::log((string)$collection->getSelect(), Zend_Log::DEBUG, "debug_clean_pending.log");

        // @var $order Mage_Sales_Model_Order
        foreach ($collection as $order) {
            $addInfo = unserialize($order->getData('additional_information'));
            $accountId = $addInfo['account_id'];
            $limitedTime = (int)$tabLimitedTime[$accountId];

            if ($limitedTime <= 0) {
                continue;
            }

            $store = Mage::app()->getStore($order->getStoreId());
            $currentStoreDate = Mage::app()->getLocale()->storeDate($store, null, true);
            $createdAtStoreDate = Mage::app()->getLocale()->storeDate($store, strtotime($order->getCreatedAt()), true);

            $difference = $currentStoreDate->sub($createdAtStoreDate);

            $measure = new Zend_Measure_Time($difference->toValue(), Zend_Measure_Time::SECOND);
            $measure->convertTo(Zend_Measure_Time::MINUTE);

            if ($limitedTime < $measure->getValue() && $order->canCancel()) {
                try {
                    $order->cancel();
                    $order->addStatusToHistory($order->getStatus(),
                            // keep order status/state
                            Mage::helper('be2bill')->__("Commande annulée automatique par le cron car la commande est en 'attente' depuis %d minutes", $limitedTime));
                    $order->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }

        return $this;
    }

    public function setRedirectUrl($observer)
    {
        $quote = $observer->getQuote();
        $redirectUrl = $quote->getPayment()->getOrderPlaceRedirectUrl();
        Mage::getSingleton('checkout/type_onepage')->getCheckout()->setRedirectUrl($redirectUrl);
        return $this;
    }

    /**
     * Appelé lors de la soumission d'un paiement reccurent
     * @return Quadra_Be2bill_Model_Observer
     */
    public function submitRecurringProfiles()
    {
        $profiles = Mage::getModel('sales/recurring_profile')->getCollection()
                ->addFieldToFilter("state", Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE)
                ->addFieldToFilter("method_code", $this->getBe2bill()->getCode());

        $helper = Mage::helper('be2bill');
        foreach ($profiles as $profile) {
            $toSubmit = $helper->isRecurringTosubmit($profile);
            if ($toSubmit) {
                $this->getBe2bill()->subscription($profile);
            }
        }
        return $this;
    }

    /**
     * CRON : Réalise la capture du paiement lorsque la date de création de la commande dépasse le nombre de jour
     * défini par le Marchand sur le compte de paiement
     *
     */
    public function submitDeferedOrders()
    {
        //Etape 1 : on recupere pour chaque compte le nombre de jour pour la capture
        $col = Mage::getModel('be2bill/merchandconfigurationaccountoptions')
                ->getCollection()
                ->addFieldToFilter('b2b_xml_option', 'defered')
                ->addFieldToFilter('active', '1');

        $tabAccountDays = array();
        foreach ($col as $obj) {
            $tabAccountDays[$obj->getData('id_b2b_merchand_configuration_account')] = unserialize($obj->getData('b2b_xml_option_extra'));
        }
        //print_r($tabAccountDays);
        //Etape 2 : on recupère la collection de commandes
        $colOrders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('status', array("in" => 'pending_be2bill'));
        $colOrders->getSelect()->join(
                        'sales_flat_order_payment', 'main_table.entity_id = sales_flat_order_payment.parent_id', array('additional_information')
                )
                ->where('additional_information LIKE "%defered-days%"');
        //echo $colOrders->getSelect();

        foreach ($colOrders as $_order) {
            $addInfo = unserialize($_order->getData('additional_information'));
            $days = $tabAccountDays[$addInfo['account_id']];

            $from = new Zend_Date(Mage::app()->getLocale()->storeTimeStamp());
            $from->subDay($days);
            //echo $from->toString(Zend_Date::YEAR . "-" . Zend_Date::MONTH . "-" . Zend_Date::DAY . " " . Zend_Date::TIMES);
            //echo $orderDate->toString(Zend_Date::YEAR . "-" . Zend_Date::MONTH . "-" . Zend_Date::DAY . " " . Zend_Date::TIMES);
            $orderDate = new Zend_Date($_order->getData('created_at'));
            if ($orderDate <= $from) {
                $order = Mage::getModel('sales/order')->load($_order->getData('entity_id'));
                $order->getPayment()->capture(null); // Capturing the payment
                $order->save();
            }
        }
    }

    /**
     * @return Quadra_Be2bill_Model_Standard
     */
    public function getBe2bill()
    {
        return Mage::getSingleton('be2bill/method_paymentMethods');
    }

    /**
     * Mirakl
     * Capture marketplace products
     *
     * @param Varien_Event_Observer $observer
     * @return Quadra_Be2bill_Model_Observer
     */
    public function captureMkpProducts($observer)
    {
        $debug = debug_backtrace(false);
        foreach ($debug as &$data) {
            unset($data['object']);
            unset($data['args']);
        }

        $body = $observer->getEvent()->getBody();
        $data = json_decode($body, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            // Data sent in XML
            $xml = new SimpleXMLElement($body, LIBXML_NOCDATA);
            $json = json_encode($xml);
            $data = json_decode($json, true);
        }

        foreach ($data as $orders) {
            if (array_key_exists('amount', $orders)) {
                // Miss a level when data sent in XML
                $orders = array($orders);
            }

            foreach ($orders as $orderData) {
                $order = Mage::getModel('sales/order')->load($orderData['order_commercial_id'], 'increment_id');
                if ($order->getId()) {
                    try {
                        $payment = $order->getPayment();
                        $methodInstance = $payment->getMethodInstance();
                        $res = $methodInstance->captureMkpProducts($payment, $orderData['amount'], $orderData['order_id']);
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }

                    $status = (isset($res) && $res->getResponseIsSuccess()) ? 'OK' : 'REFUSED';

                    Mage::dispatchEvent('mirakl_trigger_order_debit', array(
                        'order_id' => $order->getId(),
                        'remote_id' => $orderData['order_id'],
                        'status' => $status
                    ));
                }
            }
        }

        return $this;
    }

    /**
     * Mirakl
     * Refund marketplace products
     *
     * @param Varien_Event_Observer $observer
     * @return Quadra_Be2bill_Model_Observer
     */
    public function refundMkpProducts($observer)
    {
        $debug = debug_backtrace(false);
        foreach ($debug as &$data) {
            unset($data['object']);
            unset($data['args']);
        }

        $body = $observer->getEvent()->getBody();
        $data = json_decode($body, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            // Data sent in XML
            $xml = new SimpleXMLElement($body, LIBXML_NOCDATA);
            $json = json_encode($xml);
            $data = json_decode($json, true);
        }

        foreach ($data as $orders) {
            if (array_key_exists('amount', $orders)) {
                // Miss a level when data sent in XML
                $orders = array($orders);
            }

            foreach ($orders as $orderData) {
                $order = Mage::getModel('sales/order')->load($orderData['order_commercial_id'], 'increment_id');
                if ($order->getId()) {
                    $amount = 0;
                    foreach ($orderData['order_lines'] as $orderLines) {
                        foreach ($orderLines as $line) {
                            foreach ($line['refunds'] as $refunds) {
                                foreach ($refunds as $refund) {
                                    $amount += $refund['amount'];
                                }
                            }
                        }
                    }
                    
                    try {
                        $payment = $order->getPayment();
                        $methodInstance = $payment->getMethodInstance();
                        $res = $methodInstance->refundMkpProducts($payment, $amount, $orderData['order_id']);
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }

                    $status = (isset($res) && $res->getResponseIsSuccess()) ? 'OK' : 'REFUSED';

                    Mage::dispatchEvent('mirakl_trigger_order_refund', array(
                        'order_id' => $order->getId(),
                        'remote_id' => $orderData['order_id'],
                        'status' => $status
                    ));
                }
            }
        }

        return $this;
    }

}
