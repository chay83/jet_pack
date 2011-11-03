<?php

	Class RuleManager {

		public static $_pool = array();

		public function add(array $data) {
			Symphony::Database()->insert($data['rules'], 'tbl_jet_pack_rules');
			$rule_id = Symphony::Database()->getInsertID();

			return $rule_id;
		}

		public function edit($rule_id, array $data) {
			if(is_null($rule_id)) return false;

			Symphony::Database()->update($data['rules'], 'tbl_jet_pack_rules', "`id` = " . $rule_id);

			return true;
		}

		public static function fetch($rule_id = null) {
			$result = array();
			$return_single = is_null($rule_id) ? false : true;

			if($return_single) {
				// Check static cache for object
				if(in_array($rule_id, array_keys(RuleManager::$_pool))) {
					return RuleManager::$_pool[$rule_id];
				}

				// No cache object found
				if(!$rules = Symphony::Database()->fetch(sprintf("
					SELECT * FROM `tbl_jet_pack_rules` WHERE `id` = %d ORDER BY `id` ASC LIMIT 1",
					$rule_id
				))) {
					return array();
				}
			}
			else {
				$rules = Symphony::Database()->fetch("SELECT * FROM `tbl_jet_pack_rules` ORDER BY `id` ASC");
			}

			foreach($rules as $rule) {
				if(!in_array($rule['id'], array_keys(RuleManager::$_pool))) {
					RuleManager::$_pool[$rule['id']] = new Rule($rule);
				}

				$result[] = RuleManager::$_pool[$rule['id']];
			}


			return $return_single ? current($result) : $result;
		}

		public function delete($rule_id) {
			Symphony::Database()->delete("`tbl_jet_pack_rules`", " `id` = " . $rule_id);

			return true;
		}

	}

	Class Rule {
		private $settings = array();

		public function __construct(array $settings){
			$this->setArray($settings);
		}

		public function set($name, $value) {
			$this->settings[$name] = $value;
		}

		public function setArray(array $array) {
			foreach($array as $name => $value) {
				$this->set($name, $value);
			}
		}

		public function get($name = null) {
			if(is_null($name)) return $this->settings;

			if(!array_key_exists($name, $this->settings)) return null;

			return $this->settings[$name];
		}
	}
