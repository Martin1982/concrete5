<?

defined('C5_EXECUTE') or die("Access Denied.");

/**
*
* @package Utilities
* The object class is extended by most objects in Concrete, but is mostly internal
* @access private 
*
*/
	class Concrete5_Library_Object {

        /**
         * @var string
         */
        public $error = '';
		
		/* TODO: move these into an error class */

        /**
         * @param int $error
         */
        function loadError($error) {
			$this->error = $error;
		}

        /**
         * @return bool|string
         */
        function isError() {
			$args = func_get_args();
			if (isset($args[0]) && $args[0]) {
				return $this->error == $args[0];
			} else {
				return $this->error;
			}
		}

        /**
         * @return int
         */
        function getError() {
			return $this->error;
		}

        /**
         * @param array $arr
         */
        public function setPropertiesFromArray($arr) {
			foreach($arr as $key => $prop) {
				$this->{$key} = $prop;
			}
		}

        /**
         * @param string $file
         * @return string
         */
        public static function camelcase($file) {
			// turns "asset_library" into "AssetLibrary"
			$r1 = ucwords(str_replace(array('_', '-', '/'), ' ', $file));
			$r2 = str_replace(' ', '', $r1);
			return $r2;		
		}

        /**
         * @param string $string
         * @return string
         */
        public static function uncamelcase($string) {
			$v = preg_split('/([A-Z])/', $string, false, PREG_SPLIT_DELIM_CAPTURE);
			$a = array();
			array_shift($v);
			for($i = 0; $i < count($v); $i++) {
				if ($i % 2) {
					if (function_exists('mb_strtolower')) {
						$a[] = mb_strtolower($v[$i - 1] . $v[$i], APP_CHARSET);
					} else {
						$a[] = strtolower($v[$i - 1] . $v[$i]);
					}
				}
			}
			return str_replace('__', '_', implode('_', $a));
		}		
	
	}
