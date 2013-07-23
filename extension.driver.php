<?php

	include_once(EXTENSIONS . '/jet_pack/lib/class.rule.php');

	Class extension_jet_pack extends Extension {

		// About this extension:
		public function about() {
			return array(
				'name' => 'Jet Pack',
				'version' => '0.3',
				'release-date' => 'unreleased',
				'author' => array(
					array(
						'name' => 'Chay Palmer',
						'website' => 'http://www.randb.com.au',
						'email' => 'chay@randb.com.au'
					),
					array(
						'name' => 'Brendan Abbott',
						'email' => 'brendan@bloodbone.ws'
					)
				),
				'description' => 'Allows email notifications to be sent to particular Authors when an Author creates new entries.'
			);
		}

		// Set the delegates:
		public function getSubscribedDelegates() {
			return array(
				array(
					'page' => '/publish/new/',
					'delegate' => 'EntryPostCreate',
					'callback' => 'checkForRules'
				)
			);
		}

		public function fetchNavigation() {
			return array(
				array(
					'location' => __('System'),
					'name' => __('Jet Pack Rules'),
					'link' => '/rules/',
					'limit' => 'developer'
				)
			);
		}

		public function install() {

			$etm['handle'] = 'email_template_manager';

			// Check for Email Template Manager
			if(!in_array(EXTENSION_ENABLED, Symphony::ExtensionManager()->fetchStatus($etm))) {
				Administration::instance()->Page->pageAlert(__('Please make sure that the <a href=\'%s\'>Email Template Manager</a> extension is installed before enabling Jet Pack.', array('http://symphony-cms.com/download/extensions/view/64322/')), Alert::ERROR);
				return false;
			}

			$ar['handle'] = 'author_roles';

			// Check for Author Roles
			if(!in_array(EXTENSION_ENABLED, Symphony::ExtensionManager()->fetchStatus($ar))) {
				Administration::instance()->Page->pageAlert(__('Please make sure that the <a href=\'%s\'>Author Roles</a> extension is installed before enabling Jet Pack.', array('http://symphony-cms.com/download/extensions/view/62849/')), Alert::ERROR);
				return false;
			}

			return Symphony::Database()->query("
				CREATE TABLE `tbl_jet_pack_rules` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `section-id` int(11) NOT NULL,
				  `cause-role-id` int(11) NOT NULL,
				  `effect-role-id` int(11) NOT NULL,
				  `template` varchar(255) NOT NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

		public function uninstall() {
			return Symphony::Database()->query("
				DROP TABLE IF EXISTS `tbl_jet_pack_rules`;
			");
		}

		public static function baseURL() {
			return SYMPHONY_URL . '/extension/jet_pack/';
		}

		public function checkForRules($context) {
			$entry_id = $context['entry']->get('id');
			$author_id = Administration::instance()->Author->get('id');
			$author_roles = Symphony::ExtensionManager()->getInstance('author_roles');
			$section_id = $context['section']->get('id');
			$role_id = $author_roles->getAuthorRole($author_id);

			$rules = Symphony::Database()->fetchRow(0, sprintf("
					SELECT `id`
					FROM `tbl_jet_pack_rules`
					WHERE
						`section-id` = %d
					AND
						`cause-role-id` = %d
					ORDER BY `id` ASC
					LIMIT 1
				",
				$section_id,
				$role_id
			));

			if(empty($rules)){
				return true;
			}
			else{
				$this->applyRule($rules['id'], $author_id, $entry_id, $context['section']->get('handle'));
			}
		}

		public function applyRule($rule_id, $author_id, $entry_id, $section_id) {
			include_once(EXTENSIONS . '/email_template_manager/lib/class.emailtemplatemanager.php');

			$rule = RuleManager::fetch($rule_id);
			$template = EmailTemplateManager::load($rule->get('template'));

			if(is_null($template->getXML())){
				try{
					$template->setXML($template->processDatasources()->generate(true, 0));
				}
				catch(Exception $e){
					$error = $template->getError();
					throw new EmailTemplateException('Error including XML for rendering: ' . $e->getMessage());
				}
			}

			$template->parseProperties();
			$recipients = $this->getRecipients($rule->get('effect-role-id'));



			$template->{'recipients'} = $emails;
			$template->parseProperties();
			$output = $template->render();


			$author = AuthorManager::fetchByID($author_id);
			$entry_url = SYMPHONY_URL . '/publish/' . $section_id . '/edit/' . $entry_id . '/';
			$entry_html_url = '<a href="'. $entry_url .'"> View Entry </a>';


			$search = array('{$jet-pack-user}', '{$jet-pack-section}', '{$jet-pack-link}');

			$replace = array($author->getFullName(), $section_id, $entry_url);
			$text_email = str_replace($search, $replace, $output['plain']);

			$replace = array($author->getFullName(), $section_id, $entry_html_url);
			$html_email = str_replace($search, $replace, $output['html']);



			$email['content']['html'] = $html_email;
			$email['content']['plain'] = $text_email;
			$email['subject'] = $template->subject;
			$email['reply_to_name'] = $template->reply_to_name;
			$email['reply_to_email_address'] = $template->reply_to_email_address;

			$this->send($email, $recipients);
		}

		public function getRecipients($role_id) {
			$authors = Symphony::Database()->fetch(sprintf('
				SELECT
					`authors`.`id`,
					`authors`.`first_name`,
					`authors`.`last_name`,
					`authors`.`email`
				FROM
					`tbl_authors` AS `authors`
				LEFT JOIN
					`tbl_author_roles_authors` AS `author_roles`
				ON
					(`authors`.id = `author_roles`.id_author)
				WHERE
					`author_roles`.`id_role` = %d
				',
				$role_id
			));

			$recipients = array();
			foreach($authors as $author){
				$name = $author['first_name'] . ' ' . $author['last_name'];
				$recipients[$name] = $author['email'];
			}
			return $recipients;
		}

		public function send($msg, $recipients) {
			$email = Email::create();

			try {
				$email->subject = $msg['subject'];
				$email->recipients = $recipients;

				$email->text_plain = $msg['content']['plain'];
				$email->text_html = $msg['content']['html'];

				// Optional: overwrite default sender
				$email->sender_name = $msg['reply_to_name'];
				$email->sender_email_address = $msg['reply_to_email_address'];

				// Optional: set a different text encoding (default is 'quoted-printable')
				$email->text_encoding = 'base64';

				return $email->send();
			}
			catch(EmailGatewayException $e){
				throw new SymphonyErrorPage('Error sending email. Gateway Error:' . $e->getMessage());
			}
			catch(EmailException $e){
				throw new SymphonyErrorPage('Error sending email. ' . $e->getMessage());
			}
		}

	}