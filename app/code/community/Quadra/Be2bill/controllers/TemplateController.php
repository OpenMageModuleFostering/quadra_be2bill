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
class Quadra_Be2bill_TemplateController extends Mage_Core_Controller_Front_Action
{
    /**
     * @deprecated
     * load layout
     */
    /* public function nosslAction()
      {
      $this->loadLayout();
      $this->renderLayout();
      } */

    /**
     * @deprecated
     * load layout
     */
    /* public function sslAction()
      {
      $this->loadLayout();
      $this->renderLayout();
      } */

    /**
     * @deprecated
     * load layout
     */
    /* public function iframeAction()
      {
      $this->loadLayout();
      $this->renderLayout();
      } */

    /**
     * load layout
     */
    public function successAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * load layout
     */
    public function failureAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

}
