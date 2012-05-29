<?php
namespace TYPO3\Asset\Service;

/*                                                                        *
 * This script belongs to the FLOW3.Asser framework.                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

use Assetic\Asset\AssetCollection;
use Assetic\Filter\LessphpFilter;
use TYPO3\Asset\Asset\AssetManager;
use Assetic\Asset\AssetReference;

/**
 * A Service which provides further information about a given locale
 * and the current state of the i18n and L10n components.
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class AssetService {
	const CONFIGURATION_TYPE_ASSETS = 'Settings';

	/**
	 *
	 * @var array
	 **/
	protected $requiredJs = array();

	/**
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 * @FLOW3\Inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\FLOW3\Resource\ResourceManager
	 * @FLOW3\Inject
	 */
	protected $resourceManager;

	/**
	 * @var \TYPO3\FLOW3\Resource\Publishing\ResourcePublisher
	 * @FLOW3\Inject
	 */
	protected $resourcePublisher;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 * @author Marc Neuhaus <apocalip@gmail.com>
	 * @FLOW3\Inject
	 */
	protected $objectManager;
	
	public function compileAssets($name, $namespace, $bundle = array()) {
		$bundle = $this->getBundle($name, 'Bundles.' . $namespace, $bundle);

		$filters = array();
		if(isset($bundle["Filters"]))
			$filters = $this->createFiltersIntances($bundle["Filters"]);
		
		$preCompileMerge = isset($bundle["PreCompileMerge"]) ? $bundle["PreCompileMerge"] : false;
		
		if($preCompileMerge) {
			
			$as = new AssetCollection(array(
		    	new \TYPO3\Asset\Asset\MergedAsset($bundle["Files"], $filters),
			));

			$name = str_replace(":", ".", $name);
			return array( $this->publish($as->dump(), $name . "." . strtolower($namespace)) );

		} else {
			$assets = array();
			foreach ($bundle["Files"] as $file) {
				$assets[] = new \Assetic\Asset\FileAsset($file, $filters);
			}
			$as = new AssetCollection($assets);
			
			$uris = array();
			foreach ($as as $leaf) {
				$uris[] = $this->publish($leaf->dump(), $leaf->getSourcePath());
			}
			return $uris;

		}
	}


	public function getAssetConfiguration($path) {
		return $this->configurationManager->getConfiguration(self::CONFIGURATION_TYPE_ASSETS, "Assets." . $path);
	}

	public function getBundle($bundle, $basePath, $overrideSettings = array()) {
		$path = $basePath . "." . $bundle;
		$bundles = $this->configurationManager->getConfiguration(self::CONFIGURATION_TYPE_ASSETS, "Assets." . $basePath);
		$conf = $bundles[$bundle];
		$conf = array_merge($conf, $overrideSettings);
		if(isset($conf["Dependencies"])){
			foreach ($conf["Dependencies"] as $dependency) {
				$conf = array_merge_recursive($this->getBundle($dependency, $basePath), $conf);
			}
		}
		if(isset($conf["Alterations"])){
			foreach ($conf["Alterations"] as $key => $alterations) {
				if(is_array($alterations)){

					foreach ($alterations as $type => $files) {
						$position = array_search($key, $conf["Files"]);
						switch ($type) {
							case 'After':
								array_splice($conf["Files"], $position + 1, 0, $files);
								break;
							
							case 'Before':
								array_splice($conf["Files"], $position, 0, $files);
								break;

							case 'Replace':
							case 'Instead':
								array_splice($conf["Files"], $position, 1, $files);

							default:
								# code...
								break;
						}
					}
				}
			}
		}

		return $conf;
	}

	public function getCssBundleUris($name) {
		var_dump($this->compileAssets($name, "CSS"));
		return $this->compileAssets($name, "CSS");
	}

	public function getJsBundleUris($name) {
		return $this->compileAssets($name, "Js");
	}

	public function createFiltersIntances($filters) {
		$filterInstances = array();
		foreach ($filters as $filter => $conf) {
			$filterInstances[] = $this->createFilterInstance($filter, $conf);
		}
		return $filterInstances;
	}

	public function createFilterInstance($filter, $arguments) {
		switch (count($arguments)) {
			case 0: return $this->objectManager->get($filter );
			case 1: return $this->objectManager->get($filter, $arguments[0]);
			case 2: return $this->objectManager->get($filter, $arguments[0], $arguments[1]);
			case 3: return $this->objectManager->get($filter, $arguments[0], $arguments[1], $arguments[2]);
			case 4: return $this->objectManager->get($filter, $arguments[0], $arguments[1], $arguments[2], $arguments[3]);
			case 5: return $this->objectManager->get($filter, $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
			case 6: return $this->objectManager->get($filter, $arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
		}
	}

	/**
	 * shortcut to publish some content
	 * 
	 * @param  string $content
	 * @param  string $filename
	 * @return string $uri
	 */
	public function publish($content, $filename) {
		$resource = $this->resourceManager->createResourceFromContent($content, $filename);
		return $this->resourcePublisher->publishPersistentResource($resource);
	}

	/**
	 * Add an Bundle to the required bundles
	 * 
	 * @param string $name   name of the Bundle to add
	 * @param string $bundle name of the Bundle to add this Bundle to
	 */
	public function addRequiredJs($name, $bundle = "TYPO3.Asset:Required") {
		if(!isset($this->requiredJs[$bundle]))
			$this->requiredJs[$bundle] = array();

		$this->requiredJs[$bundle][] = $name;
	}

	/**
	 * Compile all the Required Scripts up to this point
	 * @param  string $bundleName name of the Bundle to get the Configuration from
	 * @return array  an array containing the uris
	 */
	public function getRequiredJs($bundleName = "TYPO3.Asset:Required") {
		$bundle = array();

		if(isset($this->requiredJs[$bundleName]))
			$bundle["Dependencies"] = $this->requiredJs[$bundleName];

		return $this->compileAssets($bundleName, "Js", $bundle);
	}
}

?>