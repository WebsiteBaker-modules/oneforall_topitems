<?php

/*
  Snippet developed for the Open Source Content Management System Website Baker (http://websitebaker.org)
  Copyright (C) 2016, Christoph Marti

  LICENCE TERMS:
  This snippet is free software. You can redistribute it and/or modify it 
  under the terms of the GNU General Public License  - version 2 or later, 
  as published by the Free Software Foundation: http://www.gnu.org/licenses/gpl.html.

  DISCLAIMER:
  This snippet is distributed in the hope that it will be useful, 
  but WITHOUT ANY WARRANTY; without even the implied warranty of 
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
  GNU General Public License for more details.


  -----------------------------------------------------------------------------------------
   Code snippet OneForAll TopItems for Website Baker v2.6.x
  -----------------------------------------------------------------------------------------

*/



// Function to display featured events of the events module (invoke function from template or code page)
if (!function_exists('oneforall_topitems')) {
	function oneforall_topitems($section_id, $num_of_items = 3) {



		// ************************************************************
		// MAKE YOUR MODIFICATIONS TO THE LAYOUT OF THE ITEMS DISPLAYED
		// ************************************************************

		// Use this html for the layout
		$setting_header = '<div id="mod_oneforall_topitems_wrapper">
		<h2>OneForAll TopItems</h2>';

		$setting_item_loop = '<a href="[LINK]">
		<div class="mod_oneforall_topitems_item">
		<h3>[TITLE]</h3>
		<ul>
			<li class="mod_oneforall_topitems_field_1">[FIELD_1]</li>
			<li class="mod_oneforall_topitems_field_2">[FIELD_2]</li>
			<li class="mod_oneforall_topitems_field_3">[FIELD_3]</li>
		</ul>
		</div>
		</a>
		';
	
		$setting_footer = '</div>';
		// End layout html




		// **************************************************************************
		// DO NOT CHANGE ANYTHING BEYOND THIS LINE UNLESS YOU KNOW WHAT YOU ARE DOING
		// **************************************************************************

		global $wb, $database;
		$output = '';

		// Get module name corresponding with section id
		$mod_name = $database->get_one("SELECT module FROM ".TABLE_PREFIX."sections WHERE section_id = '$section_id'");

		if (empty($mod_name)) {
			$output = 'ERROR: No module found for section id '.$section_id.'.';
		}
		else {

			// Look for language file
			$module_name = $mod_name;
			if (LANGUAGE_LOADED && !isset($MOD_ONEFORALL[$mod_name])) {
				include(WB_PATH.'/languages/EN.php');
				if (file_exists(WB_PATH.'/languages/'.LANGUAGE.'.php')) {
					include(WB_PATH.'/languages/'.LANGUAGE.'.php');
				}
				include(WB_PATH.'/modules/'.$mod_name.'/languages/EN.php');
				if (file_exists(WB_PATH.'/modules/'.$mod_name.'/languages/'.LANGUAGE.'.php')) {
					include(WB_PATH.'/modules/'.$mod_name.'/languages/'.LANGUAGE.'.php');
				}
			}



			// GENERATE THE PLACEHOLDERS
			
			// Make array of general placeholders
			$general_placeholders = array('[PAGE_TITLE]', '[THUMB]', '[THUMBS]', '[IMAGE]', '[IMAGES]', '[TITLE]', '[ITEM_ID]', '[LINK]', '[DATE]', '[TIME]', '[USER_ID]', '[USERNAME]', '[DISPLAY_NAME]', '[USER_EMAIL]', '[TEXT_READ_MORE]', '[TXT_ITEM]');

			// Get the field placeholders
			$query_fields = $database->query("SELECT * FROM ".TABLE_PREFIX."mod_".$mod_name."_fields");
			
			if ($query_fields->numRows() > 0) {
				while ($field = $query_fields->fetchRow()) {

					// Array with field placeholders
					$field_id                  = $field['field_id'];
					$field_placeholders_name[] = '['.strtoupper(stripslashes($field['name'])).']';
					$field_placeholders_num[]  = '[FIELD_'.$field_id.']';

					// Array with field types, label and templates
					$types[$field_id]     = $field['type'];
					$extra[$field_id]     = $field['extra'];
					$labels[$field_id]    = $field['label'];
					$templates[$field_id] = $field['template'];
				}
			}
			else {
				$field_placeholders_name = array();
				$field_placeholders_num  = array();
				$templates               = array();
			}


			// LOOP THROUGH AND SHOW ITEMS

			// Limit number of items
			$limit = is_numeric($num_of_items) ? " LIMIT ".$num_of_items : '';
			// Query items
			$query_items = $database->query("SELECT * FROM ".TABLE_PREFIX."mod_".$mod_name."_items WHERE active = '1' AND title != '' ORDER BY position, section_id".$limit);

			// Get items
			if ($query_items->numRows() > 0) {
				while ($item = $query_items->fetchRow()) {
					$item_id = stripslashes($item['item_id']);
					$page_id = stripslashes($item['page_id']);
					$title   = htmlspecialchars(stripslashes($item['title']));
					$uid     = $item['modified_by']; // User who last modified the item
					// Workout date and time of last modified item
					$item_date = gmdate(DATE_FORMAT, $item['modified_when']+TIMEZONE);
					$item_time = gmdate(TIME_FORMAT, $item['modified_when']+TIMEZONE);
					// Work-out the item link
					$item_link = WB_URL.PAGES_DIRECTORY.get_page_link($page_id).$item['link'].PAGE_EXTENSION;

					// Get item fields data
					$query_item_fields = $database->query("SELECT field_id, value FROM ".TABLE_PREFIX."mod_".$mod_name."_item_fields WHERE item_id = ".$item_id);

					if ($query_item_fields->numRows() > 0) {
						while ($item_fields = $query_item_fields->fetchRow()) {

							$field_id          = $item_fields['field_id'];
							$values[$field_id] = trim(stripslashes($item_fields['value']));
							$unserialized      = @unserialize($values[$field_id]);

							// For textareas convert newline to <br>
							if ($types[$field_id] == 'textarea') {
								$values[$field_id] = nl2br($values[$field_id]);
							}

							// For wysiwyg replace [wblinkXX] by real link (XX = PAGE_ID)
							if ($types[$field_id] == 'wysiwyg') {
								$pattern = '/\[wblink(.+?)\]/s';
								preg_match_all($pattern, $values[$field_id], $ids);
								foreach ($ids[1] as $page_id) {
									$pattern = '/\[wblink'.$page_id.'\]/s';
									// Get page link
									$link              = $database->get_one("SELECT link FROM ".TABLE_PREFIX."pages WHERE page_id = '$page_id' LIMIT 1");
									$page_link         = page_link($link);
									$values[$field_id] = preg_replace($pattern, $page_link, $values[$field_id]);
								}
							}

							// For wb_link convert page_id to page link
							if ($types[$field_id] == 'wb_link' && is_numeric($values[$field_id])) {
								$link = $database->get_one("SELECT link FROM ".TABLE_PREFIX."pages WHERE page_id = '".$values[$field_id]."' LIMIT 1");
								$values[$field_id] = page_link($link);
							}

							// For media add WB_URL to the link
							if ($types[$field_id] == 'media' && !empty($values[$field_id])) {
								$values[$field_id] = WB_URL.MEDIA_DIRECTORY.$values[$field_id];
							}

							// If value is serialized, unserialize it and convert it to string
							if ($unserialized !== false || $values[$field_id] == 'b:0;') {
								// Filter empty values
								$array_size = count(array_filter($unserialized));
								if ($array_size > 0) {
									// For datepickers with start and end use "until" to separate the two dates
									if ($types[$field_id] == 'datepicker_start_end' || $types[$field_id] == 'datetimepicker_start_end') {
										$glue = ' '.$MOD_ONEFORALL[$mod_name]['TXT_DATEDATE_SEPARATOR'].' ';
									} else {
										$glue = ' ';
									}
									$values[$field_id] = implode($glue, $unserialized);
								} else {
									$values[$field_id] = '';
								}
							}

							// For droplet
							if ($types[$field_id] == 'droplet' && !empty($values[$field_id])) {
								// Get the droplet
								$droplet = $database->get_one("SELECT name FROM ".TABLE_PREFIX."mod_droplets WHERE active = 1 AND id = '".$values[$field_id]."' LIMIT 1");
								$values[$field_id] = '[['.$droplet.']]';
							}

							// For select
							if ($types[$field_id] == 'select' && !empty($values[$field_id])) {
								$index     = $values[$field_id] - 1;
								$a_options = explode(',', $extra[$field_id]);
								$values[$field_id] = $a_options[$index];
							}
						}
					}



					// ITEM THUMB(S) AND IMAGE(S)

					// Initialize or reset thumb(s) and image(s) befor laoding next item
					$thumb_arr = array();
					$image_arr = array();
					$thumb     = "";
					$image     = "";

					// Get image data from db
					$query_image = $database->query("SELECT * FROM ".TABLE_PREFIX."mod_".$mod_name."_images WHERE `item_id` = '$item_id' AND `active` = '1' ORDER BY position ASC");
					if ($query_image->numRows() > 0) {
						while ($image = $query_image->fetchRow()) {
							$image       = array_map('stripslashes', $image);
							$image       = array_map('htmlspecialchars', $image);
							$img_id      = $image['img_id'];
							$image_file  = $image['filename'];
							$img_alt     = $image['alt'];
							$img_title   = $image['title'];
							$img_caption = $image['caption'];

							// Prepare thumb and image directory pathes and urls
							$thumb_dir = WB_PATH.MEDIA_DIRECTORY.'/'.$mod_name.'/thumbs/item'.$item_id.'/';
							$img_dir   = WB_PATH.MEDIA_DIRECTORY.'/'.$mod_name.'/images/item'.$item_id.'/';
							$thumb_url = WB_URL.MEDIA_DIRECTORY.'/'.$mod_name.'/thumbs/item'.$item_id.'/';
							$img_url   = WB_URL.MEDIA_DIRECTORY.'/'.$mod_name.'/images/item'.$item_id.'/';

							// Check if png image has a jpg thumb (version < 0.8 used jpg thumbs only)
							$thumb_file = $image_file;
							if (!file_exists($thumb_dir.$thumb_file)) {
								$thumb_file = str_replace('.png', '.jpg', $thumb_file);
							}

							// Make array of all item thumbs and images
							if (file_exists($thumb_dir.$thumb_file) && file_exists($img_dir.$image_file)) {
								$thumb_prepend = '<a href="'.$item_link.'"><img src="';
								$img_prepend   = '<img src="';
								$thumb_append  = '" alt="'.$img_alt.'" title="'.$img_title.'" class="mod_'.$mod_name.'_main_thumb_f" /></a>';
								$img_append    = '" alt="'.$img_alt.'" title="'.$img_title.'" class="mod_'.$mod_name.'_main_img_f" />';
							}
							// Make array
							$thumb_arr[] = $thumb_prepend.$thumb_url.$thumb_file.$thumb_append;
							$image_arr[] = $img_prepend.$img_url.$image_file.$img_append;
						}
					}
					// Main thumb/image (image position 1)
					$thumb = empty($thumb_arr[0]) ? '' : $thumb_arr[0];
					$image = empty($image_arr[0]) ? '' : $image_arr[0];
					unset($thumb_arr[0]);
					unset($image_arr[0]);

					// Make strings for use in the item templates
					$thumbs = implode("\n", $thumb_arr);
					$images = implode("\n", $image_arr);



					// REPLACE PLACEHOLDERS BY VALUES

					// Get user data
					if (empty($users[$uid]['username'])) {
						$uid                         = '';
						$users[$uid]['username']     = '';
						$users[$uid]['display_name'] = '';
						$users[$uid]['email']        = '';
					}

					// Make array of general values of current item
					$general_values = array(PAGE_TITLE, $thumb, $thumbs, $image, $images, $title, $item_id, $item_link, $item_date, $item_time, $uid, $users[$uid]['username'], $users[$uid]['display_name'], $users[$uid]['email'], $TEXT['READ_MORE'], $MOD_ONEFORALL[$mod_name]['TXT_ITEM']);

					// Replace placeholders in field templates by label and value
					$ready_templates = array();
					foreach ($templates as $field_id => $template) {

						// If value is empty return a blank template
						if (!isset($values[$field_id]) || empty($values[$field_id])) {
							$template = '';
						} else {
							$search   = array('[CUSTOM_LABEL]', '[CUSTOM_CONTENT]');
							$replace  = array($labels[$field_id], $values[$field_id]);
							$template = str_replace($search, $replace, $template);
						}

						// Array of templates with replaced placeholders
						$ready_templates[] = $template;
					}

					// Print item loop
					$search  = array_merge($general_placeholders, $field_placeholders_name, $field_placeholders_num);
					$replace = array_merge($general_values, $ready_templates, $ready_templates);
					$output .= trim(str_replace($search, $replace, $setting_item_loop));

					// Clear arrays for next item
					unset($values);
					unset($ready_templates);
				}
			}
		}

		// If we have one or more featured event(s), print them
		if (!empty($output)) {
			// Print header
			echo $setting_header;
			// Print event(s)
			echo $output;
			// Print footer
			echo $setting_footer;		
		}
	}
}
?>
