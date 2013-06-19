<?php
namespace lowtone\media\video\youtube\videos;
use lowtone\media\video\videos\Video as Base;

/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2011-2012, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\media\video\youtube\videos
 */
class Video extends Base {

	public function player() {
		return $this->{self::PROPERTY_GUID};
	}

	// Static
	
	public static function __register(array $options = NULL) {
		self::__registerVideoClass("video/x-youtube");

		return get_post_type_object(self::__postType());
	}

}