<?php
require_once("$CFG->libdir/formslib.php");

class google_drive_zoom_form extends moodleform {
    public function definition() {
        $mform = $this->_form; // Obtener la instancia del formulario

        // Campo para el enlace de Google Drive
        $mform->addElement('text', 'gdrive_link', get_string('gdrive_link', 'local_myplugin'));
        $mform->setType('gdrive_link', PARAM_URL); // Asegurar que sea una URL
        $mform->addRule('gdrive_link', null, 'required', null, 'client');

        // Campo para el ID de la sala de Zoom
        $mform->addElement('text', 'zoom_id', get_string('zoom_id', 'local_myplugin'));
        $mform->setType('zoom_id', PARAM_TEXT); // Asegurar que sea texto
        $mform->addRule('zoom_id', null, 'required', null, 'client');

        // Campo para el correo electrónico
        $mform->addElement('text', 'email', get_string('email', 'local_myplugin'));
        $mform->setType('email', PARAM_EMAIL); // Asegurar que sea un correo electrónico
        $mform->addRule('email', null, 'required', null, 'client');

        // Botón de enviar
        $mform->addElement('submit', 'submitbutton', get_string('submit', 'local_myplugin'));
    }

    public function validation($data, $files) {
        $errors = [];

        // Validar enlace de Google Drive
        if (!filter_var($data['gdrive_link'], FILTER_VALIDATE_URL)) {
            $errors['gdrive_link'] = get_string('invalidgdriveurl', 'local_myplugin');
        }

        // Validar ID de Zoom (opcionalmente puedes agregar más validaciones específicas)
        if (empty($data['zoom_id'])) {
            $errors['zoom_id'] = get_string('requiredfield', 'local_myplugin');
        }

        // Validar correo electrónico
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = get_string('invalidemail', 'local_myplugin');
        }

        return $errors;
    }
}

// Uso en una página de Moodle
$mform = new google_drive_zoom_form();
if ($mform->is_cancelled()) {
    // Manejar la cancelación del formulario
    echo "Formulario cancelado.";
} else if ($data = $mform->get_data()) {
    // Procesar los datos del formulario
    // Por ejemplo, guardar los datos en la base de datos o enviar un correo
    echo "Datos procesados: ";
    echo "Enlace de Google Drive: " . htmlspecialchars($data->gdrive_link) . "<br>";
    echo "ID de Zoom: " . htmlspecialchars($data->zoom_id) . "<br>";
    echo "Correo Electrónico: " . htmlspecialchars($data->email) . "<br>";
} else {
    // Mostrar el formulario
    echo $mform->render();
}
?>
