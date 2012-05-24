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
class JsViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {
	/**
	 * @var TYPO3\Asset\Service\AssetService
	 * @FLOW3\Inject
	 */
	protected $assetService;

	/**
	 * @var string
	 */
	protected $tagName = 'script';

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('type', 'string', 'Type of the Script');
		$this->registerTagAttribute('src', 'string', 'Uri to the Script');
	}

	/**
	 * Render the link.
	 *
	 * @param string $name of the Bundle
	 * @return string The rendered link
	 * @api
	 */
	public function render($name) {
		$uri = $this->assetService->getJsBundleUri($name);
		$this->tag->addAttribute("type", "text/javascript");
		$this->tag->addAttribute("src", $uri);
		$this->tag->forceClosingTag(true);
		return $this->tag->render();
	}
}


?>