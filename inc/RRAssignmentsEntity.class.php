<?php

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', '../../..');
}
require_once GLPI_ROOT . '/inc/includes.php';

if (!defined('PLUGIN_TICKETBALANCE_DIR')) {
    define('PLUGIN_TICKETBALANCE_DIR', __DIR__);
}
require_once PLUGIN_TICKETBALANCE_DIR . '/inc/config.class.php';

class PluginTicketBalanceRRAssignmentsEntity extends CommonDBTM {

    protected $DB;
    protected $rrAssignmentTable;
    protected $rrOptionsTable;

    public function __construct() {
        global $DB;

        $this->DB = $DB;
        $this->rrAssignmentTable = TicketBalanceConfigClass::getRrAssignmentTable();
        $this->rrOptionsTable = TicketBalanceConfigClass::getRrOptionsTable();
    }

    public function init() {
        $this->createTable();
        $this->truncateTable();
        $this->insertAllItilCategory();
        $this->insertOptions();
    }

    public function cleanUp() {
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - entrou...');

        /**
         * remover configurações
         */
        if ($this->DB->tableExists($this->rrAssignmentTable)) {
            $sqlDropAssign = <<< EOT
            DROP TABLE {$this->rrAssignmentTable}
EOT;
            PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlDrop: ' . $sqlDropAssign);
            $this->DB->queryOrDie($sqlDropAssign, $this->DB->error());
        } else {
            PluginTicketBalanceLogger::addWarning(__FUNCTION__ . " - tabela não removida porque não existe: " . $this->rrAssignmentTable);
        }

        /**
         * remover opções
         */
        if ($this->DB->tableExists($this->rrOptionsTable)) {
            $sqlDropOptions = <<< EOT
            DROP TABLE {$this->rrOptionsTable}
EOT;
            PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlDrop: ' . $sqlDropOptions);
            $this->DB->queryOrDie($sqlDropOptions, $this->DB->error());
        } else {
            PluginTicketBalanceLogger::addWarning(__FUNCTION__ . " - tabela não removida porque não existe: " . $this->rrOptionsTable);
        }
    }

    protected function createTable() {
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - entrou...');

        /**
         * criar tabela de configurações
         */
        if (!$this->DB->tableExists($this->rrAssignmentTable)) {
            $sqlCreateAssign = <<< EOT
                    CREATE TABLE IF NOT EXISTS {$this->rrAssignmentTable} (
                        id INT(11) NOT NULL auto_increment,
                        itilcategories_id INT(11),
                        is_active INT(1) DEFAULT 0,
                        last_assignment_index INT(11) DEFAULT NULL,
                        PRIMARY KEY (id),
                        UNIQUE INDEX ix_itilcategories_uq (itilcategories_id ASC)
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
EOT;
            PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlCreate: ' . $sqlCreateAssign);
            $this->DB->queryOrDie($sqlCreateAssign, $this->DB->error());
        }

        /**
         * criar tabela de opções
         */
        if (!$this->DB->tableExists($this->rrOptionsTable)) {
            $sqlCreateOption = <<< EOT
                    CREATE TABLE IF NOT EXISTS {$this->rrOptionsTable} (
                        id INT(11) NOT NULL auto_increment,
                        auto_assign_group INT(1) DEFAULT 1,
                        auto_assign_user INT(1) DEFAULT 1,
                        PRIMARY KEY (id)
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
EOT;
            PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlCreate: ' . $sqlCreateOption);
            $this->DB->queryOrDie($sqlCreateOption, $this->DB->error());
        }
    }

    protected function truncateTable() {
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - entrou...');

        /**
         * limpar todas as configurações
         */
        if ($this->DB->tableExists($this->rrAssignmentTable)) {
            $sqlTruncAssign = <<< EOT
                TRUNCATE TABLE {$this->rrAssignmentTable}
EOT;
            PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlTrunc: ' . $sqlTruncAssign);
            $this->DB->queryOrDie($sqlTruncAssign, $this->DB->error());
        }

        /**
         * limpar todas as opções
         */
        if ($this->DB->tableExists($this->rrOptionsTable)) {
            $sqlTruncOptions = <<< EOT
                TRUNCATE TABLE {$this->rrOptionsTable}
EOT;
            PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlTrunc: ' . $sqlTruncOptions);
            $this->DB->queryOrDie($sqlTruncOptions, $this->DB->error());
        }
    }

    protected function insertAllItilCategory() {
        $sqlCategory_0 = <<< EOT
                SELECT id FROM glpi_itilcategories
                WHERE itilcategories_id = 0 AND groups_id <> 0
EOT;
        $sqlCategory = <<< EOT
                SELECT id FROM glpi_itilcategories
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlCategory: ' . $sqlCategory);
        $itilCategoriesCollection = $this->DB->queryOrDie($sqlCategory, $this->DB->error());
        $itilCategoriesArray = iterator_to_array($itilCategoriesCollection);
        foreach ($itilCategoriesArray as $itilCategory) {
            $this->insertItilCategory($itilCategory['id']);
        }
    }

    public function insertItilCategory($itilCategory) {
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - entrou...');

        /**
         * inserir uma única entrada
         */
        $sqlInsert = <<< EOT
                INSERT INTO {$this->rrAssignmentTable} (itilcategories_id) VALUES ({$itilCategory})
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlInsert: ' . $sqlInsert);
        $this->DB->queryOrDie($sqlInsert, $this->DB->error());
    }

    public function insertOptions() {
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - entrou...');

        // inserir entrada
        $sqlInsert = <<< EOT
                INSERT INTO {$this->rrOptionsTable} (auto_assign_group) VALUES (1),
                INSERT INTO {$this->rrOptionsTable} (auto_assign_user) VALUES (1)
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlInsert: ' . $sqlInsert);
        $this->DB->queryOrDie($sqlInsert, $this->DB->error());
    }

    public function getOptionAutoAssignGroup() {
        $sql = <<< EOT
                SELECT auto_assign_group FROM {$this->rrOptionsTable} LIMIT 1
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sql: ' . $sql);
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        return $resultArray[0]['auto_assign_group'];
    }

    public function getOptionAutoAssignUser() {
        $sql = <<< EOT
                SELECT auto_assign_user FROM {$this->rrOptionsTable} LIMIT 1
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sql: ' . $sql);
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        return $resultArray[0]['auto_assign_user'];
    }

    public function getGroupByItilCategory($itilCategory) {
        $sql = <<< EOT
                SELECT groups_id FROM glpi_itilcategories
                WHERE id = {$itilCategory}
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sql: ' . $sql);
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        $groupsId = $resultArray[0]['groups_id'];
        return $groupsId !== 0 ? $groupsId : false;
    }

	public function updateAutoAssignGroup($autoAssignGroup) {
		global $DB; // Certifique-se de usar a instância global do banco de dados

		// Escape do valor para evitar SQL Injection
		$escapedValue = $DB->escape($autoAssignGroup);

		$sqlUpdate = <<< EOT
			UPDATE {$this->rrOptionsTable}
			SET auto_assign_group = {$escapedValue}
			WHERE id = 1
EOT;

		PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlUpdate: ' . $sqlUpdate);

		// Executa a query
		$DB->queryOrDie($sqlUpdate, $DB->error());
	}

	public function updateAutoAssignUser($autoAssignUser) {
		global $DB; // Certifique-se de usar a instância global do banco de dados

		// Escape do valor para evitar SQL Injection
		$escapedValue = $DB->escape($autoAssignUser);

		$sqlUpdate = <<< EOT
			UPDATE {$this->rrOptionsTable}
			SET auto_assign_user = {$escapedValue}
			WHERE id = 1
EOT;

		PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlUpdate: ' . $sqlUpdate);

		// Executa a query
		$DB->queryOrDie($sqlUpdate, $DB->error());
	}

    public function deleteItilCategory($itilCategory) {
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - entrou...');

        /**
         * excluir uma única entrada
         */
        $sqlDelete = <<< EOT
                DELETE FROM {$this->rrAssignmentTable} WHERE itilcategories_id = {$itilCategory}
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlDelete: ' . $sqlDelete);
        $this->DB->queryOrDie($sqlDelete, $this->DB->error());
    }

    public function updateLastAssignmentIndexCategoria($itilcategoriesId, $index) {
        $sqlUpdate = <<< EOT
                UPDATE {$this->rrAssignmentTable}
                SET last_assignment_index = {$index}
                WHERE itilcategories_id = {$itilcategoriesId}
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlUpdate: ' . $sqlUpdate);
        $this->DB->queryOrDie($sqlUpdate, $this->DB->error());
    }

    public function updateLastAssignmentIndexGlobal($itilcategoriesId, $index) {
        $sqlUpdate = <<< EOT
                UPDATE {$this->rrAssignmentTable}
                SET last_assignment_index = {$index}
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlUpdate: ' . $sqlUpdate);
        $this->DB->queryOrDie($sqlUpdate, $this->DB->error());
    }

    public function updateIsActive($itilcategoriesId, $isActive) {
        $sqlUpdate = <<< EOT
                UPDATE {$this->rrAssignmentTable}
                SET is_active = {$isActive}
                WHERE itilcategories_id = {$itilcategoriesId}
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sqlUpdate: ' . $sqlUpdate);
        $this->DB->queryOrDie($sqlUpdate, $this->DB->error());
    }

    public function getLastAssignmentIndex($itilcategoriesId) {
        $sql = <<< EOT
                SELECT last_assignment_index FROM {$this->rrAssignmentTable} 
                WHERE itilcategories_id = {$itilcategoriesId} AND is_active = 1
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sql: ' . $sql);
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - $resultArray: ' . print_r($resultArray, true));
        if (count($resultArray) === 0 || count($resultArray) > 1) {
            /**
             * para o comportamento especificado da categoria não é necessário
             * ou existem mais de uma linha para a categoria
             */
            return false;
        } else {
            PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - categoria tem entrada');
            return $resultArray[0]['last_assignment_index'];
        }
    }

    //@return array de array (id, itilcategories_id, category_name, groups_id, group_name, num_group_members, is_active)
    public function getAll() {
        $sql_0 = <<< EOT
                SELECT 
                    a.id,
                    a.itilcategories_id,
                    c.completename AS category_name,
                    c.groups_id,
                    g.completename AS group_name,
                    (SELECT 
                            COUNT(id)
                        FROM
                            glpi_groups_users gu
                        WHERE
                            gu.groups_id = g.id) AS num_group_members,
                    a.is_active
                FROM
                    glpi_plugin_ticketbalance_assignments a
                        JOIN
                    glpi_itilcategories c ON c.id = a.itilcategories_id
                        JOIN
                    glpi_groups g ON g.id = c.groups_id
EOT;
        $sql = <<< EOT
                SELECT 
                    a.id,
                    a.itilcategories_id,
                    c.completename AS category_name,
                    c.groups_id,
                    g.completename AS group_name,
                    (SELECT 
                            COUNT(id)
                        FROM
                            glpi_groups_users gu
                        WHERE
                            gu.groups_id = g.id) AS num_group_members,
                    a.is_active
                FROM
                    glpi_plugin_ticketbalance_assignments a
                        JOIN
                    glpi_itilcategories c ON c.id = a.itilcategories_id
                        LEFT JOIN
                    glpi_groups g ON g.id = c.groups_id
EOT;
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - sql: ' . $sql);
        $resultCollection = $this->DB->queryOrDie($sql, $this->DB->error());
        $resultArray = iterator_to_array($resultCollection);
        PluginTicketBalanceLogger::addWarning(__FUNCTION__ . ' - $resultArray: ' . print_r($resultArray, true));
        return $resultArray;
    }

}