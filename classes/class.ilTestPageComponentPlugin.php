<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

/**
 * Test Page Component plugin
 * @author Fred Neumann <fred.neumann@gmx.de>
 * @author Alexander Killing <killing@leifos.de>
 */
class ilTestPageComponentPlugin extends ilPageComponentPlugin
{
    /**
     * Get plugin name
     * @return string
     */
    public function getPluginName() : string
    {
        return "TestPageComponent";
    }

    /**
     * Check if parent type is valid
     */
    public function isValidParentType(string $a_parent_type) : bool
    {
        // test with all parent types
        return true;
    }

    /**
     * Handle an event
     * @param string $a_component
     * @param string $a_event
     * @param mixed  $a_parameter
     */
    public function handleEvent(string $a_component, string $a_event, $a_parameter) : void
    {
        $_SESSION['pctpc_listened_event'] = array('time' => time(), 'event' => $a_event);
    }

    /**
     * This function is called when the page content is cloned
     * @param array  $a_properties     properties saved in the page, (should be modified if neccessary)
     * @param string $a_plugin_version plugin version of the properties
     */
    public function onClone(array &$a_properties, string $a_plugin_version) : void
    {
        global $DIC;
        $mt = $DIC->ui()->mainTemplate();
        if ($file_id = $a_properties['page_file']) {
            try {
                include_once("./Modules/File/classes/class.ilObjFile.php");
                $fileObj = new ilObjFile($file_id, false);
                $newObj = clone($fileObj);
                $newObj->setId(0);
                $new_id = $newObj->create();
                $newObj = new ilObjFile($new_id, false);
                $a_properties['page_file'] = $newObj->getId();
                $mt->setOnScreenMessage("info", "File Object $file_id cloned.", true);
            } catch (Exception $e) {
                $mt->setOnScreenMessage("failure", $e->getMessage(), true);
            }
        }

        if ($additional_data_id = $a_properties['additional_data_id']) {
            $data = $this->getData($additional_data_id);
            $id = $this->saveData($data);
            $a_properties['additional_data_id'] = $id;
        }
    }

    /**
     * This function is called before the page content is deleted
     * @param array  $a_properties     properties saved in the page (will be deleted afterwards)
     * @param string $a_plugin_version plugin version of the properties
     */
    public function onDelete(array $a_properties, string $a_plugin_version, bool $move_operation = false) : void
    {
        global $DIC;
        $mt = $DIC->ui()->mainTemplate();

        if ($move_operation) {
            return;
        }

        if ($file_id = ($a_properties['page_file'] ?? null)) {
            try {
                include_once("./Modules/File/classes/class.ilObjFile.php");
                $fileObj = new ilObjFile($file_id, false);
                $fileObj->delete();
                $mt->setOnScreenMessage("info", "File Object $file_id deleted.", true);
            } catch (Exception $e) {
                $mt->setOnScreenMessage("failure", $e->getMessage(), true);
            }
        }

        if ($additional_data_id = $a_properties['additional_data_id']) {
            $this->deleteData($additional_data_id);
        }
    }

    /**
     * Recursively copy directory (taken from php manual)
     * @param string $src
     * @param string $dst
     */
    private function rCopy(string $src, string $dst) : void
    {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst);
        }
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->rCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Get additional data by id
     */
    public function getData(int $id) : ?string
    {
        global $DIC;
        $db = $DIC->database();

        $query = "SELECT data FROM pctcp_data WHERE id = " . $db->quote($id, 'integer');
        $result = $db->query($query);
        if ($row = $db->fetchAssoc($result)) {
            return $row['data'];
        }
        return null;
    }

    /**
     * Save new additional data
     */
    public function saveData(string $data) : int
    {
        global $DIC;
        $db = $DIC->database();

        $id = $db->nextId('pctcp_data');
        $db->insert(
            'pctcp_data',
            array(
                'id' => array('integer', $id),
                'data' => array('text', $data)
            )
        );
        return $id;
    }

    /**
     * Update additional data
     */
    public function updateData(int $id, string $data) : void
    {
        global $DIC;
        $db = $DIC->database();

        $db->update(
            'pctcp_data',
            array(
                'data' => array('text', $data)
            ),
            array(
                'id' => array('integer', $id)
            )
        );
    }

    /**
     * Delete additional data
     */
    public function deleteData(int $id) : void
    {
        global $DIC;
        $db = $DIC->database();

        $query = "DELETE FROM pctcp_data WHERE ID = " . $db->quote($id, 'integer');
        $db->manipulate($query);
    }
}