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
$eavInstaller = new Mage_Eav_Model_Entity_Setup('be2bill_setup');

$eavInstaller->startSetup();

$entityTypeId = $eavInstaller->getEntityTypeId('catalog_product');

/**
 * Add 'be2bill_enable_oc_sev_payment' attribute to the 'eav/attribute' table
 */
$attribute = $eavInstaller->getAttribute($entityTypeId, 'be2bill_enable_oc_sev_payment');

if (!$attribute) {
    $eavInstaller->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'be2bill_enable_oc_sev_payment', array(
        'type' => 'int',
        'backend' => '',
        'frontend' => '',
        'label' => 'Is Product Available for Oneclick Several Payment with Be2bill',
        'input' => 'select',
        'class' => '',
        'source' => 'eav/entity_attribute_source_boolean',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
        'visible' => true,
        'required' => false,
        'user_defined' => true,
        'default' => '0',
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
        'visible_on_front' => false,
        'unique' => false,
        'apply_to' => '',
        'is_configurable' => false
    ));
}

$attributeSets = $eavInstaller->getConnection()->fetchAll('select attribute_set_id from ' . $this->getTable('eav/attribute_set') . ' where entity_type_id=?', $entityTypeId);
$attributeId = $eavInstaller->getAttributeId('catalog_product', 'be2bill_enable_oc_sev_payment');

foreach ($attributeSets as $attributeSet) {
    /*
     * Create new attribute group
     */
    $setId = $attributeSet['attribute_set_id'];
    $eavInstaller->addAttributeGroup($entityTypeId, $setId, 'Be2bill Payment Options');

    /*
     * Add attribute to Be2bill Payment Options attribute group
     */
    $groupId = $eavInstaller->getAttributeGroupId($entityTypeId, $setId, 'Be2bill Payment Options');
    $eavInstaller->addAttributeToGroup($entityTypeId, $setId, $groupId, $attributeId, 1);
}

/**
 * Add 'be2bill_3dsecure' attribute to the 'eav/attribute' table
 */
/* $attribute = $eavInstaller->getAttribute($entityTypeId, 'be2bill_3dsecure');

  if (!$attribute) {
  $eavInstaller->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'be2bill_3dsecure', array(
  'type'              => 'int',
  'backend'           => '',
  'frontend'          => '',
  'label'             => 'Is Product Available for 3D Secure Payment',
  'input'             => 'select',
  'class'             => '',
  'source'            => 'eav/entity_attribute_source_boolean',
  'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
  'visible'           => true,
  'required'          => false,
  'user_defined'      => true,
  'default'           => '0',
  'searchable'        => false,
  'filterable'        => false,
  'comparable'        => false,
  'visible_on_front'  => false,
  'unique'            => false,
  'apply_to'          => '',
  'is_configurable'   => false
  ));
  }

  $attributeSets = $eavInstaller->getConnection()->fetchAll('select attribute_set_id from '.$this->getTable('eav/attribute_set').' where entity_type_id=?', $entityTypeId);
  $attributeId = $eavInstaller->getAttributeId('catalog_product', 'be2bill_3dsecure');

  foreach ($attributeSets as $attributeSet) {

  //Add attribute to Be2bill Payment Options attribute group

  $setId = $attributeSet['attribute_set_id'];
  $groupId = $eavInstaller->getAttributeGroupId($entityTypeId, $setId, 'Be2bill Payment Options');
  $eavInstaller->addAttributeToGroup($entityTypeId, $setId, $groupId, $attributeId, 2);
  }

  $entityTypeId = $eavInstaller->getEntityTypeId('catalog_category');
  $attribute = $eavInstaller->getAttribute($entityTypeId, 'be2bill_3dsecure');

  if (!$attribute) {
  $eavInstaller->addAttribute(
  Mage_Catalog_Model_Category::ENTITY, 'be2bill_3dsecure', array(
  'type'              => 'int',
  'backend'           => '',
  'frontend'          => '',
  'label'             => 'Are Category\'s Products Available for 3D Secure Payment',
  'input'             => 'select',
  'class'             => '',
  'source'            => 'eav/entity_attribute_source_boolean',
  'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
  'visible'           => true,
  'required'          => false,
  'user_defined'      => true,
  'default'           => '0',
  'searchable'        => false,
  'filterable'        => false,
  'comparable'        => false,
  'visible_on_front'  => false,
  'unique'            => false,
  'apply_to'          => '',
  'is_configurable'   => false,
  'group'             => 'Be2bill Options'
  ));
  }
 */
$eavInstaller->endSetup();

