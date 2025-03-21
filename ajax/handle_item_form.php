<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Display information about all the mod_bunamrag modules in the requested course.
 *
 * @package     mod_bunamrag
 * @copyright   2025 Omar Terrazas Razo <terrazasrazo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php'); // Incluye el archivo de configuración de Moodle


require_login(); // Asegura que el usuario esté autenticado
require_sesskey(); // Protege contra CSRF

header('Content-Type: application/json'); // Indica que la respuesta será JSON

global $DB;

try {
    // Obtener los datos enviados por POST
    $data = [
        'bunamrag' => required_param('bunamrag', PARAM_TEXT),
        'bunamragcontent' => required_param('bunamragcontent', PARAM_TEXT),
        'hvpid' => required_param('hvpid', PARAM_INT),
        'hvptitle' => required_param('hvptitle', PARAM_TEXT),
    ];

    // Validar los datos
    if (empty($data['bunamrag']) || empty($data['bunamragcontent'])) {
        throw new Exception('Datos incompletos');
    }

    // Guardar los datos en la base de datos
    $record = new \stdClass();
    $record->bunamrag = $data['bunamrag'];
    $record->bunamragcontent = $data['bunamragcontent'];
    $record->hvpid = $data['hvpid'];
    $record->hvptitle = $data['hvptitle'];
    $record->timecreated = time();
    $record->timemodified = time();

    $result = $DB->insert_record('bunamrag_items', $record);

    // Devolver una respuesta JSON exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Datos guardados correctamente',
        'record_id' => $result,
        'hvpid' => $record->hvpid,
        'hvptitle' => $record->hvptitle
    ]);
} catch (Exception $e) {
    // Devolver una respuesta JSON en caso de error
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
