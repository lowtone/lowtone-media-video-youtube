<?php
/*
 * Plugin Name: YouTube
 * Plugin URI: http://wordpress.lowtone.nl/media-video-youtube
 * Description: Integrate YouTube in media selector.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */
/**
 * @author Paul van der Meijs <code@lowtone.nl>
 * @copyright Copyright (c) 2011-2012, Paul van der Meijs
 * @license http://wordpress.lowtone.nl/license/
 * @version 1.0
 * @package wordpress\plugins\lowtone\media\video\youtube
 */

namespace lowtone\media\video\youtube {

	use lowtone\content\packages\Package,
		lowtone\media\types\Type,
		lowtone\media\video\youtube\videos\Video,
		lowtone\ui\forms\Form,
		lowtone\ui\forms\Input,
		lowtone\ui\forms\Html,
		lowtone\ui\forms\FieldSet;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array("lowtone", "lowtone\\style", "lowtone\\google\\youtube", "lowtone\\media\\video", "lowtone\\media"),
			Package::INIT_MERGED_PATH => __NAMESPACE__,
			Package::INIT_SUCCESS => function() {

				// Register textdomain
				
				load_plugin_textdomain("lowtone_media_video_youtube", false, basename(__DIR__) . "/assets/languages");

				// Register Video class

				Video::__register();

				// Add media type

				\lowtone\media\addMediaType(new Type(array(
						Type::PROPERTY_TITLE => __("YouTube", "lowtone_media_video_youtube"),
						Type::PROPERTY_NEW_FILE_TEXT => __("Add a reference to a video on YouTube.", "lowtone_media_video_youtube"),
						Type::PROPERTY_SLUG => "youtube",
						Type::PROPERTY_IMAGE => plugins_url("/assets/images/youtube-icon.png", __FILE__),
						Type::PROPERTY_NEW_FILE_CALLBACK => function() {
								// require_once('./admin.php');

								global $post_type, $post_type_object, $title, $editing, $post, $post_ID;

								$post_type = Video::__postType();

								$post_type_object = get_post_type_object( $post_type );
								
								$title = __("Add YouTube video", "lowtone_media_video_youtube");

								$editing = true;

								if (!current_user_can($post_type_object->cap->edit_posts) || ! current_user_can($post_type_object->cap->create_posts))
									wp_die(__('Cheatin&#8217; uh?'));

								// Schedule auto-draft cleanup
								
								if (!wp_next_scheduled("wp_scheduled_auto_draft_delete"))
									wp_schedule_event(time(), "daily", "wp_scheduled_auto_draft_delete");

								wp_enqueue_script("autosave");

								// Show post form.
								
								$post = get_default_post_to_edit($post_type, true);

								$post->{Video::PROPERTY_POST_MIME_TYPE} = Video::MIME_TYPE_YOUTUBE;

								$post_ID = $post->ID;

								add_meta_box(
										"lowtone_media_video_youtube_video",
										__("YouTube video", "lowtone_media_video_youtube"),
										function() {

											wp_enqueue_style("lowtone_media_video_youtube_form", plugins_url("/assets/styles/youtube-form.css", __FILE__), array("lowtone_style_grid"));
											wp_enqueue_script("lowtone_media_video_youtube_form", plugins_url("/assets/scripts/youtube-form.js", __FILE__), array("angular"));
											
											$form = new Form();

											$form
												->createFieldSet(array(
													FieldSet::PROPERTY_ATTRIBUTES => array(
														"ng-app" => "",
														"ng-controller" => "YouTubeSearchCtrl"
													)
												))
												->appendChild(
													$form
														->createInput(Input::TYPE_HIDDEN, array(
															Input::PROPERTY_NAME => array("lowtone_media_video_youtube", "guid"),
															Input::PROPERTY_VALUE => "{{video_url}}"
														))
												)
												->appendChild(
													$form
														->createInput(Input::TYPE_TEXT, array(
															Input::PROPERTY_NAME => array("lowtone_media_video_youtube", "search_query"),
															Input::PROPERTY_PLACEHOLDER => __("Search video", "lowtone_media_video_youtube_add"),
															Input::PROPERTY_ATTRIBUTES => array(
																"ng-model" => "search_query",
																"ng-change" => "search()"
															)
														))
												)
												->appendChild(
													$form
														->createHtml(array(
															Html::PROPERTY_CONTENT => '<ul class="results">' . 
																'<li ng-repeat="result in results" ng-click="select(result)" ng-class="result.class" class="one-fourth column">' . 
																'<figure>' . 
																'<img ng-repeat="thumbnail in result.media$group.media$thumbnail" src="{{thumbnail.url}}" alt="" class="{{thumbnail.yt$name}}">' .
																'</figure>' .
																'<h3>{{result.title.$t}}</h3>' .
																'</li>'
														))
												)
												->out();

											$form
												->createInput(Input::TYPE_HIDDEN, array(
													Input::PROPERTY_NAME => array("lowtone_media_video_youtube", "post_mime_type"),
													Input::PROPERTY_VALUE => Video::MIME_TYPE_YOUTUBE
												))
												->out();

										}
									);

								add_meta_box(
										"submitdiv", 
										__("Save"), 
										"attachment_submit_meta_box", 
										Video::__postType(), 
										"side", 
										"core" 
									);

								include "edit-form-advanced.php";

								// include('./admin-footer.php');
							}
					)));

				// Add tab to media uploader

				add_filter("media_upload_tabs", function($tabs) {
					$tabs["youtube"] = __("YouTube", "lowtone_media_video_youtube");

					return $tabs;
				});

				// Replace link with YouTube URL

				add_filter("attachment_link", function($link, $postId) {
					if (($post = get_post($postId)) && Video::MIME_TYPE_YOUTUBE == $post->{Video::PROPERTY_POST_MIME_TYPE})
						return $post->{Video::PROPERTY_GUID};

					return $link;
				}, 10, 2);

				// Save post
				
				$saveAttachment = function($postId) {
					if (!isset($_POST["lowtone_media_video_youtube"]))
						return;

					$post = get_post($postId);

					$post->{Video::PROPERTY_GUID} = $_POST["lowtone_media_video_youtube"]["guid"];
					$post->{Video::PROPERTY_POST_MIME_TYPE} = $_POST["lowtone_media_video_youtube"]["post_mime_type"];

					unset($_POST["lowtone_media_video_youtube"]);

					wp_update_post($post);

					if (!has_post_thumbnail($post->{Video::PROPERTY_ID})) {

						$video = Video::fromPost($post);
						
						if ($thumbnail = $video->fetchThumbnail("sddefault")) 
							$video->thumbnail($thumbnail);

					}
				};
				
				add_action("add_attachment", $saveAttachment);
				add_action("edit_attachment", $saveAttachment);

				// Embed JS
				
				$enqueueEmbedScript = function() {
					wp_enqueue_script("lowtone_media_video_youtube_embed", plugins_url("/assets/scripts/jquery.youtube-embed.js", __FILE__), array("jquery"));
				};
				
				add_action("wp_enqueue_scripts", $enqueueEmbedScript);
				add_action("admin_enqueue_scripts", $enqueueEmbedScript);

				return true;
			}
		));

	if (!$__i)
		return false;

}
