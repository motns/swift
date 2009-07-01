<?php

require('Minify.php');

class Swift_Server {

	private $_config;
	private $_version;
	private $_current_modules;

	public function __construct($_config){

		$this->_config = $_config;

	}

	public function setCode($code){
		$bits = split(',',$code);

		$version = array_shift($bits);
		$this->setVersion($version);
	}

	public function setModules($modules){
		$this->_current_modules = $modules;
	}

	public function setVersion($version){
		$this->_version = $version;
	}

	public function serve(){
		// Adapted from index.php in the Minify distribution

		$min_uploaderHoursBehind = isset($this->_config['min_uploaderHoursBehind'])?$this->_config['min_uploaderHoursBehind']:0;
		Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;

		$min_cacheFileLocking = isset($this->_config['min_cacheFileLocking'])?$this->_config['min_cacheFileLocking']:true;
		$min_cachePath = $this->_config['cache_dir'];
		Minify::setCache($min_cachePath, $min_cacheFileLocking);

		$min_symlinks = isset($this->_config['min_cacheFileLocking'])?$this->_config['min_cacheFileLocking']:true;
		$min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;

		/*
		 if ($min_allowDebugFlag && isset($_GET['debug'])) {
			$min_serveOptions['debug'] = true;
			}

			if ($min_errorLogger) {
			require_once 'Minify/Logger.php';
			if (true === $min_errorLogger) {
			require_once 'FirePHP.php';
			Minify_Logger::setLogger(FirePHP::getInstance(true));
			} else {
			Minify_Logger::setLogger($min_errorLogger);
			}
			}
			*/

		// check for URI versioning
		if(!empty($this->_version)){
			$min_serveOptions['maxAge'] = 31536000;
		}
		
		$min_serveOptions['swift']['files'] = array();
		
		foreach($this->_current_modules AS $module){
			if(!empty($this->_config['debug_use_alt_resources']) && !empty($this=>_config['modules'][$module]['debug_path'])){
				$min_serveOptions['swift']['files'][] = $this=>_config['modules'][$module]['debug_path'];
			} else {
				$min_serveOptions['swift']['files'][] = $this->_config['modules'][$module]['path'];
			}
		}
		
		Minify::serve('Swift', $min_serveOptions);


	}
}

require_once 'Minify/Controller/Base.php';

// Adapted from the minify Groups controller but simplified

class Minify_Controller_Swift extends Minify_Controller_Base {

	public function setupSources($options) {
		// strip controller options
		$swift = $options['swift'];
		unset($options['swift']);
		
		$sources = array();
		
		$files = $swift['files'];
		
		foreach ($files as $file) {
			if (0 === strpos($file, '//')) {
				$file = $_SERVER['DOCUMENT_ROOT'] . substr($file, 1);
			}
			$realPath = realpath($file);
			if (is_file($realPath)) {
				$sources[] = new Minify_Source(array(
                    'filepath' => $realPath
				));
			} else {
				$this->log("The path \"{$file}\" could not be found (or was not a file)");
				return $options;
			}
		}
		if ($sources) {
			$this->sources = $sources;
		}
		return $options;
	}
}