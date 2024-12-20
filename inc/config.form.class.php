<?php

/**
 * -------------------------------------------------------------------------
 * Plugin TicketBalance para GLPI
 * -------------------------------------------------------------------------
 *
 * LICENÇA
 *
 * Este arquivo é parte do plugin RoundRobin GLPI.
 *
 * RoundRobin é um software livre; você pode redistribuí-lo e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU conforme publicada pela
 * Free Software Foundation; seja na versão 3 da Licença, ou (a seu critério) 
 * qualquer versão posterior.
 *
 * RoundRobin é distribuído na esperança de que seja útil,
 * mas SEM QUALQUER GARANTIA; sem mesmo a garantia implícita de
 * COMERCIALIZAÇÃO ou ADEQUAÇÃO A UM PROPÓSITO PARTICULAR. Consulte
 * a Licença Pública Geral GNU para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral GNU
 * junto com o RoundRobin. Se não, veja <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022 por iniciativa s.r.l. - http://www.initiativa.it
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/initiativa/roundrobin
 * -------------------------------------------------------------------------
 */
include ('../../../inc/includes.php');
require_once 'config.class.php';
require_once 'RRAssignmentsEntity.class.php';

class PluginRoundRobinSettings extends CommonDBTM {

    public function __construct() {
        PluginRoundRobinLogger::addWarning(__METHOD__ . ' - construtor chamado');
    }

    public function renderTitle() {
        $injectHTML = <<< EOT
                <p>
                    <div align='center'>
                        <h1>Configurações do RoundRobin</h1>
                    </div>
                </p>
EOT;
        echo $injectHTML;
    }

    public function showFormRoundRobin() {
        global $CFG_GLPI, $DB;

        if (self::checkCentralInterface()) {
            PluginRoundRobinLogger::addWarning(__METHOD__ . ' - exibir conteúdo');
            self::displayContent();
        } else {
            echo "<div align='center'><br><img src='" . $CFG_GLPI['root_doc'] . "/pics/warning.png'><br>" . __("Acesso negado") . "</div>";
        }
    }

    public static function checkCentralInterface() {
        $currentInterface = Session::getCurrentInterface();
        PluginRoundRobinLogger::addWarning(__METHOD__ . ' - interface atual: ' . $currentInterface);
        return $currentInterface === 'central';
    }

    public static function displayContent() {
        echo "<div class='center'>";
        echo "<form name='settingsForm' action='config.form.php' method='post' enctype='multipart/form-data'>";
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='4'>" . "Ativar atribuição de tickets Round Robin para cada ITILCategory" . "</th></tr>";
        echo "<tr><th colspan='4'>" . "<hr />" . "</th></tr>";

        /**
         * linha de opção
         */
        echo "<tr><th colspan='4'>";
        echo "Atribuir também ao Grupo original: &nbsp;&nbsp; <input type='radio' name='auto_assign_group' value='1'";
        $auto_assign_group = self::getAutoAssignGoup();
        if ($auto_assign_group) {
            echo "checked='checked'";
        }
        echo "> Sim&nbsp;&nbsp;";
        echo "<input type='radio' name='auto_assign_group' value='0'";
        if (!$auto_assign_group) {
            echo "checked='checked'";
        }
        echo "> Não";
        echo "</th></tr>";

        /**
         * linhas de atribuições
         */
        echo "<tr><th colspan='4'>" . "<hr />" . "</th></tr>";
        echo "<tr><th>ITILCategory</th><th>Grupo</th><th>Número de Membros</th><th>Configuração</th></tr>";

        /**
         * renderizar cada linha para perfil e configurações
         */
        foreach (self::getSettings() as $row) {
            $id = $row['id'];
            $itilcategories_id = $row['itilcategories_id'];
            $category_name = $row['category_name'];
            $group_name = isset($row['group_name']) ? $row['group_name'] : "<em>Nenhum grupo atribuído</em>";
            $num_group_members = isset($row['group_name']) ? $row['num_group_members'] : "<em/N/A</em>";
            $is_active = $row['is_active'];

            echo "<tr><td>$category_name</td><td>$group_name</td><td>$num_group_members</td>";
            echo "<td>";
            echo Html::hidden('itilcategories_id_' . $id, ['value' => $itilcategories_id]);
            echo "<input type='radio' name='is_active_$id' value='1'";
            if ($is_active) {
                echo "checked='checked'";
            }
            echo "> Ativado&nbsp;&nbsp;";
            echo "<input type='radio' name='is_active_$id' value='0'";
            if (!$is_active) {
                echo "checked='checked'";
            }
            echo "> Desativado</td></tr>";
        }
        /**
         * controles
         */
        echo "<tr><td colspan='4'><hr/></td></tr>";
        echo "<tr><td colspan='3'>&nbsp;<td><input type='submit' name='save' class='submit' value=" . __('Salvar') . ">&nbsp;&nbsp;<input type='submit' class='submit' name='cancel' value=" . __('Cancelar') . "></td></tr>";
        echo "</table>";
    }

    protected static function getSettings() {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        return $rrAssignmentsEntity->getAll();
    }

    protected static function getAutoAssignGoup() {
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();
        return $rrAssignmentsEntity->getOptionAutoAssignGroup();
    }

    public static function saveSettings() {
        PluginRoundRobinLogger::addWarning(__METHOD__ . ' - POST: ' . print_r($_POST, true));
        $rrAssignmentsEntity = new PluginRoundRobinRRAssignmentsEntity();

        /**
         * salvar opção(ões)
         */
        $rrAssignmentsEntity->updateAutoAssignGroup($_POST['auto_assign_group']);

        /**
         * salvar todas as atribuições
         */
        foreach (self::getSettings() as $row) {
            $itilCategoryId = $_POST["itilcategories_id_{$row['id']}"];
            $newValue = $_POST["is_active_{$row['id']}"];
            $rrAssignmentsEntity->updateIsActive($itilCategoryId, $newValue);
        }
    }

}
