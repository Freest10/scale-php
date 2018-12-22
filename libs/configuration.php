<?php
	class MainConfiguration {
		private static $instance = null;
		private $ini    = array();
		private $edited = false;

		private function __construct() {
			if(!is_readable(CONFIG_INI_PATH)) {
				throw new \SystemException('', "backend.errors.can_not_find_configuration_file", "modal");
			}

			$this->ini = parse_ini_file(CONFIG_INI_PATH, true);
			if(isset($this->ini['system']) && isset($this->ini['system']['session-lifetime']) && $this->ini['system']['session-lifetime'] < 1) {
 			   $this->ini['system']['session-lifetime'] = 1440;
			}
		}

		public function __destruct() {
			if($this->edited) {
				$this->writeIni();
			}
		}

		public static function getInstance() {
			if(!self::$instance) {
				self::$instance = new MainConfiguration();
			}
			return self::$instance;
		}

		public function getParsedIni() {
			return $this->ini;
		}

		public function get($section, $variable) {
			if(isset($this->ini[$section]) &&
			   isset($this->ini[$section][$variable])) {
				$value = $this->ini[$section][$variable];
				$value = $this->unescapeValue($value);
				if ($section == 'system' && $variable == 'session-lifetime' && $value < 1) $value = 1440;
				return $value;
			} else return null;
		}

		public function set($section, $variable, $value) {
			if(!isset($this->ini[$section])) {
				$this->ini[$section] = array();
			}
			if($value === null && isset($this->ini[$section][$variable])) {
				unset($this->ini[$section][$variable]);
			} else {
				if ($section == 'system' && $variable == 'session-lifetime' && $value < 1) $value = 1440;
				$this->ini[$section][$variable] = $value;
			}
			$this->edited = true;
		}

		public function getList($section) {
			if(isset($this->ini[$section]) && is_array($this->ini[$section])) {
				return array_keys($this->ini[$section]);
			} return null;
		}

		private function writeIni() {
			$iniString = "";
			foreach($this->ini as $sname => $section) {
				if(empty($section)) continue;
				$iniString .= "[{$sname}]\n";
				foreach($section as $name => $value) {
					if(is_array($value)) {
						foreach($value as $sval) {
							$sval = ($sval !== '') ? '"' . $sval . '"' : '';
							$iniString .= "{$name}[] = {$sval}\n";
						}
					} else {
						$value = ($value !== '') ? '"' . $value . '"' : '';
						$iniString .= "{$name} = {$value}\n";
					}
				}
				$iniString .= "\n";
			}
			file_put_contents(CONFIG_INI_PATH, $iniString);
		}

		private function unescapeValue($value) {
			if(is_array($value)) {
				foreach($value as $i => $v) {
					$value[$i] = $this->unescapeValue($v);
				}
				return $value;
			}

			if(strlen($value) >= 2 && substr($value, 0, 1) == "'" && substr($value, -1, 1) == "'") {
				$value = substr($value, 1, strlen($value) - 2);
			}
			return $value;
		}
	};