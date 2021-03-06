<?php

	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentExtensionFileManagerProperties extends AdministrationPage{

		private $_FileManager;

		function __construct(&$parent){
			parent::__construct($parent);

			$this->_FileManager =& $this->_Parent->ExtensionManager->create('filemanager');

			$this->setTitle('Symphony &ndash; File Manager &ndash; ' . str_replace(DOCROOT . $this->_FileManager->getStartLocation(), NULL, $_GET['file']));
		}

		function action(){

			$FileManager =& $this->_Parent->ExtensionManager->create('filemanager');
			$file = new File(DOCROOT . $FileManager->getStartLocation() . $_GET['file']);

			if(isset($_POST['action']['save'])){
				$fields = $_POST['fields'];

				$file->setName($fields['name']);

				if(isset($fields['contents'])) $file->setContents($fields['contents']);

				$file->setPermissions($fields['permissions']);

				$relpath = str_replace(DOCROOT . $FileManager->getStartLocation(), NULL, dirname($_GET['file']));

				if($file->isWritable())
					redirect($FileManager->baseURL() . 'properties/?file=' . rtrim(dirname($_GET['file']), '/') . '/' . $file->name() . '&result=saved');

				else redirect($FileManager->baseURL() . 'browse/' . $relpath);

			}

			elseif(isset($_POST['action']['delete'])){

				General::deleteFile($file->path() . '/' . $file->name());

				$relpath = str_replace(DOCROOT . $FileManager->getStartLocation(), NULL, dirname($_GET['file']));

				redirect($FileManager->baseURL() . 'browse/' . $relpath);

			}
		}

		function view(){

			$this->Form->setAttribute('action', extension_filemanager::baseURL() . 'properties/?file=' . $_GET['file']);

			$file = new File(DOCROOT . $this->_FileManager->getStartLocation() . $_GET['file']);

			$FileManager =& $this->_Parent->ExtensionManager->create('filemanager');

			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

			if($formHasErrors) $this->pageAlert('An error occurred while processing this form. <a href="#error">See below for details.</a>', AdministrationPage::PAGE_ALERT_ERROR);

			if(isset($_GET['result'])){
				switch($_GET['result']){

					case 'saved':

						$this->pageAlert(
							__(
								__('%s updated successfully'),
								array(($file->isDir() ? __('Folder') : __('File')))
							),
							Alert::SUCCESS
						);

						break;

				}
			}

			$this->setPageType('form');

			$path = extension_filemanager::baseURL() . 'browse/';
			$breadcrumb = '';
			$pathelements = explode('/', $_GET['file']);
			foreach($pathelements as $element) {
				if($element != '') {
					$path .= $element . '/';
					$breadcrumb .= ' / ' . ($element == end($pathelements) ? $element : Widget::Anchor($element, $path)->generate());
				}
			}

			$this->appendSubheading(trim($FileManager->getStartLocationLink(), '/') . $breadcrumb);

			$fields = array();

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');

			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input('fields[name]', General::sanitize($file->name())));

			if(isset($this->_errors['name'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['name']));
			else $div->appendChild($label);

			$label = Widget::Label(__('Permissions'));
			$label->appendChild(Widget::Input('fields[permissions]', General::sanitize($file->permissions())));

			if(isset($this->_errors['permissions'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['permissions']));
			else $div->appendChild($label);

			$fieldset->appendChild($div);

			$this->Form->appendChild($fieldset);


			if(!$file->isDir() && in_array(File::fileType($file->name()), array(File::CODE, File::DOC))){

				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('class', 'settings');
				$fieldset->appendChild(new XMLElement('legend', __('Editor')));

				$label = Widget::Label(__('Contents'));
				$label->appendChild(Widget::Textarea('fields[contents]', '25', '50', General::sanitize($file->contents()), array('class' => 'code')));

				if(isset($this->_errors['contents'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['contents']));
				else $fieldset->appendChild($label);

				$this->Form->appendChild($fieldset);

			}

			if(!$file->isDir() && File::fileType($file->name()) == File::IMAGE){

				$fieldset = new XMLElement('fieldset');
				$fieldset->setAttribute('class', 'settings');
				$fieldset->appendChild(new XMLElement('legend', __('Preview')));

				$img = new XMLElement('img');
				$img->setAttribute('src', URL . $FileManager->getStartLocation() . $_GET['file']);
				$img->setAttribute('alt', $file->name());
				$fieldset->appendChild($img);

				$this->Form->appendChild($fieldset);

			}

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');

			if(is_writeable(DOCROOT . $this->_FileManager->getStartLocation() . $_GET['file'])) {

				$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));

				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'confirm delete', 'title' => __('Delete this ') . ($file->isDir() ? __('Folder') : __('File'))));
				$div->appendChild($button);
			}
			else {
				$notice = new XMLElement('p', __('The server does not have permission to edit this file.'));
				$notice->setAttribute('class','inactive');
				$div->appendChild($notice);
			}

			$this->Form->appendChild($div);

		}
	}

?>
