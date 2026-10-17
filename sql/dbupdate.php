<#1>
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
 * Test Page Component  plugin: database update script
 */

/**
 * Additional values
 */
if (!$ilDB->tableExists('pctcp_data')) {
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),

        'data' => array(
            'type' => 'text',
            'length' => 255,
            'notnull' => false,
        ),
    );
    $ilDB->createTable('pctcp_data', $fields);
    $ilDB->addPrimaryKey('pctcp_data', array('id'));
    $ilDB->createSequence('pctcp_data');
}
?>