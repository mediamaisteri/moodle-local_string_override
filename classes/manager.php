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

/**
 * Custom string manager for overriding existing translation strings
 *
 * @package    local_string_override
 * @copyright  2016 Mediamaisteri Oy <info@mediamaisteri.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_string_override_manager extends core_string_manager_standard {
    /**
     * Load all strings for one component
     *
     * @param string $component The module the string is associated with
     * @param string $lang
     * @param bool $disablecache Do not use caches, force fetching the strings from sources
     * @param bool $disablelocal Do not use customized strings in xx_local language packs
     * @return array of all string for given component and lang
     */
    public function load_component_strings($component, $lang, $disablecache = false, $disablelocal = false) {
        global $CFG;

        list($plugintype, $pluginname) = core_component::normalize_component($component);
        if ($plugintype === 'core' and is_null($pluginname)) {
            $component = 'core';
        } else {
            $component = $plugintype . '_' . $pluginname;
        }

        $cachekey = $lang.'_'.$component.'_'.$this->get_key_suffix();

        $cachedstring = $this->cache->get($cachekey);
        if (!$disablecache and !$disablelocal) {
            if ($cachedstring !== false) {
                return $cachedstring;
            }
        }

        // No cache found - let us merge all possible sources of the strings.
        if ($plugintype === 'core') {
            $file = $pluginname;
            if ($file === null) {
                $file = 'moodle';
            }
            $string = array();
            // First load english pack.
            if (!file_exists("$CFG->dirroot/lang/en/$file.php")) {
                return array();
            }
            include("$CFG->dirroot/lang/en/$file.php");
            $enstring = $string;

            // And then corresponding local if present and allowed.
            if (!$disablelocal and file_exists("$this->localroot/en_local/$file.php")) {
                include("$this->localroot/en_local/$file.php");
            }
            // Now loop through all langs in correct order.
            $deps = $this->get_language_dependencies($lang);

            if (empty($deps)) {
                // This allows us to override also English strings.
                $deps = array('en');
            }

            foreach ($deps as $dep) {
                // The main lang string location.
                if (file_exists("$this->otherroot/$dep/$file.php")) {
                    include("$this->otherroot/$dep/$file.php");
                }

                // Custom feature that allows plugins to override core strings (See MDL-46582).
                foreach (core_component::get_plugin_types() as $plugintype => $plugintypedir) {
                    foreach (core_component::get_plugin_list($plugintype) as $pluginname => $plugindir) {
                        $filename = "$plugindir/lang/$dep/$file.php";

                        if (file_exists($filename)) {
                            include($filename);
                        }
                    }
                }

                if (!$disablelocal and file_exists("$this->localroot/{$dep}_local/$file.php")) {
                    include("$this->localroot/{$dep}_local/$file.php");
                }
            }

        } else {
            if (!$location = core_component::get_plugin_directory($plugintype, $pluginname) or !is_dir($location)) {
                return array();
            }
            if ($plugintype === 'mod') {
                // Bloody mod hack.
                $file = $pluginname;
            } else {
                $file = $plugintype . '_' . $pluginname;
            }
            $string = array();
            // First load English pack.
            if (!file_exists("$location/lang/en/$file.php")) {
                // English pack does not exist, so do not try to load anything else.
                return array();
            }
            include("$location/lang/en/$file.php");
            $enstring = $string;
            // And then corresponding local english if present.
            if (!$disablelocal and file_exists("$this->localroot/en_local/$file.php")) {
                include("$this->localroot/en_local/$file.php");
            }

            // Now loop through all langs in correct order.
            $deps = $this->get_language_dependencies($lang);

            if (empty($deps)) {
                // This allows us to override also English strings.
                $deps = array('en');
            }

            foreach ($deps as $dep) {
                // Legacy location - used by contrib only.
                if (file_exists("$location/lang/$dep/$file.php")) {
                    include("$location/lang/$dep/$file.php");
                }
                // The main lang string location.
                if (file_exists("$this->otherroot/$dep/$file.php")) {
                    include("$this->otherroot/$dep/$file.php");
                }

                // Custom feature that allows plugins to override strings of other plugins (See MDL-46582).
                foreach (core_component::get_plugin_types() as $plugintype => $plugintypedir) {
                    foreach (core_component::get_plugin_list($plugintype) as $pluginname => $plugindir) {
                        $filename = "$plugindir/lang/$dep/{$file}.php";

                        if (file_exists($filename)) {
                            include($filename);
                        }
                    }
                }

                // Local customisations.
                if (!$disablelocal and file_exists("$this->localroot/{$dep}_local/$file.php")) {
                    include("$this->localroot/{$dep}_local/$file.php");
                }
            }
        }

        // We do not want any extra strings from other languages - everything must be in en lang pack.
        $string = array_intersect_key($string, $enstring);

        if (!$disablelocal) {
            // Now we have a list of strings from all possible sources,
            // cache it in MUC cache if not already there.
            if ($cachedstring === false) {
                $this->cache->set($cachekey, $string);
            }
        }
        return $string;
    }
}
