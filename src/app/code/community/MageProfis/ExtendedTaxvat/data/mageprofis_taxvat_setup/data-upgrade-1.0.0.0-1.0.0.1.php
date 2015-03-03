<?php
/**
  * MageProfis_ExtendedTaxvat
  *
  * @category  MageProfis
  * @package   MageProfis_ExtendedTaxvat
  * @author    Mathis Klooss <mathis@mage-profis.de>, Christopher Boehm <christopher@mage-profis.de>
  * @copyright 2015 Mage-Profis GmbH (http://www.mage-profis.de). All rights served.
  */

$installer = $this;
$installer->startSetup();

$entityTypeId     = $installer->getEntityTypeId('customer');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$installer->addAttribute('customer', 'validation_result', array(
    'input'         => 'text',
    'type'          => 'varchar',
    'label'         => 'Ust.Id- Validation Result',
    'visible'       => 1,
    'required'      => 0,
    'user_defined' 	=> 1,
));

$installer->addAttributeToGroup(
	$entityTypeId,
	$attributeSetId,
	$attributeGroupId,
	'validation_result',
	'999'
);

$attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'validation_result');
$attribute->setData('used_in_forms', array('adminhtml_customer'))
	->setData('is_used_for_customer_segment', true)
	->setData('is_system', 0)
	->setData('is_user_defined', 1)
	->setData('is_visible', 1)
	->setData('sort_order', 100)
	->save();

$installer->endSetup();
