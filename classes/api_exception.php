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


namespace paygw_flouci;

/**
 * Thrown when the Flouci API cannot be reached or answers with something unusable.
 *
 * The message shown to users is generic; the technical detail is only in $debuginfo
 * (which never contains credentials).
 *
 * @package    paygw_flouci
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_exception extends \moodle_exception {

    /** @var int HTTP status code returned by Flouci (0 if there was no HTTP response). */
    public int $httpcode;

    /**
     * Constructor.
     *
     * @param string $debuginfo Technical description of the failure.
     * @param int $httpcode HTTP status code, 0 when none.
     */
    public function __construct(string $debuginfo, int $httpcode = 0) {
        $this->httpcode = $httpcode;
        parent::__construct('apierror', 'paygw_flouci', '', null, $debuginfo);
    }
}
