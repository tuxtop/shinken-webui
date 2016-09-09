<?php
namespace Dwoo;

use Dwoo\Block\Plugin as BlockPlugin;
use Dwoo\Exception\CoreException;
use Dwoo\Plugins\Blocks\BlockDynamic;
use Dwoo\Template\File;

/**
 * Main dwoo class, allows communication between the compiler, template and data classes
 * <pre>
 * requirements :
 *  php 5.3.0 or above
 *  SPL and PCRE extensions (for php versions prior to 5.3.0)
 *  mbstring extension for some string manipulation plugins (especially if you intend to use UTF-8)
 * recommended :
 *  hash extension (for Template\String - minor performance boost)
 * </pre>
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from the use of this software.
 *
 * @author     David Sanchez <david38sanchez@gmail.com>
 * @copyright  Copyright (c) 2013-2014, David Sanchez
 * @license    http://dwoo.org/LICENSE GNU Lesser General Public License v3.0
 * @link       http://dwoo.org/
 * @version    2.0
 * @date       2014-02-26
 * @package    Dwoo
 */
class Core {

	/**
	 * current version number
	 * @var string
	 */
	const VERSION = '2.0a';

	/**
	 * unique number of this dwoo release
	 * this can be used by templates classes to check whether the compiled template
	 * has been compiled before this release or not, so that old templates are
	 * recompiled automatically when Dwoo is updated
	 * @var int
	 */
	const RELEASE_TAG = 20;

	/**
	 * Dwoo library directory
	 */
	const DWOO_DIRECTORY = __DIR__;

	/**
	 * Plugins classes namespaces
	 * @var string
	 */
	const PLUGIN_BLOCK_CLASS_PREFIX_NAME    = '\Dwoo\Plugins\Blocks\Block';
	const PLUGIN_FUNC_CLASS_PREFIX_NAME     = '\Dwoo\Plugins\Functions\Function';
	const PLUGIN_FUNC_FUNCTION_PREFIX_NAME  = '\Dwoo\Plugins\Functions\function';

	/**
	 * constants that represents all plugin types
	 * these are bitwise-operation-safe values to allow multiple types
	 * on a single plugin
	 * @var int
	 */
	const CLASS_PLUGIN      = 1;
	/** @deprecated stay only for smarty compatibility */
	const FUNC_PLUGIN       = 2;
	const NATIVE_PLUGIN     = 4;
	const BLOCK_PLUGIN      = 8;
	const COMPILABLE_PLUGIN = 16;
	const CUSTOM_PLUGIN     = 32;
	const SMARTY_MODIFIER   = 64;
	const SMARTY_BLOCK      = 128;
	const SMARTY_FUNCTION   = 256;
	const PROXY_PLUGIN      = 512;
	const TEMPLATE_PLUGIN   = 1024;
	/**#@-*/

	/**
	 * character set of the template, used by string manipulation plugins
	 * it must be lowercase, but setCharset() will take care of that
	 * @see setCharset
	 * @see getCharset
	 * @var string
	 */
	protected $charset = 'utf-8';

	/**
	 * global variables that are accessible through $dwoo.* in the templates
	 * default values include:
	 * $dwoo.version - current version number
	 * $dwoo.ad - a Powered by Dwoo link pointing to dwoo.org
	 * $dwoo.now - the current time
	 * $dwoo.template - the current template filename
	 * $dwoo.charset - the character set used by the template
	 * on top of that, foreach and other plugins can store special values in there,
	 * see their documentation for more details.
	 * @private
	 * @var array
	 */
	public $globals = array();

	/**
	 * @var bool
	 */
	public $debugMode = false;

	/**
	 * directory where the compiled templates are stored
	 * defaults to DWOO_COMPILEDIR (= dwoo_dir/compiled by default)
	 * @var string
	 */
	protected $compileDir;

	/**
	 * directory where the template files are stored
	 * @var array
	 */
	protected $templateDir = array();

	/**
	 * directory where the cached templates are stored
	 * defaults to DWOO_CACHEDIR (= dwoo_dir/cache by default)
	 * @var string
	 */
	protected $cacheDir;

	/**
	 * defines how long (in seconds) the cached files must remain valid
	 * can be overriden on a per-template basis
	 * -1 = never delete
	 * 0 = disabled
	 * >0 = duration in seconds
	 * @var int
	 */
	protected $cacheTime = 0;

	/**
	 * security policy object
	 * @var Security\Policy
	 */
	protected $securityPolicy = null;

	/**
	 * stores the custom plugins callbacks
	 * @see addPlugin
	 * @see removePlugin
	 * @var array
	 */
	protected $plugins = array();

	/**
	 * stores the filter callbacks
	 * @see addFilter
	 * @see removeFilter
	 * @var array
	 */
	protected $filters = array();

	/**
	 * stores the resource types and associated
	 * classes / compiler classes
	 * @var array
	 */
	protected $resources = array(
		'file'      => array(
			'class' => '\Dwoo\Template\File', 'compiler' => null
		), 'string' => array(
			'class' => '\Dwoo\Template\String', 'compiler' => null
		)
	);

	/**
	 * the dwoo loader object used to load plugins by this dwoo instance
	 * @var ILoader
	 */
	protected $loader = null;

	/**
	 * currently rendered template, set to null when not-rendering
	 * @var ITemplate
	 */
	protected $template = null;

	/**
	 * stores the instances of the class plugins during template runtime
	 * @var array
	 */
	protected $runtimePlugins;

	/**
	 * stores the returned values during template runtime
	 * @var array
	 */
	protected $returnData;

	/**
	 * plugin data, available from plugin side
	 * @var array
	 */
	protected $pluginData = array();

	/**
	 * stores the data during template runtime
	 * @var array
	 * @protected
	 */
	protected $data;

	/**
	 * stores the current scope during template runtime
	 * this should ideally not be accessed directly from outside template code
	 * @var mixed
	 * @private
	 */
	public $scope;

	/**
	 * stores the scope tree during template runtime
	 * @var array
	 */
	protected $scopeTree;

	/**
	 * stores the block plugins stack during template runtime
	 * @var array
	 */
	protected $stack;

	/**
	 * stores the current block plugin at the top of the stack during template runtime
	 * @var object|null
	 */
	protected $curBlock;

	/**
	 * stores the output buffer during template runtime
	 * @var string
	 */
	protected $buffer;

	/**
	 * stores plugin proxy
	 * @var IPluginProxy
	 */
	protected $pluginProxy;

	/**
	 * constructor, sets the cache and compile dir to the default values if not provided
	 * @param string $compileDir path to the compiled directory, defaults to lib/compiled
	 * @param string $cacheDir   path to the cache directory, defaults to lib/cache
	 * @throws Exception\CoreException
	 */
	public function __construct($compileDir = null, $cacheDir = null) {
		// Check PHP version greater than 5.3.0
		try {
			if ((version_compare(PHP_VERSION, '5.3') >= 0) === false) {
				throw new CoreException('You need PHP 5.3 minimum.<br>Current version: ' . PHP_VERSION);
			}
		}
		catch (CoreException $e) {
			throw $e;
		}

		// Set compile directory
		if ($compileDir !== null) {
			$this->setCompileDir($compileDir);
		}

		// Set cache directory
		if ($cacheDir !== null) {
			$this->setCacheDir($cacheDir);
		}

		// Initialize globals
		$this->initGlobals();
	}

	/**
	 * resets some runtime variables to allow a cloned object to be used to render sub-templates
	 */
	public function __clone() {
		$this->template = null;
		unset($this->data);
		unset($this->returnData);
	}

	/**
	 * outputs the template instead of returning it, this is basically a shortcut for get(*, *, *, true)
	 * @see get
	 * @param mixed     $tpl                                                 template, can either be a ITemplate object (i.e. Template\File), a valid path to a template, or
	 *                                                                       a template as a string it is recommended to provide a ITemplate as it will probably make things faster,
	 *                                                                       especially if you render a template multiple times
	 * @param mixed     $data                                                the data to use, can either be a IDataProvider object (i.e. Data) or an associative array. if you're
	 *                                                                       rendering the template from cache, it can be left null
	 * @param ICompiler $compiler                                            the compiler that must be used to compile the template, if left empty a default
	 *                                                                       Compiler will be used.
	 * @return string nothing or the template output if $output is true
	 */
	public function output($tpl, $data = array(), ICompiler $compiler = null) {
		return $this->get($tpl, $data, $compiler, true);
	}

	/**
	 * returns the given template rendered using the provided data and optional compiler
	 * @param mixed     $_tpl                                                template, can either be a ITemplate object (i.e. Template\File), a valid path to a template, or
	 *                                                                       a template as a string it is recommended to provide a ITemplate as it will probably make things faster,
	 *                                                                       especially if you render a template multiple times
	 * @param mixed     $data                                                the data to use, can either be a IDataProvider object (i.e. Data) or an associative array. if you're
	 *                                                                       rendering the template from cache, it can be left null
	 * @param ICompiler $_compiler                                           the compiler that must be used to compile the template, if left empty a default
	 *                                                                       Compiler will be used.
	 * @param bool      $_output                                             flag that defines whether the function returns the output of the template (false, default) or echoes it directly (true)
	 * @throws Exception
	 * @throws \Exception
	 * @throws Exception\CoreException
	 * @return string|null or the template output if $output is false
	 */
	public function get($_tpl, $data = array(), $_compiler = null, $_output = false) {
		// a render call came from within a template, so we need a new dwoo instance in order to avoid breaking this one
		if ($this->template instanceof ITemplate) {
			$clone = clone $this;

			return $clone->get($_tpl, $data, $_compiler, $_output);
		}

		// auto-create template if required
		if ($_tpl instanceof ITemplate) {
			// valid, skip
		}
		elseif (is_string($_tpl)) {
			$_tpl = new File($_tpl);
			$_tpl->setIncludePath($this->templateDir);
		}
		else {
			throw new CoreException('Dwoo->get/Dwoo->output\'s first argument must be a \Dwoo\ITemplate (i.e. \Dwoo\Template\File) or a valid path to a template file. Got: ' . $_tpl, E_USER_NOTICE);
		}

		// Put debug mode value
		$_tpl->setDebugMode($this->debugMode);

		// save the current template, enters render mode at the same time
		// if another rendering is requested it will be proxied to a new Core instance
		$this->template = $_tpl;

		// load data
		if ($data instanceof IDataProvider) {
			$this->data = $data->getData();
		}
		elseif (is_array($data)) {
			$this->data = $data;
		}
		elseif ($data instanceof \ArrayAccess) {
			$this->data = $data;
		}
		else {
			throw new CoreException('Dwoo->get/Dwoo->output\'s data argument must be a \Dwoo\IDataProvider object (i.e. \Dwoo\Data) or an associative array', E_USER_NOTICE);
		}

		$this->globals['template'] = $_tpl->getName();
		$this->initRuntimeVars($_tpl);

		// try to get cached template
		$file        = $_tpl->getCachedTemplate($this);
		$doCache     = $file === true;
		$cacheLoaded = is_string($file);

		if ($cacheLoaded === true) {
			// cache is present, run it
			if ($_output === true) {
				include $file;
				$this->template = null;
			}
			else {
				ob_start();
				include $file;
				$this->template = null;
				return ob_get_clean();
			}
		}
		else {
			// no cache present
			if ($doCache === true) {
				$dynamicId = uniqid();
			}

			$recompile = false;
			do {
				// render template
				try {
					$compiledTemplate = $_tpl->getCompiledTemplate($this, $_compiler);
					$out = include $compiledTemplate;
				}
				catch (Exception $e) {
					// output
					if ($_output === true) {
						ob_flush();
					}
					else {
						ob_clean();
					}
					throw $e;
				}

				if ($out === false) {
					// template returned false so it needs to be recompiled
					if ($recompile) {
						// this is the second time this happened...
						throw new Exception('Failed to compile template.');
					}
					$_tpl->forceCompilation();
					$recompile = true;
				}
			}
			while($recompile);

			if ($doCache === true) {
				$out = preg_replace('/(<%|%>|<\?php|<\?|\?>)/', '<?php /*'.$dynamicId.'*/ echo \'$1\'; ?>', $out);
				$out = BlockDynamic::unescape($out, $dynamicId, $compiledTemplate);
			}

			// process filters
			foreach ($this->filters as $filter) {
				if (is_array($filter) && $filter[0] instanceof Filter) {
					$out = call_user_func($filter, $out);
				}
				else {
					$out = call_user_func($filter, $this, $out);
				}
			}

			if ($doCache === true) {
				// building cache
				$file = $_tpl->cache($this, $out);

				// run it from the cache to be sure dynamics are rendered
				if ($_output === true) {
					include $file;
					// exit render mode
					$this->template = null;
				}
				else {
					ob_start();
					include $file;
					// exit render mode
					$this->template = null;
					return ob_get_clean();
				}
			}
			else {
				// no need to build cache
				// exit render mode
				$this->template = null;
				// output
				if ($_output === true) {
					echo $out;
				}
				return $out;
			}
		}

        return null;
	}

	/**
	 * Assign a global variable
	 * @param $name
	 * @param $value
	 * @return $this
	 */
	public function setGlobal($name, $value) {
		$this->globals[$name] = $value;

		return $this;
	}

	/**
	 * Assign multiple global variables
	 * @param array $globals
	 * @return $this
	 */
	public function setGlobals(array $globals) {
		foreach ($globals as $key => $value) {
			$this->globals[$key] = $value;
		}

		return $this;
	}

	/**
	 * Set data retrievable inside every plugin
	 * @param array $data
	 * @return $this
	 */
	public function setPluginData(array $data) {
		$this->pluginData = array_merge($this->pluginData, $data);

		return $this;
	}

	/**
	 * Get plugin data
	 * @param $name
	 * @return array
	 */
	public function getPluginData($name) {
		return $this->pluginData[$name];
	}

	/**
	 * re-initializes the globals array before each template run
	 * this method is only callede once when the Dwoo object is created
	 */
	protected function initGlobals() {
		$this->globals = array(
			'version' => self::VERSION,
			'ad'      => '<a href="http://dwoo.org/">Powered by Dwoo</a>',
			'now'     => $this->_now(),
			'charset' => $this->charset,
		);
	}

	/**
	 * re-initializes the runtime variables before each template run
	 * override this method to inject data in the globals array if needed, this
	 * method is called before each template execution
	 *
	 * @param ITemplate $tpl the template that is going to be rendered
	 */
	protected function initRuntimeVars(ITemplate $tpl) {
		$this->runtimePlugins = array();
		$this->scope          =& $this->data;
		$this->scopeTree      = array();
		$this->stack          = array();
		$this->curBlock       = null;
		$this->buffer         = '';
		$this->returnData     = array();
	}

	/*
	 * --------- settings functions ---------
	 */

	/**
	 * adds a custom plugin that is not in one of the plugin directories
	 * @param string   $name       the plugin name to be used in the templates
	 * @param string|array $callback   the plugin callback, either a function name,
	 *                             a class name or an array containing an object
	 *                             or class name and a method name
	 * @param bool     $compilable if set to true, the plugin is assumed to be compilable
	 * @throws Exception\CoreException
	 */
	public function addPlugin($name, $callback, $compilable = false) {
		$compilable = $compilable ? self::COMPILABLE_PLUGIN : 0;
		if (is_array($callback)) {
			if (is_subclass_of(is_object($callback[0]) ? get_class($callback[0]) : $callback[0], '\Dwoo\Block\Plugin')) {
				$this->plugins[$name] = array('type' => self::BLOCK_PLUGIN | $compilable, 'callback' => $callback, 'class' => (is_object($callback[0]) ? get_class($callback[0]) : $callback[0]));
			}
			else {
				$this->plugins[$name] = array('type' => self::CLASS_PLUGIN | $compilable, 'callback' => $callback, 'class' => (is_object($callback[0]) ? get_class($callback[0]) : $callback[0]), 'function' => $callback[1]);
			}
		}
		else if (class_exists($callback)) {
			if (is_subclass_of($callback, '\Dwoo\Block\Plugin')) {
				$this->plugins[$name] = array('type' => self::BLOCK_PLUGIN | $compilable, 'callback' => $callback, 'class' => $callback);
			}
			else {
				$this->plugins[$name] = array('type' => self::CLASS_PLUGIN | $compilable, 'callback' => $callback, 'class' => $callback, 'function' => ($compilable ? 'compile' : 'process'));
			}
		}
		else {
			throw new CoreException('Callback could not be processed correctly, please check that the function/class you used exists');
		}
	}

	/**
	 * removes a custom plugin
	 *
	 * @param string $name the plugin name
	 */
	public function removePlugin($name) {
		if (isset($this->plugins[$name])) {
			unset($this->plugins[$name]);
		}
	}

	/**
	 * adds a filter to this Dwoo instance, it will be used to filter the output of all the templates rendered by this instance
	 * @param mixed $callback a callback or a filter name if it is autoloaded from a plugin directory
	 * @param bool  $autoload if true, the first parameter must be a filter name from one of the plugin directories
	 * @throws Exception\CoreException
	 */
	public function addFilter($callback, $autoload = false) {
		if ($autoload) {
			$class = 'Filter' . $callback;

			if (! class_exists($class) && ! function_exists($class)) {
				try {
					$this->getLoader()->loadPlugin($callback);
				}
				catch (CoreException $e) {
					if (strstr($callback, 'Filter')) {
						throw new CoreException('Wrong filter name : ' . $callback . ', the "Filter" prefix should not be used, please only use "' . str_replace('Filter', '', $callback) . '"');
					}
					throw new CoreException('Wrong filter name : ' . $callback . ', when using autoload the filter must be in one of your plugin dir as "name.php" containg a class or function named "Filter$name"');
				}
			}

			if (class_exists($class)) {
				$callback = array(new $class($this), 'process');
			}
			elseif (function_exists($class)) {
				$callback = $class;
			}
			else {
				throw new CoreException('Wrong filter name : ' . $callback . ', when using autoload the filter must be in one of your plugin dir as "name.php" containg a class or function named "Filter$name"');
			}

			$this->filters[] = $callback;
		}
		else {
			$this->filters[] = $callback;
		}
	}

	/**
	 * removes a filter
	 * @param mixed $callback callback or filter name if it was autoloaded
	 */
	public function removeFilter($callback) {
		if (($index = array_search('Filter' . $callback, $this->filters, true)) !== false) {
			unset($this->filters[$index]);
		}
		elseif (($index = array_search($callback, $this->filters, true)) !== false) {
			unset($this->filters[$index]);
		}
		else {
			$class = 'Filter' . $callback;
			foreach ($this->filters as $index => $filter) {
				if (is_array($filter) && $filter[0] instanceof $class) {
					unset($this->filters[$index]);
					break;
				}
			}
		}
	}

	/**
	 * adds a resource or overrides a default one
	 * @param string   $name            the resource name
	 * @param string   $class           the resource class (which must implement ITemplate)
	 * @param callback $compilerFactory the compiler factory callback, a function that must return a compiler instance used to compile this resource, if none is provided. by default it will produce a Compiler object
	 * @throws Exception\CoreException
	 */
	public function addResource($name, $class, $compilerFactory = null) {
		if (strlen($name) < 2) {
			throw new CoreException('Resource names must be at least two-character long to avoid conflicts with Windows paths');
		}

		if (! class_exists($class)) {
			throw new CoreException('Resource class does not exist');
		}

		$interfaces = class_implements($class);
		if (in_array('Dwoo\ITemplate', $interfaces) === false) {
			throw new CoreException('Resource class must implement ITemplate');
		}

		$this->resources[$name] = array('class' => $class, 'compiler' => $compilerFactory);
	}

	/**
	 * removes a custom resource
	 * @param string $name the resource name
	 */
	public function removeResource($name) {
		unset($this->resources[$name]);
		if ($name === 'file') {
			$this->resources['file'] = array('class' => 'Template\File', 'compiler' => null);
		}
	}

	/*
	 * --------- getters and setters ---------
	 */

	/**
	 * sets the loader object to use to load plugins
	 * @param ILoader $loader loader object
	 */
	public function setLoader(ILoader $loader) {
		$this->loader = $loader;
	}

	/**
	 * returns the current loader object or a default one if none is currently found
	 * @return ILoader
	 */
	public function getLoader() {
		if ($this->loader === null) {
			$this->loader = new Loader($this);
		}

		return $this->loader;
	}

	/**
	 * returns the custom plugins loaded
	 * used by the ITemplate classes to pass the custom plugins to their ICompiler instance
	 *
	 * @return array
	 */
	public function getCustomPlugins() {
		return $this->plugins;
	}

	/**
	 * Returns runtime plugins
	 * @return array
	 */
	public function getRuntimePlugins() {
		return $this->runtimePlugins;
	}

	/**
	 * returns the cache directory with a trailing DIRECTORY_SEPARATOR
	 * @return string
	 */
	public function getCacheDir() {
		return $this->cacheDir;
	}

	/**
	 * sets the cache directory and automatically appends a DIRECTORY_SEPARATOR
	 * @param string $dir the cache directory
	 * @throws Exception\CoreException
	 */
	public function setCacheDir($dir) {
		try {
			$this->cacheDir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
			if (is_writable($this->cacheDir) === false) {
				throw new CoreException('The cache directory must be writable, chmod "' . $this->cacheDir . '" to make it writable');
			}
		}
		catch(CoreException $e) {
			throw $e;
		}
	}

	/**
	 * returns the compile directory with a trailing DIRECTORY_SEPARATOR
	 * @return string
	 */
	public function getCompileDir() {
		return $this->compileDir;
	}

	/**
	 * returns an array of the template directory with a trailing DIRECTORY_SEPARATOR
	 * @return array
	 */
	public function getTemplateDir() {
		return $this->templateDir;
	}

	/**
	 * sets the template directory and automatically appends a DIRECTORY_SEPARATOR
     * template directory is stored in an array
	 * @param string $dir
	 * @throws Exception\CoreException
	 */
	public function setTemplateDir($dir) {
		try {
			$tmpDir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
			if (is_dir($tmpDir) === false) {
				throw new CoreException('The template directory: "' . $tmpDir . '" does not exists, create the directory or specify an other location !');
			}
			$this->templateDir[] = $tmpDir;
		}
		catch(CoreException $e) {
			throw $e;
		}
	}

	/**
	 * sets the compile directory and automatically appends a DIRECTORY_SEPARATOR
	 * @param string $dir the compile directory
	 * @throws Exception\CoreException
	 */
	public function setCompileDir($dir) {
		try {
			$this->compileDir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
			if (is_writable($this->compileDir) === false) {
				throw new CoreException('The compile directory must be writable, chmod "' . $this->compileDir . '" to make it writable!');
			}
		}
		catch (CoreException $e) {
			throw $e;
		}
	}

	/**
	 * returns the default cache time that is used with templates that do not have a cache time set
	 * @return int the duration in seconds
	 */
	public function getCacheTime() {
		return $this->cacheTime;
	}

	/**
	 * sets the default cache time to use with templates that do not have a cache time set
	 * @param int $seconds the duration in seconds
	 */
	public function setCacheTime($seconds) {
		$this->cacheTime = (int)$seconds;
	}

	/**
	 * returns the character set used by the string manipulation plugins
	 * the charset is automatically lowercased
	 * @return string
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * sets the character set used by the string manipulation plugins
	 * the charset will be automatically lowercased
	 * @param string $charset the character set
	 */
	public function setCharset($charset) {
		$this->charset = strtolower((string)$charset);
	}

	/**
	 * returns the current template being rendered, when applicable, or null
	 * @return ITemplate|null
	 */
	public function getTemplate() {
		return $this->template;
	}

	/**
	 * sets the current template being rendered
	 * @param ITemplate $tpl template object
	 */
	public function setTemplate(ITemplate $tpl) {
		$this->template = $tpl;
	}

	/**
	 * sets the default compiler factory function for the given resource name
	 * a compiler factory must return a ICompiler object pre-configured to fit your needs
	 * @param string   $resourceName    the resource name (i.e. file, string)
	 * @param callback $compilerFactory the compiler factory callback
	 */
	public function setDefaultCompilerFactory($resourceName, $compilerFactory) {
		$this->resources[$resourceName]['compiler'] = $compilerFactory;
	}

	/**
	 * returns the default compiler factory function for the given resource name
	 * @param string $resourceName the resource name
	 * @return callback the compiler factory callback
	 */
	public function getDefaultCompilerFactory($resourceName) {
		return $this->resources[$resourceName]['compiler'];
	}

	/**
	 * sets the security policy object to enforce some php security settings
	 * use this if untrusted persons can modify templates
	 * @param Security\Policy $policy the security policy object
	 */
	public function setSecurityPolicy(Security\Policy $policy = null) {
		$this->securityPolicy = $policy;
	}

	/**
	 * returns the current security policy object or null by default
	 * @return Security\Policy|null the security policy object if any
	 */
	public function getSecurityPolicy() {
		return $this->securityPolicy;
	}

	/**
	 * sets the object that must be used as a plugin proxy when plugin can't be found
	 * by dwoo's loader
	 * @param IPluginProxy $pluginProxy the proxy object
	 */
	public function setPluginProxy(IPluginProxy $pluginProxy) {
		$this->pluginProxy = $pluginProxy;
	}

	/**
	 * returns the current plugin proxy object or null by default
	 * @return IPluginProxy|null the proxy object if any
	 */
	public function getPluginProxy() {
		return $this->pluginProxy;
	}

	/*
	 * --------- util functions ---------
	 */

	/**
	 * [util function] checks whether the given template is cached or not
	 * @param ITemplate $tpl the template object
	 * @return bool
	 */
	public function isCached(ITemplate $tpl) {
		return is_string($tpl->getCachedTemplate($this));
	}

	/**
	 * [util function] clears the cached templates if they are older than the given time
	 * @param int $olderThan minimum time (in seconds) required for a cached template to be cleared
	 * @return int the amount of templates cleared
	 */
	public function clearCache($olderThan = -1) {
		$cacheDirs = new  \RecursiveDirectoryIterator($this->getCacheDir());
		$cache     = new \RecursiveIteratorIterator($cacheDirs);
		$expired   = time() - $olderThan;
		$count     = 0;
		foreach ($cache as $file) {
			if ($cache->isDot() || $cache->isDir() || substr($file, - 5) !== '.html') {
				continue;
			}
			if ($cache->getCTime() < $expired) {
				$count += unlink((string)$file) ? 1 : 0;
			}
		}

		return $count;
	}

	/**
	 * [util function] fetches a template object of the given resource
	 * @param string    $resourceName the resource name (i.e. file, string)
	 * @param string    $resourceId   the resource identifier (i.e. file path)
	 * @param int       $cacheTime    the cache time setting for this resource
	 * @param string    $cacheId      the unique cache identifier
	 * @param string    $compileId    the unique compiler identifier
	 * @param ITemplate $parentTemplate
	 * @throws \Exception
	 * @throws \ReflectionException
	 * @throws Exception\CoreException
	 * @return ITemplate
	 */
	public function templateFactory($resourceName, $resourceId, $cacheTime = null, $cacheId = null, $compileId = null, ITemplate $parentTemplate = null) {
		try {
			if (isset($this->resources[$resourceName])) {
				$reflectionClass = new \ReflectionClass($this->resources[$resourceName]['class']);
				return $reflectionClass->getMethod('templateFactory')->invokeArgs($reflectionClass, array($this, $resourceId, $cacheTime, $cacheId, $compileId, $parentTemplate));
			}
			else {
				throw new CoreException('Unknown resource type : ' . $resourceName);
			}
		}
		catch (\ReflectionException $e) {
			throw $e;
		}
		catch (CoreException $e) {
			throw $e;
		}
	}

	/**
	 * [util function] checks if the input is an array or arrayaccess object, optionally it can also check if it's empty
	 * @param mixed $value          the variable to check
	 * @param bool  $checkIsEmpty   if true, the function will also check if the array|arrayaccess is empty,
	 *                              and return true only if it's not empty
	 * @return int|bool true if it's an array|arrayaccess (or the item count if $checkIsEmpty is true) or false if it's not an array|arrayaccess (or 0 if $checkIsEmpty is true)
	 */
	public function isArray($value, $checkIsEmpty = false) {
		if (is_array($value) === true || $value instanceof \ArrayAccess) {
			if ($checkIsEmpty === false) {
				return true;
			}
			else {
				return $this->count($value);
			}
		}

        return false;
	}

	/**
	 * [util function] checks if the input is an array or a traversable object, optionally it can also check if it's empty
	 * @param mixed $value          the variable to check
	 * @param bool  $checkIsEmpty   if true, the function will also check if the array|traversable is empty,
	 *                              and return true only if it's not empty
	 * @return int|bool true if it's an array|traversable (or the item count if $checkIsEmpty is true) or false if it's not an array|traversable (or 0 if $checkIsEmpty is true)
	 */
	public function isTraversable($value, $checkIsEmpty = false) {
		if (is_array($value) === true) {
			if ($checkIsEmpty === false) {
				return true;
			}
			else {
				return count($value) > 0;
			}
		}
		else if ($value instanceof \Traversable) {
			if ($checkIsEmpty === false) {
				return true;
			}
			else {
				return $this->count($value);
			}
		}

		return false;
	}

	/**
	 * [util function] counts an array or arrayaccess/traversable object
	 * @param mixed $value
	 * @return int|bool the count for arrays and objects that implement countable, true for other objects that don't, and 0 for empty elements
	 */
	public function count($value) {
		if (is_array($value) === true || $value instanceof \Countable) {
			return count($value);
		}
		else if ($value instanceof \ArrayAccess) {
			if ($value->offsetExists(0)) {
				return true;
			}
		}
		else if ($value instanceof \Iterator) {
			$value->rewind();
			if ($value->valid()) {
				return true;
			}
		}
		else if ($value instanceof \Traversable) {
			foreach ($value as $dummy) {
				return true;
			}
		}

		return 0;
	}

	/**
	 * [util function] triggers a dwoo error
	 * @param string $message the error message
	 * @param int    $level   the error level, one of the PHP's E_* constants
	 */
	public function triggerError($message, $level = E_USER_NOTICE) {
		if (! ($tplIdentifier = $this->template->getResourceIdentifier())) {
			$tplIdentifier = $this->template->getResourceName();
		}
		trigger_error('Dwoo error (in ' . $tplIdentifier . ') : ' . $message, $level);
	}

	/*
	 * --------- runtime functions ---------
	 */

	/**
	 * [runtime function] adds a block to the block stack
	 * @param string $blockName the block name (without prefix)
	 * @param array  $args      the arguments to be passed to the block's init() function
	 * @throws \Exception
	 * @throws \ReflectionException
	 * @return Plugin the newly created block
	 */
	public function addStack($blockName, array $args = array()) {
		if (isset($this->plugins[$blockName])) {
			$class = $this->plugins[$blockName]['class'];
		}
		else {
			foreach (array(Core::PLUGIN_BLOCK_CLASS_PREFIX_NAME, Core::PLUGIN_FUNC_CLASS_PREFIX_NAME) as $value) {
				try {
					$reflectionClass = new \ReflectionClass($value . ucfirst($blockName));
					$class           = '\\' . $reflectionClass->getName();
				}
				catch (\ReflectionException $e) {
					throw $e;
				}
			}
		}

		if ($this->curBlock !== null) {
			$this->curBlock->buffer(ob_get_contents());
			ob_clean();
		}
		else {
			$this->buffer .= ob_get_contents();
			ob_clean();
		}

		try {
			$reflectionClass = new \ReflectionClass($class);
			$block           = $reflectionClass->newInstance($this);
			$reflectionClass->getMethod('begin')->invokeArgs($block, $args);
		}
		catch (\ReflectionException $e) {
			throw $e;
		}

		$this->stack[] = $this->curBlock = $block;

		return $block;
	}

	/**
	 * [runtime function] removes the plugin at the top of the block stack
	 * calls the block buffer() function, followed by a call to end()
	 * and finally a call to process()
	 */
	public function delStack() {
		$args = func_get_args();

		$this->curBlock->buffer(ob_get_contents());
		ob_clean();

		$reflectionMethod = new \ReflectionMethod($this->curBlock, 'end');
		$reflectionMethod->invokeArgs($this->curBlock, $args);

		$tmp = array_pop($this->stack);

		if (count($this->stack) > 0) {
			$this->curBlock = end($this->stack);
			$this->curBlock->buffer($tmp->process());
		}
		else {
			if ($this->buffer !== '') {
				echo $this->buffer;
				$this->buffer = '';
			}
			$this->curBlock = null;
			echo $tmp->process();
		}

		unset($tmp);
	}

	/**
	 * [runtime function] returns the parent block of the given block
	 * @param Block\Plugin $block
	 * @return Block\Plugin or false if the given block isn't in the stack
	 */
	public function getParentBlock(BlockPlugin $block) {
		$index = array_search($block, $this->stack, true);
		if ($index !== false && $index > 0) {
			return $this->stack[$index - 1];
		}

		return false;
	}

	/**
	 * [runtime function] finds the closest block of the given type, starting at the top of the stack
	 * @param string $type the type of plugin you want to find
	 * @return Block\Plugin or false if no plugin of such type is in the stack
	 */
	public function findBlock($type) {
		if (isset($this->plugins[$type])) {
			$type = $this->plugins[$type]['class'];
		}
		else {
			$type = self::PLUGIN_BLOCK_CLASS_PREFIX_NAME . str_replace(self::PLUGIN_BLOCK_CLASS_PREFIX_NAME, '', $type);
		}

		$keys = array_keys($this->stack);
		while (($key = array_pop($keys)) !== false) {
			if ($this->stack[$key] instanceof $type) {
				return $this->stack[$key];
			}
		}

		return false;
	}

	/**
	 * [runtime function] returns a Plugin of the given class
	 * this is so a single instance of every class plugin is created at each template run,
	 * allowing class plugins to have "per-template-run" static variables
	 * @param \ReflectionClass $reflectionClass the class name
	 * @return mixed an object of the given class
	 */
	public function getObjectPlugin(\ReflectionClass $reflectionClass) {
		if (isset($this->runtimePlugins[$reflectionClass->getName()])) {
			return $this->runtimePlugins[$reflectionClass->getName()];
		}

		return $this->runtimePlugins[$reflectionClass->getName()] = $reflectionClass->newInstance($this);
	}

	/**
	 * [runtime function] calls the process() method of the given class-plugin name
	 * @param string $plugName the class plugin name (without plugin prefix)
	 * @param array  $params   an array of parameters to send to the process() method
	 * @throws \ReflectionException
	 * @return string the process() return value
	 */
	public function classCall($plugName, array $params = array()) {
		$prefix   = '';
		$plugName = ucfirst($plugName);

		// Check if its a block
		if (file_exists(self::DWOO_DIRECTORY . DIRECTORY_SEPARATOR . 'Plugins' . DIRECTORY_SEPARATOR . 'Blocks' . DIRECTORY_SEPARATOR . 'Block' . $plugName . '.php') === true) {
			$prefix = self::PLUGIN_BLOCK_CLASS_PREFIX_NAME;
		}
		// Check if its a function
		else if (file_exists(self::DWOO_DIRECTORY . DIRECTORY_SEPARATOR . 'Plugins' . DIRECTORY_SEPARATOR . 'Functions' . DIRECTORY_SEPARATOR . 'Function' . $plugName . '.php') === true) {
			$prefix = self::PLUGIN_FUNC_CLASS_PREFIX_NAME;
		}

		try {
			$reflectionClass = new \ReflectionClass($prefix . $plugName);
			return $reflectionClass->getMethod('process')->invokeArgs($this->getObjectPlugin($reflectionClass), $params);
		}
		catch (\ReflectionException $e) {
			throw $e;
		}
	}

	/**
	 * [runtime function] calls a php function
	 * @param string $callback the function to call
	 * @param array  $params   an array of parameters to send to the function
	 * @return mixed the return value of the called function
	 */
	public function arrayMap($callback, array $params) {
		if ($params[0] === $this) {
			$addThis = true;
			array_shift($params);
		}
		if ((is_array($params[0]) || ($params[0] instanceof \Iterator && $params[0] instanceof \ArrayAccess))) {
			if (empty($params[0])) {
				return $params[0];
			}

			// array map
			$out = array();
			$cnt = count($params);

			if (isset($addThis)) {
				array_unshift($params, $this);
				$items = $params[1];
				$keys  = array_keys($items);

				if (is_string($callback) === false) {
					while (($i = array_shift($keys)) !== null) {
						$out[] = call_user_func_array($callback, array(1 => $items[$i]) + $params);
					}
				}
				elseif ($cnt === 1) {
					while (($i = array_shift($keys)) !== null) {
						$out[] = $callback($this, $items[$i]);
					}
				}
				elseif ($cnt === 2) {
					while (($i = array_shift($keys)) !== null) {
						$out[] = $callback($this, $items[$i], $params[2]);
					}
				}
				elseif ($cnt === 3) {
					while (($i = array_shift($keys)) !== null) {
						$out[] = $callback($this, $items[$i], $params[2], $params[3]);
					}
				}
				else {
					while (($i = array_shift($keys)) !== null) {
						$out[] = call_user_func_array($callback, array(1 => $items[$i]) + $params);
					}
				}
			}
			else {
				$items = $params[0];
				$keys  = array_keys($items);

				if (is_string($callback) === false) {
					while (($i = array_shift($keys)) !== null) {
						$out[] = call_user_func_array($callback, array($items[$i]) + $params);
					}
				}
				elseif ($cnt === 1) {
					while (($i = array_shift($keys)) !== null) {
						$out[] = $callback($items[$i]);
					}
				}
				elseif ($cnt === 2) {
					while (($i = array_shift($keys)) !== null) {
						$out[] = $callback($items[$i], $params[1]);
					}
				}
				elseif ($cnt === 3) {
					while (($i = array_shift($keys)) !== null) {
						$out[] = $callback($items[$i], $params[1], $params[2]);
					}
				}
				elseif ($cnt === 4) {
					while (($i = array_shift($keys)) !== null) {
						$out[] = $callback($items[$i], $params[1], $params[2], $params[3]);
					}
				}
				else {
					while (($i = array_shift($keys)) !== null) {
						$out[] = call_user_func_array($callback, array($items[$i]) + $params);
					}
				}
			}

			return $out;
		}
		else {
			return $params[0];
		}
	}

	/**
	 * [runtime function] reads a variable into the given data array
	 * @param string $varstr   the variable string, using dwoo variable syntax (i.e. "var.subvar[subsubvar]->property")
	 * @param mixed  $data     the data array or object to read from
	 * @return mixed
	 */
	public function readVarInto($varstr, $data) {
		if ($data === null) {
			return null;
		}

		if (is_array($varstr) === false) {
			preg_match_all('#(\[|->|\.)?((?:[^.[\]-]|-(?!>))+)\]?#i', $varstr, $m);
		}
		else {
			$m = $varstr;
		}
		unset($varstr);

		while (list($k, $sep) = each($m[1])) {
			// Is an array
			if ($sep === '.' || $sep === '[' || $sep === '') {
				// strip enclosing quotes if present
				$m[2][$k] = preg_replace('#^(["\']?)(.*?)\1$#', '$2', $m[2][$k]);

				if ((is_array($data) || $data instanceof \ArrayAccess) && isset($data[$m[2][$k]])) {
					$data = $data[$m[2][$k]];
				}
				else {
					return null;
				}
			}
			else {
				// Is a object
				if (is_object($data)) {
					// Check property exists
					if (property_exists($data, $m[2][$k])) {
						$data = $data->$m[2][$k];
					}
					else {
						$data = '';
					}
				}
				else {
					return null;
				}
			}
		}

		return $data;
	}

	/**
	 * [runtime function] reads a variable into the parent scope
	 * @param int    $parentLevels the amount of parent levels to go from the current scope
	 * @param string $varstr       the variable string, using dwoo variable syntax (i.e. "var.subvar[subsubvar]->property")
	 * @return mixed
	 */
	public function readParentVar($parentLevels, $varstr = null) {
		$tree = $this->scopeTree;
		$cur  = $this->data;

		while ($parentLevels -- !== 0) {
			array_pop($tree);
		}

		while (($i = array_shift($tree)) !== null) {
			if (is_object($cur)) {
				$cur = $cur->$i;
			}
			else {
				$cur = $cur[$i];
			}
		}

		if ($varstr !== null) {
			return $this->readVarInto($varstr, $cur);
		}
		else {
			return $cur;
		}
	}

	/**
	 * [runtime function] reads a variable into the current scope
	 * @param string $varstr the variable string, using dwoo variable syntax (i.e. "var.subvar[subsubvar]->property")
	 * @return mixed
	 */
	public function readVar($varstr) {
		if (is_array($varstr) === true) {
			$m = $varstr;
			unset($varstr);
		}
		else {
			if (strstr($varstr, '.') === false && strstr($varstr, '[') === false && strstr($varstr, '->') === false) {
				if ($varstr === 'dwoo') {
					return $this->globals;
				}
				elseif ($varstr === '__' || $varstr === '_root') {
					return $this->data;
				}
				elseif ($varstr === '_' || $varstr === '_parent') {
					$varstr = '.' . $varstr;
					$tree   = $this->scopeTree;
					$cur    = $this->data;
					array_pop($tree);

					while (($i = array_shift($tree)) !== null) {
						if (is_object($cur)) {
							$cur = $cur->$i;
						}
						else {
							$cur = $cur[$i];
						}
					}

					return $cur;
				}

				$cur = $this->scope;

				if (isset($cur[$varstr])) {
					return $cur[$varstr];
				}
				else {
					return null;
				}
			}

			if (substr($varstr, 0, 1) === '.') {
				$varstr = 'dwoo' . $varstr;
			}

			preg_match_all('#(\[|->|\.)?((?:[^.[\]-]|-(?!>))+)\]?#i', $varstr, $m);
		}

		$i = $m[2][0];
		if ($i === 'dwoo') {
			$cur = $this->globals;
			array_shift($m[2]);
			array_shift($m[1]);
			switch ($m[2][0]) {

				case 'get':
					$cur = $_GET;
					break;
				case 'post':
					$cur = $_POST;
					break;
				case 'session':
					$cur = $_SESSION;
					break;
				case 'cookies':
				case 'cookie':
					$cur = $_COOKIE;
					break;
				case 'server':
					$cur = $_SERVER;
					break;
				case 'env':
					$cur = $_ENV;
					break;
				case 'request':
					$cur = $_REQUEST;
					break;
				case 'const':
					array_shift($m[2]);
					if (defined($m[2][0])) {
						return constant($m[2][0]);
					}
					else {
						return null;
					}

			}
			if ($cur !== $this->globals) {
				array_shift($m[2]);
				array_shift($m[1]);
			}
		}
		elseif ($i === '__' || $i === '_root') {
			$cur = $this->data;
			array_shift($m[2]);
			array_shift($m[1]);
		}
		elseif ($i === '_' || $i === '_parent') {
			$tree = $this->scopeTree;
			$cur  = $this->data;

			while (true) {
				array_pop($tree);
				array_shift($m[2]);
				array_shift($m[1]);
				if (current($m[2]) === '_' || current($m[2]) === '_parent') {
					continue;
				}

				while (($i = array_shift($tree)) !== null) {
					if (is_object($cur)) {
						$cur = $cur->$i;
					}
					else {
						$cur = $cur[$i];
					}
				}
				break;
			}
		}
		else {
			$cur = $this->scope;
		}

		while (list($k, $sep) = each($m[1])) {
			if ($sep === '.' || $sep === '[' || $sep === '') {
				if ((is_array($cur) || $cur instanceof \ArrayAccess) && isset($cur[$m[2][$k]])) {
					$cur = $cur[$m[2][$k]];
				}
				else {
					return null;
				}
			}
			elseif ($sep === '->') {
				if (is_object($cur)) {
					$cur = $cur->$m[2][$k];
				}
				else {
					return null;
				}
			}
			else {
				return null;
			}
		}

		return $cur;
	}

	/**
	 * [runtime function] assign the value to the given variable
	 * @param mixed  $value the value to assign
	 * @param string $scope the variable string, using dwoo variable syntax (i.e. "var.subvar[subsubvar]->property")
	 * @throws Exception\CoreException
	 * @return bool true if assigned correctly or false if a problem occured while parsing the var string
	 */
	public function assignInScope($value, $scope) {
		if (! is_string($scope)) {
			throw new CoreException('Assignments must be done into strings, (' . gettype($scope) . ') ' . var_export($scope, true) . ' given', E_USER_ERROR);
		}
		if (strstr($scope, '.') === false && strstr($scope, '->') === false) {
			$this->scope[$scope] = $value;
		}
		else {
			// TODO handle _root/_parent scopes ?
			preg_match_all('#(\[|->|\.)?([^.[\]-]+)\]?#i', $scope, $m);

			$cur  =& $this->scope;
			$last = array(array_pop($m[1]), array_pop($m[2]));

			while (list($k, $sep) = each($m[1])) {
				if ($sep === '.' || $sep === '[' || $sep === '') {
					if (is_array($cur) === false) {
						$cur = array();
					}
					$cur =& $cur[$m[2][$k]];
				}
				elseif ($sep === '->') {
					if (is_object($cur) === false) {
						$cur = new \stdClass;
					}
					$cur =& $cur->$m[2][$k];
				}
				else {
					return false;
				}
			}

			if ($last[0] === '.' || $last[0] === '[' || $last[0] === '') {
				if (is_array($cur) === false) {
					$cur = array();
				}
				$cur[$last[1]] = $value;
			}
			elseif ($last[0] === '->') {
				if (is_object($cur) === false) {
					$cur = new \stdClass;
				}
				$cur->$last[1] = $value;
			}
			else {
				return false;
			}
		}

        return false;
	}

	/**
	 * [runtime function] sets the scope to the given scope string or array
	 * @param mixed $scope    a string i.e. "level1.level2" or an array i.e. array("level1", "level2")
	 * @param bool  $absolute if true, the scope is set from the top level scope and not from the current scope
	 * @return array the current scope tree
	 */
	public function setScope($scope, $absolute = false) {
		$old = $this->scopeTree;

		if (is_string($scope) === true) {
			$scope = explode('.', $scope);
		}

		if ($absolute === true) {
			$this->scope     =& $this->data;
			$this->scopeTree = array();
		}

		while (($bit = array_shift($scope)) !== null) {
			if ($bit === '_' || $bit === '_parent') {
				array_pop($this->scopeTree);
				$this->scope =& $this->data;
				$cnt         = count($this->scopeTree);
				for ($i = 0; $i < $cnt; $i ++) {
					$this->scope =& $this->scope[$this->scopeTree[$i]];
				}
			}
			elseif ($bit === '__' || $bit === '_root') {
				$this->scope     =& $this->data;
				$this->scopeTree = array();
			}
			elseif (isset($this->scope[$bit])) {
				if ($this->scope instanceof \ArrayAccess) {
					$tmp         = $this->scope[$bit];
					$this->scope =& $tmp;
				}
				else {
					$this->scope =& $this->scope[$bit];
				}
				$this->scopeTree[] = $bit;
			}
			else {
				unset($this->scope);
				$this->scope = null;
			}
		}

		return $old;
	}

	/**
	 * [runtime function] returns the entire data array
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * [runtime function] sets a return value for the currently running template
	 * @param string $name  var name
	 * @param mixed  $value var value
	 */
	public function setReturnValue($name, $value) {
		$this->returnData[$name] = $value;
	}

	/**
	 * [runtime function] retrieves the return values set by the template
	 * @return array
	 */
	public function getReturnValues() {
		return $this->returnData;
	}

	/**
	 * [runtime function] returns a reference to the current scope
	 * @return mixed
	 */
	public function &getScope() {
		return $this->scope;
	}

	/**
	 * Redirects all calls to unexisting to plugin proxy.
	 * @param string method name
	 * @param array  list of arguments
	 * @throws Exception\CoreException
	 * @return mixed
	 */
	public function __call($method, $args) {
		$proxy = $this->getPluginProxy();
		if (!$proxy) {
			throw new CoreException('Call to undefined method ' . __CLASS__ . '::' . $method . '()');
		}

		return call_user_func_array($proxy->getCallback($method), $args);
	}

	/**
	 * Get time now
	 * @return int|float
	 */
	private function _now() {
		if (version_compare(PHP_VERSION, '5.4', '<')) {
			return $_SERVER['REQUEST_TIME'];
		}
		else {
			return $_SERVER['REQUEST_TIME_FLOAT'];
		}
	}

	/**
	 * Magic method __get
	 * @param $name
	 * @return mixed
	 */
	public function __get($name) {
		return $this->$name;
	}
}
