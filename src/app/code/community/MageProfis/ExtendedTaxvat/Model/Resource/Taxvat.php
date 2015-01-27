<?php

class MageProfis_ExtendedTaxvat_Model_Resource_Taxvat
extends Mage_Core_Model_Resource_Db_Abstract
{

    public function _construct()
    {
        $this->_init('extendedtaxvat/taxvat', 'entity_id');
    }

    /**
     * 
     * @param Mage_Core_Model_Abstract $object
     * @return MageProfis_ExtendedTaxvat_Model_Resource_Taxvat
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if(is_null($object->getCreatedAt()))
        {
            $object->setCreatedAt(date('Y-m-d H:i:s'));
        } else {
            $object->setCreatedAt($object->getCreatedAt());
        }
        return parent::_beforeSave($object);
    }
}