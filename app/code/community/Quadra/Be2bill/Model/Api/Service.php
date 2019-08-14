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
class Quadra_Be2bill_Model_Api_Service
{

    /**
     * @var Quadra_Be2bill_Model_Abstract
     */
    protected $_methodInstance = null;

    /**
     *
     * @var Zend_Http_Client
     */
    protected $_client = null;

    /**
     *
     * @var array
     */
    protected $_codeErrorToRetry = array();

    public function __construct($args)
    {
        $this->_methodInstance = $args['methodInstance'];
        $this->_codeErrorToRetry = array('5001', '5003');
    }

    /**
     * @return Quadra_Be2bill_Model_Abstract
     */
    public function isTestMode()
    {
        return $this->getConfigData('test');
    }

    /**
     * Get client HTTP
     * @return Zend_Http_Client
     */
    public function getClient()
    {
        if (is_null($this->_client)) {
            //adapter options
            $config = array('curloptions' => array(
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => true),
            );
            try {
                //innitialize http client and adapter curl
                $adapter = Mage::getSingleton('be2bill/api_http_client_adapter_curl');
                ;
                $this->_client = new Zend_Http_Client();
                $this->_client->setAdapter($adapter);
                $adapter->setConfig($config);
            } catch (Exception $e) {
                Mage::throwException($e);
            }
        }

        return $this->_client;
    }

    /**
     * Send a request to Be2bill
     * @param string $methodToCall
     * @param array $params
     * @param string|null $uri
     *
     * @return Quadra_Be2bill_Model_Api_Response
     */
    public function send($methodToCall, $params, $uri = null)
    {
        if (is_null($uri)) {
            $uri[] = $this->getRestUrl();
            $uri[] = $this->getRestUrlHighDispo();
        } elseif (!is_array($uri)) {
            $uri = array($uri);
        }
        $this->getClient()->setParameterPost('method', $methodToCall);
        $this->getClient()->setParameterPost('params', $params);
        foreach ($uri as $restUrl) {
            $this->getClient()->setUri($restUrl);
            $response = $this->getClient()->request(Zend_Http_Client::POST);
            if ($response->getStatus() == 200) {
                $data = json_decode($response->getBody(), true);
                $response = Mage::getModel('be2bill/api_response')->setData($data);
                if (!in_array($response->getExecCode(), $this->_codeErrorToRetry)) {
                    return $response;
                }
            }
        }

        Mage::throwException(Mage::helper('be2bill')->__('Impossible de se connecter aux serveurs de Be2bill'));
    }

    /**
     * Genération du Hash de vérification Be2bill
     *
     * @param array $params
     * @param string $password
     * @return string
     */
    public function generateHASH($params, $password, $paramsNoHash = null)
    {
    	if ($paramsNoHash == null){
    		$paramsNoHash = array();
    	}
    	
        $pass = $password;
        $finalString = $pass;
        ksort($params);
        foreach ($params as $key => $value) {
        	
        	//si dans le tableau alors, on ne calcule pas le hash
        	if (in_array($key, $paramsNoHash)){
        		continue;
        	}
            if (is_array($value)) {
                ksort($value);
                foreach ($value as $index => $val) {
                	if (is_array($val)){
                		ksort($val);
                		foreach ($val as $index2 => $val2) {
                			$finalString .= $key.'['.$index.']['.$index2.']='.$val2.$pass;
                		}
                	}
                	else {
                		$finalString .= $key . '[' . $index . ']=' . $val . $pass;
                	} 
                }
            } else {
                $finalString .= $key . "=" . $value . $pass;
            }
        }

        return hash('sha256', $finalString);
    }

    /**
     * Retourne L'url vers l'api Be2bill
     * @param string $mode
     * @return string
     */
    public function getRedirectUrl($mode = null)
    {
        if ($mode == 'direct-submit') {
            if ($this->isTestMode()) {
                $url = $this->getConfigData('direct_submit_test');
            } else {
               $urls = array( 'main' => $this->getConfigData('direct_submit'), 'slave' => $this->getConfigData('direct_submit_high_dispo') );      
            }
        } else {
            if ($this->isTestMode()) {
                $url = $this->getConfigData('uri_form_test');
            } else {
            	$urls = array( 'main' => $this->getConfigData('uri_form'), 'slave' => $this->getConfigData('uri_form_high_dispo') );
            }
        }
        
        //test de disponibilité des urls
        if (!$this->isTestMode()) {
        	$url = $this->_checkUrls($urls);
        }

        return $url;
    }

    
    protected function _checkUrls($urls)
    {
    	$i = 0;
    	$rUrls = array();
    	foreach($urls as $type => $url) {
    		if($url == null || trim($url) == '')
    			continue;

    		$result = file_get_contents($url);
    		
    		if ($result) {
    			$rUrls[$i] = $url;
    			$i++;
    		}	
    		
    	}
    	
    	if(count($rUrls) == 0) {
    		return null;
    	}
    	else {
    		return $rUrls[0];
    	}
    }
    
    
    /**
     *
     * @return string
     */
    public function getRestUrl()
    {
        if ($this->isTestMode()) {
            return $this->getConfigData('uri_rest_test');
        }

        return $this->getConfigData('uri_rest');
    }

    /**
     *
     * @return string
     */
    public function getRestUrlHighDispo()
    {
        if ($this->isTestMode()) {
            return $this->getConfigData('uri_rest_high_dispo_test');
        }

        return $this->getConfigData('uri_rest_high_dispo');
    }

    public function getConfigData($path, $storeId = null)
    {
        return Mage::getStoreConfig('be2bill/be2bill_api/' . $path, $storeId);
    }

}
