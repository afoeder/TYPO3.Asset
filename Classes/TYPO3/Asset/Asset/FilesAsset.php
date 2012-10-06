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

use Assetic\Util\PathUtils;
use Assetic\Filter\FilterInterface;

/**
 * A collection of assets loaded by glob.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class FilesAsset extends \Assetic\Asset\AssetCollection {

    private $files;
    private $initialized;

    /**
     * Constructor.
     *
     * @param string|array $files   A single glob path or array of paths
     * @param array        $filters An array of filters
     * @param string       $root    The root directory
     */
    public function __construct($files, $filters = array(), $root = null, array $vars = array())
    {
        $this->files = (array) $files;
        $this->initialized = false;

        parent::__construct(array(), $filters, $root, $vars);
    }

    public function all()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::all();
    }

    public function load(FilterInterface $additionalFilter = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        parent::load($additionalFilter);
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::dump($additionalFilter);
    }

    public function getLastModified()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::getLastModified();
    }

    public function getIterator()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return parent::getIterator();
    }

    public function setValues(array $values)
    {
        parent::setValues($values);
        $this->initialized = false;
    }

    /**
     * Initializes the collection based on the glob(s) passed in.
     */
    private function initialize()
    {
        foreach ($this->files as $file) {
            $this->add(new \Assetic\Asset\FileAsset($file, array(), $this->getSourceRoot()));
        }

        $this->initialized = true;
    }
}
