<?php

class MageProfis_ExtendedTaxvat_Model_Resource_Taxvat_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {

    public function _construct()
    {
        parent::_construct();
        $this->_init('extendedtaxvat/taxvat');
    }
}
?>
