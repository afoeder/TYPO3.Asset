<?php
namespace TYPO3\Asset\Asset;

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Assetic\Asset\AssetInterface;

/**
 * Manages assets.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetManager extends \Assetic\AssetManager {
    
    private $assets = array();

    /**
     * Gets an asset by name.
     *
     * @param string $name The asset name
     *
     * @return AssetInterface The asset
     *
     * @throws InvalidArgumentException If there is no asset by that name
     */
    public function get($name) {
        if (!isset($this->assets[$name])) {
            throw new \InvalidArgumentException(sprintf('There is no "%s" asset.', $name));
        }

        return $this->assets[$name];
    }

    /**
     * Checks if the current asset manager has a certain asset.
     *
     * @param string $name an asset name
     *
     * @return Boolean True if the asset has been set, false if not
     */
    public function has($name) {
        return isset($this->assets[$name]);
    }

    /**
     * Registers an asset to the current asset manager.
     *
     * @param string         $name  The asset name
     * @param AssetInterface $asset The asset
     */
    public function set($name, AssetInterface $asset) {

        $this->assets[$name] = $asset;
    }

    /**
     * Returns an array of asset names.
     *
     * @return array An array of asset names
     */
    public function getNames() {
        return array_keys($this->assets);
    }
}
