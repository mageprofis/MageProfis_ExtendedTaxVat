<?php
/**
 * MageProfis_ExtendedTaxvat
 *
 * @category  MageProfis
 * @package   MageProfis_ExtendedTaxvat
 * @author    Mathis Klooss <mathis@mage-profis.de>, Christopher Boehm <christopher@mage-profis.de>
 * @copyright 2015 Mage-Profis GmbH (http://www.mage-profis.de). All rights served.
 */
class MageProfis_ExtendedTaxvat_Model_Cron
extends Mage_Core_Model_Abstract
{
    /**
     * remove old entries from database
     */
    public function removeOldEntries()
    {
        $collection = Mage::getModel('extendedtaxvat/taxvat')->getCollection()
                        ->addFieldToFilter('created_at',
                            array(
                                'to' => $this->getOldDate(),
                                'datetime' => true,
                            )
                        )
        ;
        foreach($collection as $_taxvat)
        {
            /* @var $_taxvat MageProfis_ExtendedTaxvat_Model_Taxvat */
            $_taxvat->delete();
        }

        // remove old invalid entries 
        $collection = Mage::getModel('extendedtaxvat/taxvat')->getCollection()
                        ->addFieldToFilter('created_at',
                            array(
                                'to' => $this->getOldDateForInValid(),
                                'datetime' => true,
                            )
                        )
                        ->addFieldToFilter('valid', 0)
        ;
        foreach($collection as $_taxvat)
        {
            /* @var $_taxvat MageProfis_ExtendedTaxvat_Model_Taxvat */
            $_taxvat->delete();
        }
    }

    /**
     * 
     * @return string
     */
    protected function getOldDate()
    {
        return date('Y-m-d H:i:s', strtotime('-4 weeks'));
    }

    /**
     * 
     * @return string
     */
    protected function getOldDateForInValid()
    {
        return date('Y-m-d H:i:s', strtotime('-1 week'));
    }
}
