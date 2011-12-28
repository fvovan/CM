<?php

class CM_Response_Resource_JS extends CM_Response_Resource_Abstract {

	public function process() {
		$this->setHeader('Content-Type', 'application/x-javascript');
		$this->enableCache();

		if ($this->_getFilename() == 'internal.js') {
			$content = new CM_File(DIR_PUBLIC . 'static/js/interface.js') . ';' . PHP_EOL;

			$classes = array();
			foreach (self::getSite()->getNamespaces() as $namespace) {
				$classes = array_merge($classes, CM_Util::rglob('*.php', DIR_LIBRARY . $namespace . '/Renderable/'));
				$classes = array_merge($classes, CM_Util::rglob('*.php', DIR_LIBRARY . $namespace . '/Component/'));
				$classes = array_merge($classes, CM_Util::rglob('*.php', DIR_LIBRARY . $namespace . '/FormField/'));
				$classes = array_merge($classes, CM_Util::rglob('*.php', DIR_LIBRARY . $namespace . '/Form/'));
			}

			foreach ($this->_getClasses($classes) as $class) {
				$jsPath = preg_replace('/\.php$/', '.js', $class['path']);
				$properties = file_exists($jsPath) ? new CM_File($jsPath) : null;
				$content .= $this->_printClass($class['name'], $class['parent'], $properties);
			}
		} elseif ($this->_getFilename() == 'library.js') {
			$content = '';
			foreach (CM_Util::rglob('*.js', DIR_PUBLIC . 'static/js/library/') as $path) {
				$content .= new CM_File($path) . ';' . PHP_EOL;
			}
		} elseif (file_exists(DIR_PUBLIC . 'static/js/' . $this->_getFilename())) {
			$content = new CM_File(DIR_PUBLIC . 'static/js/' . $this->_getFilename());
		} else {
			throw new CM_Exception_Invalid('Invalid filename: `' . $this->_getFilename() . '`');
		}
		return $content;
	}

	/**
	 * @param string $name
	 * @param string $parentName
	 * @param string $properties JSON
	 * @return string
	 */
	private function _printClass($name, $parentName, $properties = null) {
		$str = 'var ' . $name . ' = ' . $parentName . '.extend({';
		$str .= '_class:"' . $name . '"';
		//$str .= ',__super__:' . $parentName . '.prototype';
		if (!empty($properties)) {
			$str .= ',' . PHP_EOL . trim($properties) . PHP_EOL;
		}
		$str .= '});' . PHP_EOL;
		return $str;
	}
}
