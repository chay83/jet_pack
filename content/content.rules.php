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
				array(__('For'), 'col'),
				array(__('Email'), 'col')
			);

			$aTableBody = array();
			
			$rules = RuleManager::fetch();
			
			if(!is_array($rules) || empty($rules)){
				$aTableBody = array(Widget::TableRow(
					array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead)))
				));
			}

			else{
				$sectionManager = new SectionManager(Administration::instance());

				$with_selected_rules = array();
				
				$sm = new SectionManager($this);
					
				$sections = $sm->fetch();
				
				$section_arr = array();
				
				foreach($sections as $section){
					$section_arr[$section->get('id')] = $section->get('name');
				}
				
				$author_roles = Symphony::Database()->fetch("SELECT * FROM `tbl_author_roles`");

				$author_roles_arr = array();
				
				foreach($author_roles as $role){
					$author_roles_arr[$role['id']] = $role['name'];
				}
	
				foreach($rules as $rule){
					// Setup each cell
					$td1 = Widget::TableData(Widget::Anchor(
						$section_arr[$rule->get('section')], Administration::instance()->getCurrentPageURL().'edit/' . $rule->get('id') . '/', null, 'content'
					));
					$td1->appendChild(Widget::Input("items[{$rule->get('id')}]", null, 'checkbox'));

					$td2 = Widget::TableData($author_roles_arr[$rule->get('role-1')]);
					
					$td3 = Widget::TableData($author_roles_arr[$rule->get('role-2')]);

					// Add cells to a row
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3));

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
					var_dump(extension_jet_pack::baseURL() . 'rules/');
					exit;
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
					throw new SymphonyErrorPage(__('The rule you requested to edit does not exist.'), __('Role not found'), 'error');
				}
				
			}
			
			// Add in custom assets
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/jet_pack/assets/jetpack.rules.css', 'screen', 101);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/jet_pack/assets/jetpack.rules.js', 104);

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

			if($isNew) {
				$this->setTitle(__('Symphony &ndash; Jet Pack'));
				$this->appendSubheading(__('Untitled'));

				$fields = array(
					'section' => null
				);
			}
			else {
				$sm = new SectionManager($this);
					
				$sections = $sm->fetch();
				
				$section_arr = array();
				
				foreach($sections as $section){
					$section_arr[$section->get('id')] = $section->get('name');
				}
				
				$this->setTitle(__('Symphony &ndash; Jet Pack &ndash; ') . $section_arr[$existing->get('section')]);
				$this->appendSubheading($section_arr[$existing->get('section')]);

				if(isset($_POST['fields'])){
					$fields = $_POST['fields'];
				}
				else{
					$fields = array(
						'section' => $existing->get('section'),
						'role-1' =>  $existing->get('role-1'),
						'role-2' =>  $existing->get('role-2'),
						'template' =>  $existing->get('template')
					);
				}
			}
			
			$sm = new SectionManager($this);
			
			$sections = $sm->fetch();
			
			$section_options = array();
			foreach($sections as $section){
				$temp = array();
				$temp[] = $section->get('id');
				
				if($fields['id'] ==  $section->get('id')){
					$temp[] = true;
				}else{
					$temp[] = false;				
				}
				
				$temp[] = $section->get('name');
				
				$section_options[] = $temp;
			}
			
			$author_rules = Symphony::Database()->fetch("SELECT * FROM `tbl_author_roles`");
			
			foreach($author_rules as $rule){
				$temp = array();
				$temp[] = $rule['id'];
				if($fields['role-1'] ==  $rule['id']){
					$temp[] = true;
				}else{
					$temp[] = false;				
				}
				$temp[] = $rule['name'];
				
				$author_rules_options1[] = $temp;
			}
			
			foreach($author_rules as $rule){
				$temp = array();
				$temp[] = $rule['id'];
				if($fields['role-2'] ==  $rule['id']){
					$temp[] = true;
				}else{
					$temp[] = false;				
				}
				$temp[] = $rule['name'];
				
				$author_rules_options2[] = $temp;
			}
			
			foreach(EmailTemplateManager::listAll() as $name => $template){
				$temp = array();
				
				$temp[] = $name;
				if($fields['template'] ==  $name){
					$temp[] = true;
				}else{
					$temp[] = false;				
				}
				$temp[] = $template->about['name'];
				
				$template_options[] = $temp;
			}
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings type-file');
			$fieldset->appendChild(new XMLElement('legend', __('Essentials')));

			$label = Widget::Label(__('Section'));
			$label->appendChild(Widget::Select('fields[section]',$section_options));

			if(isset($this->_errors['section'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['section']));
			else $fieldset->appendChild($label);
			
			$label = Widget::Label(__('When Entry Created by Role'));
			
			$label->appendChild(Widget::Select('fields[role-1]',$author_rules_options1));

			if(isset($this->_errors['role-1'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['role-1']));
			else $fieldset->appendChild($label);
			
			$label = Widget::Label(__('Notify all members of the rule'));
						
			$label->appendChild(Widget::Select('fields[role-2]',$author_rules_options2));

			if(isset($this->_errors['role-2'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['role-2']));
			else $fieldset->appendChild($label);
			
			$label = Widget::Label(__('Use the Template'));
									
			$label->appendChild(Widget::Select('fields[template]',$template_options));

			if(isset($this->_errors['template'])) $fieldset->appendChild(Widget::wrapFormElementWithError($label, $this->_errors['template']));
			else $fieldset->appendChild($label);

			$this->Form->appendChild($fieldset);
			
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', array('accesskey' => 's')));

			if(!$isNew && $existing->get('id') != Role::PUBLIC_ROLE) {
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete this Role'), 'type' => 'submit', 'accesskey' => 'd'));
				$div->appendChild($button);
			}

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
						throw new SymphonyErrorPage(__('The rule you requested to edit does not exist.'), __('Role not found'), 'error');
					}
				}

				$section = trim($fields['section']);
				$role1 = trim($fields['role-1']);
				$role2 = trim($fields['role-2']);
				$template = trim($fields['template']);

				$data['rules'] = array(
					'section' => $section,
					'role-1' => $role1,
					'role-2' => $role2,
					'template' => $template
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
					throw new SymphonyErrorPage(__('The rule you requested to delete does not exist.'), __('Rule not found'), 'error');
				}

				RuleManager::delete($rule_id);

				if(!is_null($redirect)) redirect($redirect);
			}
		}
	}