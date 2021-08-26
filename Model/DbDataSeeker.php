<?php

namespace MagentoSupport\ART\Model;

use Magento\Framework\App\ResourceConnection;

/**
 * Class DbDataSeeker
 * @package MagentoSupport\ART\Model
 */
class DbDataSeeker
{
    /**
     * @var resource Connection
     */
    protected  $connection;

    /**
     * store data from DB
     * @var array
     */
    private $data = [];

    /**
     * DbDataSeeker constructor.
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
    }

    /**
     * @return array
     */
    public function seekDbData() {
        $this->data['isModuleEnabled'] = $this->selectFromCoreConfig(
            ['scope','scope_id','value'],
            'analytics/subscription/enabled'
        );

        //todo check custom group
        $this->data['cronExecTime'] = $this->selectFromCoreConfig(
            ['scope','scope_id','value'],
            'analytics_collect_data/schedule/cron_expr'
        );
        $this->data['analytic_cron_job'] = $this->findAnalyticsCronJobInDb();
        $this->data['isTokenPresent'] = $this->data['cronExecTime'] = $this->selectFromCoreConfig(
            ['scope','scope_id','value'],
            'analytics/general/token'
        );
        $this->data['flagTable'] = $this->checkFlagTable();
        $this->data['escapedQuotes'] = $this->checkEscapedQuotes();
        $this->data['isMultiCurrency'] = $this->isMultiCurrency();
        return $this->data;

    }

    /**
     * Get data from core config table
     * @param $columns
     * @param $pathValue
     * @return false|string
     */
    private function selectFromCoreConfig ($columns, $pathValue) {
        $configTable = $this->connection->getTableName('core_config_data');
        $select = $this->connection->select()->from($configTable, $columns)->where('path = :path');
        $bind = [':path' => $pathValue];
        $result = $this->connection->fetchAll($select,$bind);
        return json_encode($result);
    }

    /**
     * check multiCurrency
     * @return string
     */
    private function isMultiCurrency () {
        $select = $this->connection->select()->from(
            $this->connection->getTableName('sales_order'),
            ['base_currency_code','COUNT(*)'])
            ->distinct(true)
            ->group('base_currency_code');
        $result = $this->connection->fetchAll($select);
        if (count($result) > 1) {
            return 'There is multiple currencies was found:' . json_encode($result);
        }
        else {
          return 'No multiple currencies was found';
        }
    }

    /**
     * Find all analytics_collect_data rows
     * @return false|string
     */
    private function findAnalyticsCronJobInDb () {
        $select = $this->connection->select()->from(
            $this->connection->getTableName('cron_schedule'),
            ['job_code','messages','status'])->where('job_code LIKE :job_code');
        $bind = [':job_code' => 'analytics_collect_data'];
        $result = $this->connection->fetchAll($select,$bind);
        return json_encode($result);


    }

    /**
     * Find anything related to Analytics in Flag table
     * @return false|string
     */
    private function checkFlagTable() {
        $select = $this->connection->select()->from(
            $this->connection->getTableName('flag'),
            ['flag_code','flag_data','last_update'])
            ->where('flag_code LIKE :flag_code');
        $bind = [':flag_code' => 'analytics%'];
        $result = $this->connection->fetchAll($select,$bind);
        return json_encode($result);
    }

    /**
     * Find escaped quotes
     * @return false|string
     */
    private function checkEscapedQuotes() {
        $select = $this->connection->select()->from(
            $this->connection->getTableName('sales_order_item'),
            ['name','COUNT(*)','sku'])
            ->where('name like \'%\\\\\\\\"%\' or name like \'%\"%\' ')
            ->group(['name','sku']);
        $result = $this->connection->fetchAll($select);
        return json_encode(count($result));

    }
}