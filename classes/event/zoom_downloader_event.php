defined('MOODLE_INTERNAL') || die();

use core\event\base;
use GuzzleHttp\Client;

class course_module_viewed extends base {
    protected function init() {
        $this->data['objecttable'] = 'exportanotas';
        $this->data['crud'] = 'r'; // Operación de lectura
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    protected function get_custom_validation_error() {
        return '';
    }
}