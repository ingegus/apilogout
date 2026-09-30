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
 * Plugin strings are defined here.
 *
 * @package     local_hublogout
 * @category    string
 * @copyright   2025 Gustavo A. Rodriguez A. - IngeGus <hola@ingegus.dev>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_hublogout;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

class external extends external_api {

    public static function kill_user_session_parameters() {
        return new external_function_parameters([
            'email' => new external_value(PARAM_EMAIL, 'Email del usuario a cerrar sesión', VALUE_REQUIRED),
        ]);
    }

    public static function kill_user_session($email) {
        global $DB;

        $params = self::validate_parameters(self::kill_user_session_parameters(), ['email' => $email]);
        $user = $DB->get_record('user', ['email' => $params['email'], 'deleted' => 0]);

        if (!$user) {
            return [
                'status'  => false,
                'message' => 'Usuario no encontrado en Moodle'
            ];
        }
	
	// Validar si existen los registros activos en la tabla de sesiones de Moodle
	$hasactiveSession = $DB->record_exists('sessions', ['userid' => $user->id]);
	
	if (!$hasactiveSession) {
		return [
			'status' => false,
			'message' => 'Usuario sin sesión iniciada'
		];
	}

        // Destrucción nativa de la sesión en Moodle
        \core\session\manager::kill_user_sessions($user->id);

        return [
            'status'  => true,
            'message' => 'Sesiones cerradas exitosamente para: ' . $user->email
        ];
    }

    public static function kill_user_session_returns() {
        return new external_single_structure([
            'status'  => new external_value(PARAM_BOOL, 'Estado de la operación'),
            'message' => new external_value(PARAM_TEXT, 'Respuesta detallada'),
        ]);
    }
}