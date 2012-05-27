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

	public function getAssetConfiguration($path) {
		return $this->configurationManager->getConfiguration(self::CONFIGURATION_TYPE_ASSETS, "Assets." . $path);
	}

	public function getAssetFiles($bundle, $basePath) {
		$path = $basePath . "." . $bundle;
		$bundles = $this->configurationManager->getConfiguration(self::CONFIGURATION_TYPE_ASSETS, "Assets." . $basePath);
		$conf = $bundles[$bundle];
		if(isset($conf["Dependencies"])){
			foreach ($conf["Dependencies"] as $dependency) {
				$conf = array_merge($this->getAssetFiles($dependency, $basePath), $conf);
			}
		}
		if(isset($conf["Files"])){
			#$this->applyAlterations($bundles[$name])
		}
		return $conf;
	}

	public function applyAlterations($configuration) {
		foreach ($configuration as $key => $alterations) {
			if(is_array($alterations)){
				unset($configuration[$key]);
				
				$position = array_search($key, $configuration);

				foreach ($alterations as $type => $files) {
					switch ($type) {
						case 'After':
							array_splice($configuration, $position + 1, 0, $files);
							break;
						
						case 'Before':
							array_splice($configuration, $position, 0, $files);
							break;

						case 'Replace':
						case 'Instead':
							array_splice($configuration, $position, 1, $files);

						default:
							# code...
							break;
					}
				}
			}
		}
		return $configuration;
	}

	public function getCssBundleUri($name) {
		$files = $this->getAssetFiles($name, 'Bundles.CSS');

		$css = new AssetCollection(array(
		    new \TYPO3\Asset\Asset\ConfigurationAsset($files["Files"], true, array(new LessphpFilter())),
		));
		$name = str_replace(":", ".", $name);
		$resource = $this->resourceManager->createResourceFromContent($css->dump(), $name . ".css");
		return $this->resourcePublisher->publishPersistentResource($resource);
	}

	public function getJsBundleUri($name) {
		$path = 'Bundles.Js';

		$am = new AssetManager();
		$bundles = $this->getAssetConfiguration($path);

		foreach ($bundles as $key => $bundle) {
			$assetConfiguration = $this->applyAlterations($bundles[$key]["Files"]);
			$collection = array();

			if(isset($bundles[$key]["Dependencies"])){
				foreach ($bundles[$key]["Dependencies"] as $dependency) {
					$collection[] = new AssetReference($am, $dependency);
				}
			}
			$collection[] = new \TYPO3\Asset\Asset\ConfigurationAsset($assetConfiguration);
			
			$am->set($key, new AssetCollection($collection));
		}

		$js = new AssetCollection(array(
		    new AssetReference($am, $name),
		));

		$resource = $this->resourceManager->createResourceFromContent($js->dump(), $name . ".js");
		return $this->resourcePublisher->publishPersistentResource($resource);
	}
}

?>