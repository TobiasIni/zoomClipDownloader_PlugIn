<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_zoomdownloader_install() {
    global $DB;

    // Definir el rol usando cadenas de idioma
    $role = new stdClass();
    $role->name = get_string('role_zoomdownloader_name', 'mod_zoomdownloader');  // Identificador corregido
    $role->shortname = 'zoomdownloader_drive_role';  // Sin salto de línea
    $role->description = get_string('role_zoomdownloader_description', 'mod_zoomdownloader');  // Identificador corregido
    $role->archetype = 'user';

    // Verificar si el rol ya existe
    if (!$DB->record_exists('role', array('shortname' => $role->shortname))) {
        // Crear el rol en la base de datos
        $roleid = create_role($role->name, $role->shortname, $role->description, $role->archetype);

        if ($roleid) {
            // Establecer contextos permitidos para el rol
            $contextlevels = array(CONTEXT_SYSTEM);
            foreach ($contextlevels as $contextlevel) {
                if (!$DB->record_exists('role_context_levels', array('roleid' => $roleid, 'contextlevel' => $contextlevel))) {
                    $DB->insert_record('role_context_levels', array('roleid' => $roleid, 'contextlevel' => $contextlevel));
                }
            }
        }
    }
}
