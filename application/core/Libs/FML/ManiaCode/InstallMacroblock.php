<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a macroblock
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class InstallMacroblock implements Element {
	/*
	 * Protected properties
	 */
	protected $tagName = 'install_macroblock';
	protected $name = null;
	protected $file = null;
	protected $url = null;

	/**
	 * Create a new InstallMacroblock object
	 *
	 * @param string $name (optional) Macroblock name
	 * @param string $url  (optional) Macroblock url
	 * @return \FML\ManiaCode\InstallMacroblock|static
	 */
	public static function create($name = null, $url = null) {
		return new static($name, $url);
	}

	/**
	 * Construct a new InstallMacroblock object
	 *
	 * @param string $name (optional) Macroblock name
	 * @param string $file (optional) Macroblock file
	 * @param string $url  (optional) Macroblock url
	 */
	public function __construct($name = null, $file = null, $url = null) {
		if (!is_null($name)) {
			$this->setName($name);
		}
		if (!is_null($file)) {
			$this->setFile($file);
		}
		if (!is_null($url)) {
			$this->setUrl($url);
		}
	}

	/**
	 * Set the name of the macroblock
	 *
	 * @param string $name Macroblock name
	 * @return \FML\ManiaCode\InstallMacroblock|static
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the file of the macroblock
	 *
	 * @param string $file Macroblock file
	 * @return \FML\ManiaCode\InstallMacroblock|static
	 */
	public function setFile($file) {
		$this->file = (string)$file;
		return $this;
	}

	/**
	 * Set the url of the macroblock
	 *
	 * @param string $url Macroblock url
	 * @return \FML\ManiaCode\InstallMacroblock|static
	 */
	public function setUrl($url) {
		$this->url = (string)$url;
		return $this;
	}

	/**
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement  = $domDocument->createElement($this->tagName);
		$nameElement = $domDocument->createElement('name', $this->name);
		$xmlElement->appendChild($nameElement);
		$fileElement = $domDocument->createElement('file', $this->file);
		$xmlElement->appendChild($fileElement);
		$urlElement = $domDocument->createElement('url', $this->url);
		$xmlElement->appendChild($urlElement);
		return $xmlElement;
	}
}
