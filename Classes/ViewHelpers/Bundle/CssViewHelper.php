<?php
namespace TYPO3\Asset\ViewHelpers\Bundle;

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 *
 * @api
 */
class CssViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {
	/**
	 * @var TYPO3\Asset\Service\AssetService
	 * @FLOW3\Inject
	 */
	protected $assetService;

	/**
	 * @var string
	 */
	protected $tagName = 'link';

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('rel', 'string', 'Specifies the relationship between the current document and the linked stylesheet');
	}

	/**
	 * Render the link.
	 *
	 * @param string $name of the Bundle
	 * @return string The rendered link
	 * @api
	 */
	public function render($name) {
		$uris = $this->assetService->getCssBundleUris($name);
		$output = "";
		foreach ($uris as $uri) {
			$this->tag->addAttribute("rel", "stylesheet");
			$this->tag->addAttribute("href", $uri);
			$output.= $this->tag->render() . chr(10);
		}
		return $output;
	}
}


?>