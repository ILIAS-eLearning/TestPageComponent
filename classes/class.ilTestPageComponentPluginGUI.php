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
 * Test Page Component GUI
 * @author            Fred Neumann <fred.neumann@gmx.de>
 * @author            Alexander Killing <killing@leifos.de>
 * @ilCtrl_isCalledBy ilTestPageComponentPluginGUI: ilPCPluggedGUI
 * @ilCtrl_isCalledBy ilTestPageComponentPluginGUI: ilUIPluginRouterGUI
 */
class ilTestPageComponentPluginGUI extends ilPageComponentPluginGUI
{
    protected $lng;
    protected $ctrl;
    protected $tpl;

    public function __construct()
    {
        global $DIC;

        parent::__construct();

        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC['tpl'];
    }

    /**
     * Execute command
     */
    public function executeCommand() : void
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {
            default:
                // perform valid commands
                $cmd = $this->ctrl->getCmd();
                if (in_array($cmd, array("create", "save", "edit", "update", "cancel", "downloadFile"))) {
                    $this->$cmd();
                }
                break;
        }
    }

    /**
     * Create
     */
    public function insert() : void
    {
        $form = $this->initForm(true);
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Save new pc example element
     */
    public function create() : void
    {
        $form = $this->initForm(true);
        if ($this->saveForm($form, true)) {
            ;
        }
        {
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("msg_obj_modified"), true);
            $this->returnToParent();
        }
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    public function edit() : void
    {
        $form = $this->initForm();

        $this->tpl->setContent($form->getHTML());
    }

    public function update() : void
    {
        $form = $this->initForm(false);
        if ($this->saveForm($form, false)) {
            ;
        }
        {
            $this->tpl->setOnScreenMessage("success", $this->lng->txt("msg_obj_modified"), true);
            $this->returnToParent();
        }
        $form->setValuesByPost();
        $this->tpl->setContent($form->getHTML());
    }

    /**
     * Init editing form
     */
    protected function initForm(bool $a_create = false) : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();

        // page value
        $page_value = new ilTextInputGUI('page_value', 'page_value');
        $page_value->setMaxLength(40);
        $page_value->setSize(40);
        $page_value->setRequired(true);
        $form->addItem($page_value);

        // page file
        $page_file = new ilFileInputGUI('page_file', 'page_file');
        $page_file->setALlowDeletion(true);
        $form->addItem($page_file);

        // additional data
        $data = new ilTextInputGUI('additional_data', 'additional_data');
        $data->setMaxLength(40);
        $data->setSize(40);
        $form->addItem($data);

        // page info values
        foreach ($this->getPageInfo() as $key => $value) {
            $info = new ilNonEditableValueGUI($key);
            $info->setValue($value);
            $form->addItem($info);
        }

        // save and cancel commands
        if ($a_create) {
            $this->addCreationButton($form);
            $form->addCommandButton("cancel", $this->lng->txt("cancel"));
            $form->setTitle($this->plugin->getPluginName());
        } else {
            $prop = $this->getProperties();
            $page_value->setValue($prop['page_value']);
            $data->setValue($this->plugin->getData($prop['additional_data_id']));

            $form->addCommandButton("update", $this->lng->txt("save"));
            $form->addCommandButton("cancel", $this->lng->txt("cancel"));
            $form->setTitle($this->plugin->getPluginName());
        }

        $form->setFormAction($this->ctrl->getFormAction($this));
        return $form;
    }

    protected function saveForm(ilPropertyFormGUI $form, bool $a_create) : bool
    {
        if ($form->checkInput()) {
            $properties = $this->getProperties();

            // value saved in the page
            $properties['page_value'] = $form->getInput('page_value');

            // file object
            if (isset($_FILES["page_file"]["name"])) {
                $old_file_id = empty($properties['page_file']) ? null : $properties['page_file'];

                $fileObj = new ilObjFile((int) $old_file_id, false);
                $fileObj->setType("file");
                $fileObj->setTitle($_FILES["page_file"]["name"]);
                $fileObj->setDescription("");
                $fileObj->setFileName($_FILES["page_file"]["name"]);
                $fileObj->setMode("filelist");
                if (empty($old_file_id)) {
                    $fileObj->create();
                } else {
                    $fileObj->update();
                }
                // upload file to filesystem
                if ($_FILES["page_file"]["tmp_name"] !== "") {
                    $fileObj->getUploadFile(
                        $_FILES["page_file"]["tmp_name"],
                        $_FILES["page_file"]["name"]
                    );
                }

                $properties['page_file'] = $fileObj->getId();
            }

            // additional data
            $id = $properties['additional_data_id'] ?? null;
            if (empty($id)) {
                $id = $this->plugin->saveData($form->getInput('additional_data'));
                $properties['additional_data_id'] = $id;
            } else {
                $this->plugin->updateData($id, $form->getInput('additional_data'));
            }

            if ($a_create) {
                return $this->createElement($properties);
            } else {
                return $this->updateElement($properties);
            }
        }

        return false;
    }

    /**
     * Cancel
     */
    public function cancel()
    {
        $this->returnToParent();
    }

    /**
     * Get HTML for element
     * @param string    page mode (edit, presentation, print, preview, offline)
     * @return string   html code
     */
    public function getElementHTML($a_mode, $a_properties, $a_plugin_version) : string
    {
        $display = array_merge($a_properties, $this->getPageInfo());

        // show properties stores in the page
        $html = '<pre>' . print_r($display, true);

        // show additional data
        if (!empty($a_properties['additional_data_id'])) {
            $data = $this->plugin->getData($a_properties['additional_data_id']);
            $html .= 'Data: ' . $data . "\n";
        }

        // show uploaded file
        if (!empty($a_properties['page_file'])) {
            try {
                $fileObj = new ilObjFile($a_properties['page_file'], false);

                // security
                $_SESSION[__CLASS__]['allowedFiles'][$fileObj->getId()] = true;

                $this->ctrl->setParameter($this, 'id', $fileObj->getId());
                $url = $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', 'ilTestPageComponentPluginGUI'),
                    'downloadFile');
                $title = $fileObj->getPresentationTitle();

            } catch (Exception $e) {
                $url = "";
                $title = $e->getMessage();
            }

            $html .= 'File: <a href="' . $url . '">' . $title . '</a>' . "\n";
        }

        // Show listened event
        if ($event = ($_SESSION['pctpc_listened_event'] ?? null)) {
            $html .= "\n";
            $html .= 'Last Auth Event: ' . ilDatePresentation::formatDate(new ilDateTime($event['time'], IL_CAL_UNIX));
            $html .= ' ' . $event['event'];
        }

        $html .= '</pre>';

        return $html;
    }

    /**
     * download file of file lists
     */
    function downloadFile() : void
    {
        $file_id = (int) $_GET['id'];
        if ($_SESSION[__CLASS__]['allowedFiles'][$file_id]) {
            $fileObj = new ilObjFile($file_id, false);
            $fileObj->sendFile();
        } else {
            throw new ilException('not allowed');
        }
    }

    /**
     * Get information about the page that embeds the component
     * @return    array    key => value
     */
    public function getPageInfo() : array
    {
        return array(
            'page_id' => $this->plugin->getPageId(),
            'parent_id' => $this->plugin->getParentId(),
            'parent_type' => $this->plugin->getParentType()
        );
    }
}