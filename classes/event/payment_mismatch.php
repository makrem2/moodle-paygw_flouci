<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


namespace paygw_flouci\event;

/**
 * Flouci reported a payment whose amount or reference does not match the session; needs manual review.
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_mismatch extends \core\event\base {

    /**
     * Initialises the event.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'paygw_flouci';
    }

    /**
     * Event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_payment_mismatch', 'paygw_flouci');
    }

    /**
     * Event description.
     *
     * @return string
     */
    public function get_description() {
        return "Flouci reported SUCCESS for reference '{$this->other['ref']}' (user with id '{$this->relateduserid}') " .
            "but the amount or reference does not match: expected {$this->other['expected']} millimes, " .
            "reported {$this->other['reported']}. The order was NOT delivered; review it manually.";
    }

    /**
     * Object id mapping (for backup/restore of logs).
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'paygw_flouci', 'restore' => \core\event\base::NOT_MAPPED];
    }

    /**
     * Other-field mapping.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['ref'])) {
            throw new \coding_exception('The \'ref\' value must be set in other.');
        }
        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
    }
}
