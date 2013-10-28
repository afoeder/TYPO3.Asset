<?php
namespace TYPO3\Asset\ViewHelpers;

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Assetic\AssetManager;
use Assetic\AssetWriter;
use Assetic\Cache\FilesystemCache;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\CacheBustingWorker;
use Assetic\Filter;
use Assetic\FilterManager;
use TYPO3\Asset\Asset\AssetCache;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Exception;

/**
 *
 * @api
 */
class UriViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {
    /**
     * @var \TYPO3\Flow\Utility\Environment
     * @Flow\Inject
     */
    protected $environment;

    /**
     * @var \TYPO3\Flow\Package\PackageManagerInterface
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\Publishing\ResourcePublisher
     */
    protected $resourcePublisher;

    /**
     *
     * @param string $path
     * @param string $package
     * @param boolean $debug
     * @param string $output
     * @param string $filters
     * @param string $output
     * @return string The rendered link
     * @api
     */
    public function render($path = NULL, $package = NULL, $debug = false, $output = NULL, $filters = NULL) {

        if ($package === NULL) {
            $package = $this->controllerContext->getRequest()->getControllerPackageKey();
        }

        $packageResourcePath = $this->packageManager->getPackage($package)->getResourcesPath();

        $path = $packageResourcePath . 'Public/' . $path;
        $outputPath = $packageResourcePath . 'Public/' . $output;

        $lastModified = $this->getLastModified($path);
        if ($lastModified > filemtime($outputPath)) {
            $this->requireDependencies();

            $assetManager = new AssetManager();
            $filterManager = new FilterManager();

            $filterManager->set('less', new Filter\LessphpFilter());
            $filterManager->set('sass', new Filter\Sass\SassFilter());

            $root = FLOW_PATH_ROOT;
            $buildPath = $this->environment->getPathToTemporaryDirectory() . 'Assetic/';

            $factory = new AssetFactory($root);
            $factory->setAssetManager($assetManager);
            $factory->setFilterManager($filterManager);
            $factory->setDefaultOutput($this->environment->getPathToTemporaryDirectory() . 'Assetic/');
            $factory->setDebug($debug);
            $factory->addWorker(new CacheBustingWorker());

            $filters = explode(',', $filters);
            $asset = $factory->createAsset(array($path), $filters);
            file_put_contents($outputPath, $asset->dump());
        }
        return $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $package . '/' . $output;
    }

    public function getLastModified($path) {
        $lastModified = 0;
        foreach ($this->getFiles($path) as $file) {
            $lastModified = max($lastModified, filemtime($file));
        }
        return $lastModified;
    }

    public function getFiles($file, $files = array()) {
        $files[]= $file;
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'less':
                $content = file_get_contents($file);
                preg_match_all('/@import[ "\'\(]*([^"\']*)[\)"\';]*/', $content, $matches);
                foreach ($matches[1] as $importedFile) {
                    $importedFilePath = realpath(dirname($file) . '/' . $importedFile);
                    if ($importedFilePath === FALSE) {
                        throw new Exception('Asset could not be found
                            Referenced file: ' . $importedFile . '
                            Source File: ' . str_replace(FLOW_PATH_ROOT, '', $file) . '
                        ');
                    }
                    $files = $this->getFiles($importedFilePath, $files);
                }
                break;

            default:
                break;
        }
        return $files;
    }

    public function requireDependencies() {
        $lessphpPath = $this->packageManager->getPackage('leafo.lessphp')->getPackagePath();
        require_once($lessphpPath . 'lessc.inc.php');
    }
}