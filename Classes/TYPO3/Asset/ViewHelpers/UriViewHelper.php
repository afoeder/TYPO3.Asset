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
     * @var TYPO3\Flow\Cache\CacheManager
     * @Flow\Inject
     */
    protected $cacheManager;

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
        $start = microtime();
        if ($package === NULL) {
            $package = $this->controllerContext->getRequest()->getControllerPackageKey();
        }

        $packageResourcePath = $this->packageManager->getPackage($package)->getResourcesPath();

        $path = $packageResourcePath . 'Public/' . $path;
        $outputPath = $packageResourcePath . 'Public/' . $output;

        $lastModified = $this->getLastModified($path);
        if (!file_exists($outputPath) || $lastModified > filemtime($outputPath)) {
            $this->requireDependencies();
            file_put_contents($outputPath, $this->codekitMerge($path));

            $assetManager = new AssetManager();
            $filterManager = new FilterManager();

            $filterManager->set('less', new Filter\LessphpFilter());
            $filterManager->set('sass', new Filter\Sass\SassFilter());
            $filterManager->set('scss', new Filter\Sass\ScssFilter());
            $filterManager->set('compass', new Filter\Sass\ScssFilter());
            $filterManager->set('scssphp', new Filter\ScssphpFilter());

            $root = FLOW_PATH_ROOT;
            $buildPath = $this->environment->getPathToTemporaryDirectory() . 'Assetic/';

            $factory = new AssetFactory($root);
            $factory->setAssetManager($assetManager);
            $factory->setFilterManager($filterManager);
            $factory->setDefaultOutput($this->environment->getPathToTemporaryDirectory() . 'Assetic/');
            $factory->setDebug($debug);
            $factory->addWorker(new CacheBustingWorker());

            if ($filters !== NULL) {
                $filters = explode(',', $filters);
                try {
                    $asset = $factory->createAsset(array($outputPath), $filters);
                    file_put_contents($outputPath, $asset->dump());
                } catch(\Exception $e) {
                    unlink($outputPath);
                    throw $e;
                }
            }
        }
        return $this->resourcePublisher->getStaticResourcesWebBaseUri() . 'Packages/' . $package . '/' . $output;
    }

    public function getLastModified($path, $cacheFlush = FALSE) {
        $cache = $this->cacheManager->getCache('TYPO3_Asset_FileCache');
        $identifier = sha1('Files: ' . $path);
        $modificationTimes = $cache->get($identifier);

        $cacheFlush = TRUE;
        if ($modificationTimes === FALSE || $cacheFlush === TRUE) {
            $files = $this->getFiles($path);
            $modificationTimes = array();
            foreach ($files as $file) {
                $modificationTimes[$file] = filemtime($file);
            }
            $cache->set($identifier, $modificationTimes);
        } else {
            foreach ($modificationTimes as $file => $modificationTime) {
                if (filemtime($file) !== $modificationTime) {
                    return $this->getLastModified($path, TRUE);
                }
            }
        }

        return max($modificationTimes);
    }

    public function getFiles($file, $files = array()) {
        $files[]= $file;
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $content = file_get_contents($file);

        $this->checkCaseSensitivity($file);

        preg_match_all('/@codekit-(append|prepend)[ "\']*([^\'"]*)/', $content, $matches);
        if (count($matches[2]) > 0) {
            foreach ($matches[2] as $importedFile) {
                $importedFilePath = realpath(dirname($file) . '/' . $importedFile);
                if ($importedFilePath === FALSE) {
                    throw new Exception('Asset could not be found
                        Referenced file: ' . $importedFile . '
                        Source File: ' . str_replace(FLOW_PATH_ROOT, '', $file) . '
                    ');
                }
                $files = $this->getFiles($importedFilePath, $files);
            }
        }

        switch ($extension) {
            case 'less':
                preg_match_all('/@import[ "\'\(]*([^"\']*)[\)"\';]*/', $content, $matches);
                if (count($matches[1]) > 0) {
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
                }
                break;

            case 'sass':
            case 'scss':
                preg_match_all('/@import[ "\'\(]*([^"\']*)[\)"\';]*/', $content, $matches);
                if (count($matches[1]) > 0) {
                    foreach ($matches[1] as $importedFile) {
                        $importedFileWithoutExtension = str_replace('.' . $extension, '', $importedFile);
                        $importedFilePath1 = realpath(dirname($file) . '/_' . $importedFileWithoutExtension . '.' . $extension);
                        $importedFilePath2 = realpath(dirname($file) . '/' . $importedFileWithoutExtension . '.' . $extension);
                        if ($importedFilePath1 === FALSE && $importedFilePath2 === FALSE) {
                            throw new Exception('Asset could not be found
                                Referenced file: ' . $importedFile . '
                                Source File: ' . str_replace(FLOW_PATH_ROOT, '', $file) . '
                            ');
                        }
                        $files = $this->getFiles($importedFilePath1 == FALSE ? $importedFilePath2 : $importedFilePath1, $files);
                    }
                }
                break;
                break;

            default:
                break;
        }
        return $files;
    }

    public function codekitMerge($file) {
        $content = file_get_contents($file);
        $prepend = '';
        $append = '';
        preg_match_all('/@codekit-(append|prepend)[ "\']*([^\'"]*)/', $content, $matches);
        if (count($matches[2]) > 0) {
            foreach ($matches[2] as $key => $importedFile) {
                $importedFilePath = realpath(dirname($file) . '/' . $importedFile);
                if ($matches[1][$key] === 'prepend') {
                    $prepend.= $this->codekitMerge($importedFilePath);
                }
                if ($matches[1][$key] === 'append') {
                    $append.= $this->codekitMerge($importedFilePath);
                }
            }
        }
        return $prepend . $content . $append;
    }

    public function requireDependencies() {
        $lessphpPath = $this->packageManager->getPackage('leafo.lessphp')->getPackagePath();
        require_once($lessphpPath . 'lessc.inc.php');

        $scssphpPath = $this->packageManager->getPackage('leafo.scssphp')->getPackagePath();
        require_once($scssphpPath . 'scss.inc.php');
    }

    public function checkCaseSensitivity($file) {
        $parts = explode('/', $file);
        $realPath = '/' . array_shift($parts);
        foreach ($parts as $part) {
            $paths = scandir($realPath);
            foreach ($paths as $path) {
                if (strtolower($path) === strtolower($part)) {
                    $realPath.= '/' . $path;
                    break;
                }
            }
        }
        $realPath = '/' . ltrim($realPath, '/');

        if ($realPath !== $file) {
            throw new Exception('The Asset you\'re trying to reference has an case sensitivity issue
                referenced files vs real filename:
                ' . str_replace(FLOW_PATH_ROOT, '', $file) . '
                ' . str_replace(FLOW_PATH_ROOT, '', $realPath));
        }
    }
}