<?php

namespace FML\Types;

/**
 * Interface for Elements with media attributes
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Playable {

	/**
	 * Set data
	 *
	 * @param string $data Media url
	 * @return \FML\Types\Playable|static
	 */
	public function setData($data);

	/**
	 * Set data id to use from Dico
	 *
	 * @param string $dataId Data id
	 * @return \FML\Types\Playable|static
	 */
	public function setDataId($dataId);

	/**
	 * Set play
	 *
	 * @param bool $play Whether the Control should start playing automatically
	 * @return \FML\Types\Playable|static
	 */
	public function setPlay($play);

	/**
	 * Set looping
	 *
	 * @param bool $looping Whether the Control should play looping
	 * @return \FML\Types\Playable|static
	 */
	public function setLooping($looping);

	/**
	 * Set music
	 *
	 * @param bool $music Whether the Control represents background music
	 * @return \FML\Types\Playable|static
	 */
	public function setMusic($music);

	/**
	 * Set volume
	 *
	 * @param float $volume Media volume
	 * @return \FML\Types\Playable|static
	 */
	public function setVolume($volume);
}
