<?php

/*
Plugin Name: img.ly gallery
Version: 1.0
Plugin URI: http://fis.io/imgly-gallery.html
Description: Display your recent img.ly pictures gallery in a widget. based on My Twitpics plugin by Pepijn Koning.
Author: @fisio
Author URI: http://fis.io/
*/

/*  Copyright 2009 Pepijn Koning

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('MAGPIE_CACHE_ON', 0); //2.7 Cache Bug

$imgly_options['widget_fields']['title'] = array('label'=>'Widget title:', 'type'=>'text', 'default'=>'My img.ly gallery');
$imgly_options['widget_fields']['username'] = array('label'=>'Twitter ID:', 'type'=>'text', 'default'=>'');
$imgly_options['widget_fields']['num'] = array('label'=>'Number of pics:', 'type'=>'text', 'default'=>'4');
$imgly_options['widget_fields']['size'] = array('label'=>'Picture size:', 'type'=>'text', 'default'=>'75');
$imgly_options['widget_fields']['margin'] = array('label'=>'Margin:', 'type'=>'text', 'default'=>'5');
$imgly_options['widget_fields']['border'] = array('label'=>'Border:', 'type'=>'text', 'default'=>'1');
$imgly_options['widget_fields']['bordercolor'] = array('label'=>'Border color:', 'type'=>'text', 'default'=>'#FFFFFF');
$imgly_options['widget_fields']['linked'] = array('label'=>'Linked photos:', 'type'=>'checkbox', 'default'=>false);

$imgly_options['prefix'] = 'imgly';


// Display img.ly last added photo's
function imgly_pics($username = '', $num = 4, $linked = true, $size = 75, $margin = 5, $border = 0, $bordercolor = '#FFFFFF') {
	$file = @file_get_contents("http://img.ly/images/".$username.".rss");

	for($i = 1; $i <= $num; ++$i) {

		$imageid = explode('<title>http://img.ly/', $file);
		$imageid = explode('</title>', $imageid[$i]);
		$imageid = trim($imageid[0]);

		if($linked == true) {
			echo '<a href="http://img.ly/'.$imageid.'" target="_blank">';
		}
			echo '<img src="http://img.ly/show/mini/'.$imageid.'" width="'.$size.'" height="'.$size.'" style="margin: '.$margin.'px; border: '.$border.'px solid '.$bordercolor.';" class="imgly" />';
		if($linked == true) {
			echo '</a>';
		}
	}
}


// img.ly widget stuff
function widget_imgly_init() {
	if (!function_exists('register_sidebar_widget'))
		return;
	
		$check_options = get_option('widget_imgly');
  		if ($check_options['number']=='') {
    			$check_options['number'] = 1;
    			update_option('widget_imgly', $check_options);
  		}

	function widget_imgly($args, $number = 1) {
	
	global $imgly_options;
		
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		include_once(ABSPATH . WPINC . '/rss.php');
		$options = get_option('widget_imgly');
		
		// fill options with default values if value is not set
		$item = $options[$number];
		foreach($imgly_options['widget_fields'] as $key => $field) {
			if (! isset($item[$key])) {
				$item[$key] = $field['default'];
			}
		}
		
		// These lines generate our output.
		echo $before_widget . $before_title . $item['title'] . $after_title;
			echo '<p class="widget_imgly_p">';
			imgly_pics($item['username'], $item['num'], $item['linked'], $item['size'], $item['margin'], $item['border'], $item['bordercolor']);
			echo '</p>';
		echo $after_widget;
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_imgly_control($number) {

		global $imgly_options;

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_imgly');
		
		if ( isset($_POST['imgly-submit']) ) {

			foreach($imgly_options['widget_fields'] as $key => $field) {
				$options[$number][$key] = $field['default'];
				$field_name = sprintf('%s_%s_%s', $imgly_options['prefix'], $key, $number);

				if ($field['type'] == 'text') {
					$options[$number][$key] = strip_tags(stripslashes($_POST[$field_name]));
				} elseif ($field['type'] == 'checkbox') {
					$options[$number][$key] = isset($_POST[$field_name]);
				}
			}

			update_option('widget_imgly', $options);
		}

		foreach($imgly_options['widget_fields'] as $key => $field) {
			
			$field_name = sprintf('%s_%s_%s', $imgly_options['prefix'], $key, $number);
			$field_checked = '';
			if ($field['type'] == 'text') {
				$field_value = htmlspecialchars($options[$number][$key], ENT_QUOTES);
			} elseif ($field['type'] == 'checkbox') {
				$field_value = 1;
				if (! empty($options[$number][$key])) {
					$field_checked = 'checked="checked"';
				}
			}
			
			printf('<p style="text-align:right;" class="imgly_field"><label for="%s">%s <input id="%s" name="%s" type="%s" value="%s" class="%s" %s /></label></p>',
				$field_name, __($field['label']), $field_name, $field_name, $field['type'], $field_value, $field['type'], $field_checked);
		}
		echo '<input type="hidden" id="imgly-submit" name="imgly-submit" value="1" />';
	}
	

	function widget_imgly_setup() {
		$options = $newoptions = get_option('widget_imgly');
		
		if ( isset($_POST['imgly-number-submit']) ) {
			$number = (int) $_POST['imgly-number'];
			$newoptions['number'] = $number;
		}
		
		if ( $options != $newoptions ) {
			update_option('widget_imgly', $newoptions);
			widget_imgly_register();
		}
	}
	
	function widget_imgly_register() {
		
		$options = get_option('widget_imgly');
		$dims = array('width' => 300, 'height' => 300);
		$class = array('classname' => 'widget_imgly');

		for ($i = 1; $i <= 9; $i++) {
			$name = sprintf(__('img.ly gallery'), $i);
			$id = "imgly-$i"; // Never never never translate an id
			wp_register_sidebar_widget($id, $name, $i <= $options['number'] ? 'widget_imgly' : /* unregister */ '', $class, $i);
			wp_register_widget_control($id, $name, $i <= $options['number'] ? 'widget_imgly_control' : /* unregister */ '', $dims, $i);
		}
		
		add_action('sidebar_admin_setup', 'widget_imgly_setup');
	}

	widget_imgly_register();
}

// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_imgly_init');

?>