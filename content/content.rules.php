<?php


	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.eventmanager.php');

	Class contentExtensionJet_packRules extends AdministrationPage {

		public function __viewIndex() {
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Jet Pack'))));

			$this->appendSubheading(__('Jet Pack Rules'), Widget::Anchor(
				__('Create New'), Administration::instance()->getCurrentPageURL().'new/', __('Create a Rule'), 'create button', NULL, array('accesskey' => 'c')
			));

			$aTableHead = array(
				array(__('Section'), 'col'),
				array(__('Cause Role'), 'col'),
				array(__('Email Template'), 'col'),
				array(__('Notifies Role'), 'col')
			);

			$aTableBody = array();

			$rules = RuleManager::fetch();

			if(!is_array($rules) || empty($rules)){
				$aTableBody = array(Widget::TableRow(
					array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead)))
				));
			}

			else {
				$sectionManager = new SectionManager(Administration::instance());
				$authorRoles = Symphony::ExtensionManager()->getInstance('author_roles');
				$with_selected_rules = array();
				$section_arr = array();
				$author_roles_arr = array();

				$sectionManager = new SectionManager($this);
				$sections = $sectionManager->fetch();

				foreach($sections as $section){
					$section_arr[$section->get('id')] = $section->get('name');
				}

				foreach($authorRoles->getRoles() as $role){
					$author_roles_arr[$role['id']] = $role['name'];
				}

				foreach($rules as $rule){
					// Setup each cell
					$td1 = Widget::TableData(Widget::Anchor(
						$section_arr[$rule->get('section-id')], Administration::instance()->getCurrentPageURL().'edit/' . $rule->get('id') . '/', null, 'content'
					));
					$td1->appendChild(Widget::Input("items[{$rule->get('id')}]", null, 'checkbox'));

					$td2 = Widget::TableData($author_roles_arr[$rule->get('cause-role-id')]);

					$template = EmailTemplateManager::load($rule->get('template'));
					$td3 = Widget::TableData(Widget::Anchor(
						$template->about['name'], SYMPHONY_URL . '/extension/email_template_manager/templates/edit/' . $rule->get('template') . '/')
					);

					$td4 = Widget::TableData($author_roles_arr[$rule->get('effect-role-id')]);

					// Add cells to a row
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3, $td4));
				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead),
				NULL,
				Widget::TableBody($aTableBody),
				'selectable'
			);

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				0 => array(null, false, __('With Selected...')),
				2 => array('delete', false, __('Delete'), 'confirm')
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);

		}

		public function __actionIndex() {
			$checked = (is_array($_POST['items'])) ? array_keys($_POST['items']) : null;

			switch ($_POST['with-selected']) {
				case 'delete':
					foreach($checked as $rule_id) {
						RuleManager::delete($rule_id);
					}
					redirect(extension_jet_pack::baseURL() . 'rules/');
					break;
			}
		}

		public function __viewNew() {
			$this->__viewEdit();
		}

		public function __viewEdit() {
			$isNew = true;

			// Verify rule exists
			if($this->_context[0] == 'edit') {
				$isNew = false;

				if(!$rule_id = $this->_context[1]) redirect(extension_jet_pack::baseURL() . 'rules/');

				if(!$existing = RuleManager::fetch($rule_id)){
					throw new SymphonyErrorPage(__('The Rule you requested to edit does not exist.'), __('Rule not found'), 'error');
				}
			}

			// Add in custom assets
			/*
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/jet_pack/assets/jetpack.rules.css', 'screen', 101);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/jet_pack/assets/jetpack.rules.js', 104);
			*/

			// Append any Page Alerts from the form's
			if(isset($this->_context[2])){
				switch($this->_context[2]){
					case 'saved':
						$this->pageAlert(
							__(
								'Rule updated at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Rules</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									extension_jet_pack::baseURL() . 'rules/new/',
									extension_jet_pack::baseURL() . 'rules/'
								)
							),
							Alert::SUCCESS);
						break;

					case 'created':
						$this->pageAlert(
							__(
								'Rule created at %1$s. <a href="%2$s" accesskey="c">Create another?</a> <a href="%3$s" accesskey="a">View all Rules</a>',
								array(
									DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
									extension_jet_pack::baseURL() . 'rules/new/',
									extension_jet_pack::baseURL() . 'rules/'
								)
							),
							Alert::SUCCESS);
						break;
				}
			}

			// Has the form got any errors?
			$formHasErrors = (is_array($this->_errors) && !empty($this->_errors));

			if($formHasErrors) $this->pageAlert(
				__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR
			);

			$this->setPageType('form');
			$sectionManager = new SectionManager($this);
			$authorRoles = Symphony::ExtensionManager()->getInstance('author_roles');
			$sections = $sectionManager->fetch();

			if($isNew) {
				$this->setTitle(__('Symphony &ndash; Jet Pack'));
				$this->appendSubheading(__('Untitled'));

				$fields = array(
					'section-id' => null
				);
			}
			else {
				$existing_section = $sectionManager->fetch($existing->get('section-id'));

				$this->setTitle(__('Symphony &ndash; Jet Pack &ndash; ') . $existing_section->get('name'));
				$this->appendSubheading($existing_section->get('name'));

				if(isset($_POST['fields'])){
					$fields = $_POST['fields'];
				}
				else{
					$fields = array(
						'section-id' => $existing->get('section-id'),
						'cause-role-id' =>  $existing->get('cause-role-id'),
						'effect-role-id' =>  $existing->get('effect-role-id'),
						'template' =>  $existing->get('template')
					);
				}
			}

			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings type-file');
			$fieldset->appendChild(new XMLElement('legend', __('Setup')));
			$fieldset->appendChild(
				new XMLElement('p', __('This Rule defines what will happen when an Author create entries in a particular section. The Rule is active as soon as it is created.'), array('class' => 'help'))
			);

			$group = new XMLElement('div', null, array('class' => 'group'));
			$div = new XMLElement('div');

		// Cause Role
			$author_roles = $authorRoles->getRoles();
			if(empty($author_roles)) $this->pageAlert(
				__('No Author Roles have been created yet. <a href=\'%s\'>Create one?</a>', array(SYMPHONY_URL . '/extension/author_roles/roles/new/')), Alert::ERROR
			);

			foreach($authorRoles->getRoles() as $rule) {
				$cause_roles[] = array($rule['id'], ($fields['cause-role-id'] == $rule['id']), $rule['name']);
				$effect_roles[] = array($rule['id'], ($fields['effect-role-id'] == $rule['id']), $rule['name']);
			}

			$label = Widget::Label(__('When an entry is created by Role'));
			$label->appendChild(Widget::Select('fields[cause-role-id]',$cause_roles));

			if(isset($this->_errors['cause-role-id'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['cause-role-id']));
			else $div->appendChild($label);

		// Section
			$section_options = array();
			foreach($sections as $section){
				$section_options[] = array($section->get('id'), ($fields['section-id'] ==  $section->get('id')), $section->get('name'));
			}

			$label = Widget::Label(__('In this Section'));
			$label->appendChild(Widget::Select('fields[section-id]',$section_options));

			if(isset($this->_errors['section-id'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['section-id']));
			else $div->appendChild($label);

		// Effect Role
			$label = Widget::Label(__('Notify all authors of Role'));
			$label->appendChild(Widget::Select('fields[effect-role-id]',$effect_roles));

			if(isset($this->_errors['effect-role-id'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['effect-role-id']));
			else $div->appendChild($label);

		// Template
			foreach(EmailTemplateManager::listAll() as $name => $template){
				$template_options[] = array($name, ($fields['template'] == $name), $template->about['name']);
			}

			if(empty($template_options)) $this->pageAlert(
				__('No Email Templates have been created yet. <a href=\'%s\'>Create one?</a>', array(SYMPHONY_URL . '/extension/email_template_manager/templates/new/')), Alert::ERROR
			);

			$label = Widget::Label(__('By sending an email using this Template'));
			$label->appendChild(Widget::Select('fields[template]',$template_options));

			if(isset($this->_errors['template'])) $div->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['template']));
			else $div->appendChild($label);

		// Save
			$group->appendChild($div);
			$fieldset->appendChild($group);
			$this->Form->appendChild($fieldset);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));

			$this->Form->appendChild($div);
		}

		public function __actionNew() {
			return $this->__actionEdit();
		}

		public function __actionEdit() {
			if(array_key_exists('delete', $_POST['action'])) {
				return $this->__actionDelete($this->_context[1], extension_jet_pack::baseURL() . 'rules/');
			}
			else if(array_key_exists('save', $_POST['action'])) {
				$isNew = ($this->_context[0] !== "edit");
				$fields = $_POST['fields'];

				// If we are editing, we need to make sure the current `$rule_id` exists
				if(!$isNew) {
					if(!$rule_id = $this->_context[1]) redirect(extension_jet_pack::baseURL() . 'rules/');

					if(!$existing = RuleManager::fetch($rule_id)){
						throw new SymphonyErrorPage(__('The Rule you requested to edit does not exist.'), __('Rule not found'), 'error');
					}
				}

				$data['rules'] = array(
					'section-id' => trim($fields['section-id']),
					'cause-role-id' => trim($fields['cause-role-id']),
					'effect-role-id' => trim($fields['effect-role-id']),
					'template' => trim($fields['template'])
				);

				if($isNew) {
					if($rule_id = RuleManager::add($data)) {
						redirect(extension_jet_pack::baseURL() . 'rules/edit/' . $rule_id . '/created/');
					}
				}
				else {
					if(RuleManager::edit($rule_id, $data)) {
						redirect(extension_jet_pack::baseURL() . 'rules/edit/' . $rule_id . '/saved/');
					}
				}
			}
		}

		public function __actionDelete($rule_id = null, $redirect = null) {
			if(array_key_exists('delete', $_POST['action'])) {
				if(!$rule_id) redirect(extension_jet_pack::baseURL() . 'rules/');

				if(!$existing = RuleManager::fetch($rule_id)){
					throw new SymphonyErrorPage(__('The Rule you requested to delete does not exist.'), __('Rule not found'), 'error');
				}

				RuleManager::delete($rule_id);

				if(!is_null($redirect)) redirect($redirect);
			}
		}
	}