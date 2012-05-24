<?php
namespace TYPO3\Asset\Asset;

/* *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Assetic\Util\PathUtils;
use Assetic\Filter\FilterInterface;

/**
 * Represents an asset loaded from a file.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class ConfigurationAsset extends \Assetic\Asset\BaseAsset {
   /**
     * @var array
     */
    protected $configuration = array();

    /**
     * Constructor.
     *
     * @param array $configuration
     * @param boolean  $mergeFiles Set this to true to merge the specified files before loading them. This is, for example useful to load a bunch of less files which rely on each another
     * @param array  $filters    An array of filters
     * @param string $sourceRoot The source asset root directory
     * @param string $sourcePath The source asset path
     * @param array $vars 
     *
     * @throws InvalidArgumentException If the supplied root doesn't match the source when guessing the path
     */
    public function __construct($configuration, $mergeFiles = false, $filters = array(), $sourceRoot = null, $sourcePath = null, array $vars = array()) {
        $this->configuration = $configuration;
        $this->mergeFiles = $mergeFiles;

        parent::__construct($filters, $sourceRoot, $sourcePath, $vars);
    }

    public function load(FilterInterface $additionalFilter = null) {
        $data = "";
        foreach ($this->configuration as $source) {
            $source = trim($source, ";'");

            if (!file_exists($source)) {
                throw new \RuntimeException(sprintf('The source file "%s" does not exist.', $source));
            }

            if($this->mergeFiles)
                $data.= file_get_contents($source) . "\n";
            else
                $this->doLoad(file_get_contents($source), $additionalFilter);
        }

        if($this->mergeFiles)
            $this->doLoad($data, $additionalFilter);
    }

    public function getLastModified() {
        $filemtime = 0;
        foreach ($this->configuration as $source) {
            $source = PathUtils::resolvePath($this->source, $this->getVars(),
                $this->getValues());

            if (!is_file($source)) {
                throw new \RuntimeException(sprintf('The source file "%s" does not exist.', $source));
            }
            $filemtime = filemtime($source) > $filemtime ? filemtime($source) : $filemtime;
        }
        return $filemtime;
    }
}
