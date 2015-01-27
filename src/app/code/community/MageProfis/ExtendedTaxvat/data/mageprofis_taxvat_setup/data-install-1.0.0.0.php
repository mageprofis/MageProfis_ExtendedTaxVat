<?php

$installer = $this;
$installer->startSetup();

$tableName = $this->getTable('extendedtaxvat/taxvat');
// table: country_code (primary), vat_no (primary), valid (0|1)
if ($installer->getConnection()
                ->isTableExists($tableName)) {
    $installer->getConnection()
            ->dropTable($tableName);
}

$table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, 11, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
                ), 'Id')
        ->addColumn('country_code', Varien_Db_Ddl_Table::TYPE_TEXT, 2, array(
            'nullable' => false,
                ), 'Country Code')
        ->addColumn('vat_no', Varien_Db_Ddl_Table::TYPE_TEXT, 20, array(
            'nullable' => false,
                ), 'VAT Nr.')
        ->addColumn('result', Varien_Db_Ddl_Table::TYPE_TEXT, 65536, array(
            'nullable' => false,
                ), 'Result')
        ->addColumn('valid', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array(
            'nullable' => false,
                ), 'Is Valid')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array(
            'nullable' => false,
                ), 'Created_at')
        ->addIndex(
                $installer->getIdxName($tableName,
                    array('country_code', 'vat_no'),
                    Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
                ,
                array('country_code', 'vat_no'),
                array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
        );

$installer->getConnection()->createTable($table);
$installer->endSetup();
