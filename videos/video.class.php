<?php
namespace lowtone\media\video\youtube\videos;
use lowtone\media\video\videos\Video as Base,
	lowtone\dom\Document,
	lowtone\io\File,
	lowtone\net\URL,
	lowtone\wp\posts\meta\Meta,
	lowtone\google\youtube\videos\Video as YouTubeVideo;

/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2011-2012, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\media\video\youtube\videos
 */
class Video extends Base {

	const MIME_TYPE_YOUTUBE = "video/x-youtube";

	const META_YOUTUBE_ID = "_lowtone_media_video_youtube_id",
		META_YOUTUBE_DATA = "_lowtone_media_video_youtube_data";

	const PLAYER_OPTION_FRAMEBORDER = "frameborder",
		PLAYER_OPTION_ALLOWFULLSCREEN = "allowfullscreen",
		PLAYER_OPTION_AUTOHIDE = "autohide",
		PLAYER_OPTION_CC_LOAD_POLICY = "cc_load_policy",
		PLAYER_OPTION_COLOR = "color",
		PLAYER_OPTION_DISABLEKB = "disablekb",
		PLAYER_OPTION_ENABLEJSAPI = "enablejsapi",
		PLAYER_OPTION_END = "end",
		PLAYER_OPTION_FS = "fs",
		PLAYER_OPTION_IV_LOAD_POLICY = "iv_load_policy",
		PLAYER_OPTION_LIST = "list",
		PLAYER_OPTION_LIST_TYPE = "listType",
		PLAYER_OPTION_MODESTBRANDING = "modestbranding",
		PLAYER_OPTION_ORIGIN = "origin",
		PLAYER_OPTION_PLAYERAPIID = "playerapiid",
		PLAYER_OPTION_PLAYLIST = "playlist",
		PLAYER_OPTION_REL = "rel",
		PLAYER_OPTION_SHOWINFO = "showinfo",
		PLAYER_OPTION_START = "start",
		PLAYER_OPTION_THEME = "theme";

	public function player($options = NULL) {
		if (NULL === ($youTubeId = $this->youTubeId()))
			return NULL;

		$options = array_merge(array(
				self::PLAYER_OPTION_FRAMEBORDER => 0,
				self::PLAYER_OPTION_ALLOWFULLSCREEN => 1,
				self::PLAYER_OPTION_AUTOHIDE => 1,
				self::PLAYER_OPTION_REL => 0,
			), (array) $options);

		$url = URL::fromString("http://www.youtube.com/embed/" . $youTubeId);

		$query = array_intersect_key($options, array_flip(array(
				self::PLAYER_OPTION_AUTOHIDE,
				self::PLAYER_OPTION_CC_LOAD_POLICY,
				self::PLAYER_OPTION_COLOR,
				self::PLAYER_OPTION_DISABLEKB,
				self::PLAYER_OPTION_ENABLEJSAPI,
				self::PLAYER_OPTION_END,
				self::PLAYER_OPTION_FS,
				self::PLAYER_OPTION_IV_LOAD_POLICY,
				self::PLAYER_OPTION_LIST,
				self::PLAYER_OPTION_LIST_TYPE,
				self::PLAYER_OPTION_MODESTBRANDING,
				self::PLAYER_OPTION_ORIGIN,
				self::PLAYER_OPTION_PLAYERAPIID,
				self::PLAYER_OPTION_PLAYLIST,
				self::PLAYER_OPTION_REL,
				self::PLAYER_OPTION_SHOWINFO,
				self::PLAYER_OPTION_START,
				self::PLAYER_OPTION_THEME,
			)));

		$url->query($query);

		$atts = array_intersect_key($options, array_flip(array(
				self::PLAYER_OPTION_WIDTH,
				self::PLAYER_OPTION_HEIGHT,
				self::PLAYER_OPTION_FRAMEBORDER,
				self::PLAYER_OPTION_ALLOWFULLSCREEN,
			)));

		$dataAtt = NULL;

		if (NULL !== ($data = $this->data())) {
			
			$dataAtt = json_encode(array(
					"aspect_ratio" => $data->{'media$group'}->{'yt$aspectRatio'}->{'$t'}
				));

		}

		$atts = array_merge(
				$atts,
				array(
					"src" => (string) $url,
					"data-youtube" => $dataAtt,
					"class" => "youtube embed"
				)
			);

		$player = new Document();

		$iFrame = $player
			->createAppendElement("iframe")
			->setAttributes($atts);

		return $player->saveHtml();
	}

	public function youTubeId() {
		if (!($meta = reset($this->getMeta()->findByKey(self::META_YOUTUBE_ID)))) {
			if (false === ($youTubeId = $this->extractYouTubeId()))
				return NULL;

			update_post_meta($this->{self::PROPERTY_ID}, self::META_YOUTUBE_ID, $youTubeId);
		} else 
			$youTubeId = $meta->{Meta::PROPERTY_META_VALUE};
		
		return $youTubeId;
	}

	public function extractYouTubeId($guid = NULL) {
		if (!isset($guid))
			$guid = $this->{self::PROPERTY_GUID};

		return preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $guid, $matches) ? $matches[1] : false;
	}

	public function data() {
		if (!($meta = reset($this->getMeta()->findByKey(self::META_YOUTUBE_DATA)))) {
			if (false === ($data = $this->fetchData()))
				return NULL;

			update_post_meta($this->{self::PROPERTY_ID}, self::META_YOUTUBE_DATA, $data);
		} else 
			$data = $meta->{Meta::PROPERTY_META_VALUE};
		
		return $data;
	}

	public function fetchData() {
		if (NULL === ($youTubeId = $this->youTubeId()))
			return false;

		$video = new YouTubeVideo(array(
				YouTubeVideo::PROPERTY_VIDEO_ID => $youTubeId,
			));

		return $video->fetchData();
	}

	public function fetchThumbnail($name = NULL) {
		if (NULL === ($data = $this->data()))
			return false;

		$thumbnails = $data->{'media$group'}->{'media$thumbnail'};

		$thumbnail = false;

		if (isset($name)) {

			foreach ($thumbnails as $t) {
				if ($t->{'yt$name'} != $name)
					continue;

				$thumbnail = $t;

				break;

			}

		} else
			$thumbnail = reset($thumbnails);

		if (!$thumbnail)
			return false;

		try {
			$file = File::get($thumbnail->url);
		} catch (\Exception $e) {
			return false;
		}

		return $file;
	}

	// Static
	
	public static function __register(array $options = NULL) {
		self::__registerVideoClass(self::MIME_TYPE_YOUTUBE);

		return get_post_type_object(self::__postType());
	}

}
