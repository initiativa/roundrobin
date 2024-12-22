<?php

include ('../../../inc/includes.php');
require_once 'config.class.php';
require_once 'RRAssignmentsEntity.class.php';

class TicketBalanceConfigFormClass extends CommonDBTM {

    // Propriedade para armazenar a dependência
    private $rrAssignmentsEntity;

    public function __construct() {
        PluginTicketBalanceLogger::addWarning(__METHOD__ . ' - construtor chamado');
        
        // Inicializar a dependência no construtor
        $this->rrAssignmentsEntity = new PluginTicketBalanceRRAssignmentsEntity();
    }

    public function renderTitle() {
        $injectHTML = <<< EOT
                <p>
                    <div align='center'>
                        <h1>Configurações do TicketBalance</h1>
                    </div>
                </p>
EOT;
        echo $injectHTML;
    }

    public function showFormTicketBalance() {
        global $CFG_GLPI, $DB;

        if (self::checkCentralInterface()) {
            PluginTicketBalanceLogger::addWarning(__METHOD__ . ' - exibir conteúdo');
            self::displayContent();
        } else {
            echo "<div align='center'><br><img src='" . $CFG_GLPI['root_doc'] . "/pics/warning.png'><br>" . __("Acesso negado") . "</div>";
        }
    }

    public static function checkCentralInterface() {
        $currentInterface = Session::getCurrentInterface();
        PluginTicketBalanceLogger::addWarning(__METHOD__ . ' - interface atual: ' . $currentInterface);
        return $currentInterface === 'central';
    }

    public function displayContent() {
        $auto_assign_group = Html::cleanInputText(self::getAutoAssignGroup());
        $auto_assign_user = Html::cleanInputText(self::getAutoAssignUser());
        $settings = self::getSettings();

        // Gerar o token CSRF e armazená-lo na sessão
        $csrfToken = Session::getNewCSRFToken();
        $_SESSION['_glpi_csrf_token'] = $csrfToken;

        echo "<div class='center'>";
        echo "<form name='settingsForm' action='config.form.php' method='post' enctype='multipart/form-data'>";
        echo Html::hidden('_glpi_csrf_token', ['value' => $csrfToken]); // Utiliza o token armazenado na sessão
        echo "<table class='tab_cadre_fixe'>";
        
        // Título do Formulário
        echo "<tr><th colspan='4'>Distribuição rotativa de técnicos em chamados, considerando o grupo encarregado da Categoria ITIL</th></tr>";
        echo "<tr><th colspan='4'><hr /></th></tr>";

        echo "<tr><th colspan='4'>";
        echo "Atribuir também o grupo encarregado da Categoria ITIL? &nbsp;&nbsp;";
        echo "<input type='radio' name='auto_assign_group' value='1'" . ($auto_assign_group ? " checked='checked'" : "") . "> Sim&nbsp;&nbsp;";
        echo "<input type='radio' name='auto_assign_group' value='0'" . (!$auto_assign_group ? " checked='checked'" : "") . "> Não";
        echo "</th></tr>";

        echo "<tr><th colspan='4'>";
        echo "Rodizio por categoria, ou global? &nbsp;&nbsp;";
        echo "<input type='radio' name='auto_assign_user' value='1'" . ($auto_assign_user ? " checked='checked'" : "") . "> Categoria&nbsp;&nbsp;";
        echo "<input type='radio' name='auto_assign_user' value='0'" . (!$auto_assign_user ? " checked='checked'" : "") . "> Global";
        echo "</th></tr>";

        echo "<tr><th colspan='4'><hr /></th></tr>";
        echo "<tr><th>ITIL Category</th><th>Grupo</th><th>Número de Membros</th><th>Configuração</th></tr>";

        foreach ($settings as $row) {
            $id = $row['id'];
            $itilcategories_id = $row['itilcategories_id'];
            $category_name = Html::cleanInputText($row['category_name']);
            $group_name = isset($row['group_name']) ? Html::cleanInputText($row['group_name']) : "<em>Nenhum grupo atribuído</em>";
            $num_group_members = isset($row['num_group_members']) ? Html::cleanInputText($row['num_group_members']) : "<em>N/A</em>";
            $is_active = $row['is_active'];

            echo "<tr>";
            echo "<td>{$category_name}</td>";
            echo "<td>{$group_name}</td>";
            echo "<td>{$num_group_members}</td>";
            echo "<td>";
            echo Html::hidden("itilcategories_id_{$id}", ['value' => $itilcategories_id]);
            echo "<input type='radio' name='is_active_{$id}' value='1' " . ($is_active ? "checked='checked'" : "") . "> Ativado&nbsp;&nbsp;";
            echo "<input type='radio' name='is_active_{$id}' value='0' " . (!$is_active ? "checked='checked'" : "") . "> Desativado";
            echo "</td>";
            echo "</tr>";
        }

        echo "<tr><td colspan='4'><hr/></td></tr>";
		echo "<tr><td colspan='4' style='text-align: right;'><input type='submit' name='save' class='submit' value=" . __('Salvar') . ">&nbsp;&nbsp;<input type='submit' class='submit' name='cancel' value=" . __('Cancelar') . "></td></tr>";
        echo "</table>";
    }

    protected static function getSettings() {
        $instance = new PluginTicketBalanceRRAssignmentsEntity();
        return $instance->getAll();
    }

    protected static function getAutoAssignGroup() {
        $instance = new PluginTicketBalanceRRAssignmentsEntity();
        return $instance->getOptionAutoAssignGroup();
    }

    protected static function getAutoAssignUser() {
        $instance = new PluginTicketBalanceRRAssignmentsEntity();
        return $instance->getOptionAutoAssignUser();
    }

    public function saveSettings() {
		// Validação do token CSRF
		if (!isset($_POST['_glpi_csrf_token']) || $_POST['_glpi_csrf_token'] !== $_SESSION['_glpi_csrf_token']) {
			die('Token CSRF inválido');
		}
		
        PluginTicketBalanceLogger::addWarning(__METHOD__ . ' - POST: ' . print_r($_POST, true));
        $rrAssignmentsEntity = new PluginTicketBalanceRRAssignmentsEntity();

        //Salvar opções)
        $rrAssignmentsEntity->updateAutoAssignGroup($_POST['auto_assign_group']);
        $rrAssignmentsEntity->updateAutoAssignUser($_POST['auto_assign_user']);

        /**
         * Salvar todas as atribuições
         */
        foreach (self::getSettings() as $row) {
            $itilCategoryId = $_POST["itilcategories_id_{$row['id']}"];
            $newValue = $_POST["is_active_{$row['id']}"];
            $rrAssignmentsEntity->updateIsActive($itilCategoryId, $newValue);
        }
    }
}
