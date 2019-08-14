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
 * An interface description for Zend_Http_Client_Adapter_Stream classes.
 *
 * This interface decribes Zend_Http_Client_Adapter which supports streaming.
 */
interface Quadra_Be2bill_Model_Api_Http_Client_Adapter_Stream
{

    /**
     * Set output stream
     *
     * This function sets output stream where the result will be stored.
     *
     * @param resource $stream Stream to write the output to
     *
     */
    function setOutputStream($stream);
}
