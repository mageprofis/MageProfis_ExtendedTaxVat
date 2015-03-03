<?php
/**
  * MageProfis_ExtendedTaxvat
  *
  * @category  MageProfis
  * @package   MageProfis_ExtendedTaxvat
  * @author    Mathis Klooss <mathis@mage-profis.de>, Christopher Boehm <christopher@mage-profis.de>
  * @copyright 2015 Mage-Profis GmbH (http://www.mage-profis.de). All rights served.
  */
class MageProfis_ExtendedTaxvat_Model_Resource_Taxvat_Collection
extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('extendedtaxvat/taxvat');
    }
}
