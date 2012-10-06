<?php
namespace TYPO3\Asset\Configuration;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Utility\Arrays;

/**
 * A general purpose configuration manager
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class ConfigurationManager extends \TYPO3\FLOW3\Configuration\ConfigurationManager {

	const CONFIGURATION_TYPE_ASSETS = 'Assets';

	public function __construct(\TYPO3\FLOW3\Object\ObjectManager $objectManager) {
		parent::__construct($objectManager->getContext());
		$this->packages = $objectManager->get("TYPO3\FLOW3\Package\PackageManagerInterface")->getActivePackages();
	}

	/**
	 * Get the available configuration-types
	 *
	 * @return array<string> array of configuration-type identifier strings
	 */
	public function getAvailableConfigurationTypes(){
		return array_merge(
			parent::getAvailableConfigurationTypes(), 
			array(self::CONFIGURATION_TYPE_ASSETS)
		);
	}

	/**
	 * Returns the specified raw configuration.
	 * The actual configuration will be merged from different sources in a defined order.
	 *
	 * Note that this is a low level method and only makes sense to be used by FLOW3 internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param string $packageKey The package key to fetch configuration for.
	 * @return array The configuration
	 * @throws \TYPO3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException on invalid configuration types
	 */
	public function getConfiguration($configurationType, $packageKey = NULL) {
		$configuration = array();
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_ASSETS :
				if (!isset($this->configurations[$configurationType]) || $this->configurations[$configurationType] === array()) {
					$this->configurations[$configurationType] = array();
					$this->loadConfiguration($configurationType, $this->packages);
				}
				if (isset($this->configurations[$configurationType])) {
					$configuration = &$this->configurations[self::CONFIGURATION_TYPE_ASSETS];
				}
			break;

			default :
				return parent::getConfiguration($configurationType, $packageKey);
		}

		if ($packageKey !== NULL && $configuration !== NULL) {
			return (Arrays::getValueByPath($configuration, $packageKey));
		} else {
			return $configuration;
		}
	}

	/**
	 * Loads special configuration defined in the specified packages and merges them with
	 * those potentially existing in the global configuration folders. The result is stored
	 * in the configuration manager's configuration registry and can be retrieved with the
	 * getConfiguration() method.
	 *
	 * @param string $configurationType The kind of configuration to load - must be one of the CONFIGURATION_TYPE_* constants
	 * @param array $packages An array of Package objects (indexed by package key) to consider
	 * @return void
	 * @throws \TYPO3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException
	 */
	protected function loadConfiguration($configurationType, array $packages) {
		$this->cacheNeedsUpdate = TRUE;

		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_ASSETS :

					// Make sure that the FLOW3 package is the first item of the packages array:
				if (isset($packages['TYPO3.FLOW3'])) {
					$flow3Package = $packages['TYPO3.FLOW3'];
					unset($packages['TYPO3.FLOW3']);
					$packages = array_merge(array('TYPO3.FLOW3' => $flow3Package), $packages);
					unset($flow3Package);
				}

				$settings = array();
				foreach ($packages as $packageKey => $package) {
					if (Arrays::getValueByPath($settings, $packageKey) === NULL) {
						$settings = Arrays::setValueByPath($settings, $packageKey, array());
					}
					$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . self::CONFIGURATION_TYPE_ASSETS));
				}
				$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . self::CONFIGURATION_TYPE_ASSETS));

				foreach ($this->orderedListOfContextNames as $contextName) {
					foreach ($packages as $package) {
						$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . self::CONFIGURATION_TYPE_ASSETS));
					}
					$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $contextName . '/' . self::CONFIGURATION_TYPE_ASSETS));
				}

				if ($this->configurations[self::CONFIGURATION_TYPE_ASSETS] !== array()) {
					$this->configurations[self::CONFIGURATION_TYPE_ASSETS] = Arrays::arrayMergeRecursiveOverrule($this->configurations[self::CONFIGURATION_TYPE_ASSETS], $settings);
				} else {
					$this->configurations[self::CONFIGURATION_TYPE_ASSETS] = $settings;
				}

				$this->configurations[self::CONFIGURATION_TYPE_ASSETS]['TYPO3']['FLOW3']['core']['context'] = (string)$this->context;
			break;

			default:
				parent::loadConfiguration($configurationType, $packages);
		}

		$this->postProcessConfiguration($this->configurations[$configurationType]);
	}
}
?>