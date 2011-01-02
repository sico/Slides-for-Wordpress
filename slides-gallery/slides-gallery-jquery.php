<?php
/*
Plugin Name: Slides Gallery (jQuery)
Version: 1.0
Description: Overrides the builtin Wordpress gallery and replaces it with a javascript slideshow.
Author: Sico
Author URI: http://johnsico.com
Plugin URI: http://johnsico.com
*/


load_plugin_textdomain('slides-gallery', NULL, dirname(plugin_basename(__FILE__)));

add_filter('post_gallery', 'slides_gallery_jquery', 10, 2);
add_action('wp_head', 'slides_gallery_jquery_header');

/*****************************
* Enqueue jQuery & Scripts
*/
function slides_enqueue_scripts() {
	if ( function_exists('plugin_url') )
		$plugin_url = plugin_url();
	else
		$plugin_url = get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));

	// jquery
	wp_deregister_script('jquery');
	wp_register_script('jquery', ($plugin_url  . '/jquery-1.4.4.min.js'), false, '1.4.4');
	wp_enqueue_script('jquery');
	
}
if (!is_admin()) {
	add_action('init', 'slides_enqueue_scripts');
}




function slides_gallery_jquery_header() {
	if ( function_exists('plugin_url') )
		$plugin_url = plugin_url();
	else
		$plugin_url = get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__));
	
	echo '<script type="text/javascript" src="' . $plugin_url . '/slides.jquery.js"></script>' . "\n";

	/**
	* Add styles
	*/
	$output = "
	<style type='text/css'></style>";

	echo "\n".$output."\n";

}

function remove_brs($string) {
	$new_string=urlencode ($string);
	$new_string=ereg_replace("%0D", "{br}", $new_string);
	$new_string=ereg_replace("%0A", "{br}", $new_string);
	$new_string=urldecode  ($new_string);
	return $new_string;
}

function slides_gallery_jquery($output, $attr) {
    
    /* load options */
$sg_preload             = get_option('sg_preload', 'false'); // boolean, Set true to preload images in an image based slideshow		
$sg_container           = get_option('sg_container', 'slides_container'); // string, Class name for slides container. Default is "slides_container"
$sg_generateNetPrev     = get_option('sg_generateNextPrev', 'false'); // boolean, Auto generate next/prev buttons
$sg_next                = get_option('sg_next', 'next'); // string, Class name for next button
$sg_prev                = get_option('sg_prev', 'prev'); // string, Class name for previous button
$sg_pagination          = get_option('sg_pagination', 'true'); // boolean, If you're not using pagination you can set to false, but don't have to
$sg_generatePagination  = get_option('sg_generatePagination', 'true'); // boolean, Auto generate pagination
$sg_paginationClass     = get_option('sg_paginationClass', 'pagination'); // string, Class name for pagination
$sg_fadeSpeed           = get_option('sg_fadeSpeed','350'); // number, Set the speed of the fading animation in milliseconds
$sg_slideSpeed          = get_option('sg_slideSpeed', '350'); // number, Set the speed of the sliding animation in milliseconds
$sg_start               = get_option('sg_start', '1'); // number, Set the speed of the sliding animation in milliseconds
$sg_effect              = get_option('sg_effect', 'slide'); // string, '[next/prev], [pagination]', e.g. 'slide, fade' or simply 'fade' for both
$sg_crossfade           = get_option('sg_crossfade','false'); // boolean, Crossfade images in a image based slideshow
$sg_randomize           = get_option('sg_randomize','false'); // boolean, Set to true to randomize slides
$sg_play                = get_option('sg_play','0'); // number, Autoplay slideshow, a positive number will set to true and be the time between slide animation in milliseconds
$sg_pause               = get_option('sg_pause','0'); // number, Pause slideshow on click of next/prev or pagination. A positive number will set to true and be the time of pause in milliseconds
$sg_hoverPause          = get_option('sg_hoverPause','false'); // boolean, Set to true and hovering over slideshow will pause it
$sg_autoHeight          = get_option('sg_autoHeight','false'); // boolean, Set to true to auto adjust height
$sg_autoHeightSpeed     = get_option('sg_autoHeightSpeed','350'); // number, Set auto height animation time in milliseconds
$sg_bigTarget           = get_option('sg_bigTarget','false'); // boolean, Set to true and the whole slide will link to next slide on click
$sg_thumbSize           = get_option('sg_thumbSize','thumbnail'); 
	/**
	* Grab attachments
	*/
	global $post;
	
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}
	
	extract(shortcode_atts(array(
		'order'      => 'ASC',
		'orderby'    => 'menu_order ID',
		'id'         => $post->ID,
		'itemtag'    => 'dl',
		'icontag'    => 'dt',
		'captiontag' => 'dd',
		'columns'    => 3,
		'size'       => 'thumbnail',
	), $attr));
	
	$id = intval($id);
	$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	
	if ( empty($attachments) )
		return '';
		
	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $id => $attachment )
			$output .= wp_get_attachment_link($id, $size, true) . "\n";
		return $output;
	}
	


	/**
	* Start output
	*/
	$output = '<!-- Begin Slides Gallery -->
	<div id="slides" class="slides-gallery">
        <div class="'.$sg_container.'">
	';

	/**
	* Add images
	*/	
			
	foreach ( $attachments as $id => $attachment ) {
		$image = wp_get_attachment_image_src($id, "large");
        $output .= '<div class="slide"><img src="'.$image[0].'" width="'.$image[1].'" height="'.$image[2].'"/></div>'. PHP_EOL;
		//$js[] = "{url: '" . $image[0] . "', title: '".addslashes($attachment->post_title)."', caption: '".addslashes(remove_brs($attachment->post_excerpt))."', description: '".addslashes(remove_brs($attachment->post_content))."'}";
	}
    
    $output .= '</div><!-- '.$sg_container.'-->'. PHP_EOL;
				
    /**
     * add thumbs for navigation if chosen
     */
	if($sg_generatePagination !== 'false'){
        $output .= '<ul class="'.$sg_paginationClass.'">'. PHP_EOL;
         foreach ( $attachments as $id => $attachment ) {
            $image = wp_get_attachment_image_src($id, "$sg_thumbSize");
            $output .= '<li class="gallery-thumb"><a href="#"><img src="'.$image[0].'" /></a></li>'. PHP_EOL;
         }                

        $output .=	'</ul>';
    }
	/**
	* Initialize
	*/
	$output .= '<script>
        jQuery(function(){
			jQuery(\'#slides\').slides({                                
                preload: '.$sg_preload.', // boolean, Set true to preload images in an image based slideshow		
                container: "'.$sg_container.'", // string, Class name for slides container. Default is "slides_container"
                generateNextPrev: '.$sg_generateNetPrev.', // boolean, Auto generate next/prev buttons
                next: "'.$sg_next.'", // string, Class name for next button
                prev: "'.$sg_prev.'", // string, Class name for previous button                 
                pagination: '.$sg_pagination.', // boolean, If you\'re not using pagination you can set to false, but don\'t have to                
                paginationClass: "'.$sg_paginationClass.'", // string, Class name for pagination
                fadeSpeed: '.$sg_fadeSpeed.', // number, Set the speed of the fading animation in milliseconds
                slideSpeed: '.$sg_slideSpeed.', // number, Set the speed of the sliding animation in milliseconds
                start: '.$sg_start.', // number, Set the speed of the sliding animation in milliseconds
                effect: "'.$sg_effect.'", // string, \'[next/prev], [pagination]\', e.g. \'slide, fade\' or simply \'fade\' for both
                crossfade: '.$sg_crossfade.', // boolean, Crossfade images in a image based slideshow
                randomize: '.$sg_randomize.', // boolean, Set to true to randomize slides
                play: '.$sg_play.', // number, Autoplay slideshow, a positive number will set to true and be the time between slide animation in milliseconds
                pause: '.$sg_pause.', // number, Pause slideshow on click of next/prev or pagination. A positive number will set to true and be the time of pause in milliseconds
                hoverPause: '.$sg_hoverPause.', // boolean, Set to true and hovering over slideshow will pause it
                autoHeight: '.$sg_autoHeight.', // boolean, Set to true to auto adjust height
                autoHeightSpeed: '.$sg_autoHeightSpeed.', // number, Set auto height animation time in milliseconds
                bigTarget: '.$sg_bigTarget.', // boolean, Set to true and the whole slide will link to next slide on click
                    ';
    if($sg_generatePagination !== 'false'){
        $output .= 'generatePagination: false, // boolean, Auto generate pagination
            ';
        
    }else{
        $output .= 'generatePagination: true, // boolean, Auto generate pagination
            ';
    }
     $output .='       });
        });
	
	</script>
	';	
	
	/**
	* End
	*/
	$output .= "	
	<!-- End slides Gallery -->\n
	";

	return $output;

}






/*****************************
* Options Page
*/

// Options
$sg_plugin_name = __("Slides Gallery", 'slides-gallery-jquery');
$sg_plugin_filename = basename(__FILE__); //"slides-gallery-jquery.php";

add_option('sg_preload', 'false', '','yes'); // boolean, Set true to preload images in an image based slideshow		
add_option('sg_container', 'slides_container', '', 'yes'); // string, Class name for slides container. Default is "slides_container"
add_option('sg_generateNextPrev', 'false', '', 'yes'); // boolean, Auto generate next/prev buttons
add_option('sg_next', 'next', '', 'yes'); // string, Class name for next button
add_option('sg_prev', 'prev', '', 'yes'); // string, Class name for previous button
add_option('sg_pagination', 'true', '', 'yes'); // boolean, If you're not using pagination you can set to false, but don't have to
add_option('sg_generatePagination', 'true', '', 'yes'); // boolean, Auto generate pagination
add_option('sg_paginationClass', 'pagination','','yes'); // string, Class name for pagination
add_option('sg_fadeSpeed','350','','yes'); // number, Set the speed of the fading animation in milliseconds
add_option('sg_slideSpeed', '350','','yes'); // number, Set the speed of the sliding animation in milliseconds
add_option('sg_start', '1','','yes'); // number, Set the speed of the sliding animation in milliseconds
add_option('sg_effect', 'slide', '', 'yes'); // string, '[next/prev], [pagination]', e.g. 'slide, fade' or simply 'fade' for both
add_option('sg_crossfade','false','','yes'); // boolean, Crossfade images in a image based slideshow
add_option('sg_randomize','false','','yes'); // boolean, Set to true to randomize slides
add_option('sg_play','0','','yes'); // number, Autoplay slideshow, a positive number will set to true and be the time between slide animation in milliseconds
add_option('sg_pause','0','','yes'); // number, Pause slideshow on click of next/prev or pagination. A positive number will set to true and be the time of pause in milliseconds
add_option('sg_hoverPause','false','','yes'); // boolean, Set to true and hovering over slideshow will pause it
add_option('sg_autoHeight','false','','yes'); // boolean, Set to true to auto adjust height
add_option('sg_autoHeightSpeed','350','','yes'); // number, Set auto height animation time in milliseconds
add_option('sg_bigTarget','false','','yes'); // boolean, Set to true and the whole slide will link to next slide on click
add_option('sg_animationStart','','','yes'); // Function called at the start of animation
add_option('sg_animationComplete','','','yes'); // Function called at the completion of animation
add_option('sg_thumbsize','thumbnail','','yes'); // Size of thumbs to use for pagers

function sg_admin_init() {
	if ( function_exists('register_setting') ) {
		register_setting('sg_settings', 'option-1', '');
	}
}
function add_sg_option_page() {
	global $wpdb;
	global $sg_plugin_name;

	add_options_page($sg_plugin_name . ' ' . __('Options', 'slides-gallery-jquery'), $sg_plugin_name, 8, basename(__FILE__), 'sg_options_page');
	
}
add_action('admin_init', 'sg_admin_init');
add_action('admin_menu', 'add_sg_option_page');

// Options function
function sg_options_page() {

	if (isset($_POST['info_update'])) {
			
		// Update options
		$sg_preload = $_POST["sg_preload"];
		update_option("sg_preload", $sg_preload);
        
        $sg_container = $_POST["sg_container"];
		update_option("sg_container", $sg_container);

        $sg_generateNextPrev = $_POST["sg_generateNextPrev"];
		update_option("sg_generateNextPrev", $sg_generateNextPrev);
        
        $sg_next = $_POST["sg_next"];
		update_option("sg_next", $sg_next);
        
        $sg_prev = $_POST["sg_prev"];
		update_option("sg_prev", $sg_prev);
        
        $sg_pagination = $_POST["sg_pagination"];
		update_option("sg_pagination", $sg_pagination);
        
        $sg_generatePagination = $_POST["sg_generatePagination"];
		update_option("sg_generatePagination", $sg_generatePagination);
        
        $sg_paginationClass = $_POST["sg_paginationClass"];
		update_option("sg_paginationClass", $sg_paginationClass);
        
        $sg_fadeSpeed = $_POST["sg_fadeSpeed"];
		update_option("sg_fadeSpeed", $sg_fadeSpeed);
        
        $sg_slideSpeed = $_POST["sg_slideSpeed"];
		update_option("sg_slideSpeed", $sg_slideSpeed);
        
        $sg_start = $_POST["sg_start"];
		update_option("sg_start", $sg_start);
        
        $sg_effect = $_POST["sg_effect"];
		update_option("sg_effect", $sg_effect);
        
        $sg_crossfade = $_POST["sg_crossfade"];
		update_option("sg_crossfade", $sg_crossfade);
        
        $sg_randomize = $_POST["sg_randomize"];
		update_option("sg_randomize", $sg_randomize);
        
        $sg_play = $_POST["sg_play"];
		update_option("sg_play", $sg_play);
        
        $sg_pause = $_POST["sg_pause"];
		update_option("sg_pause", $sg_pause);
        
        $sg_hoverPause = $_POST["sg_hoverPause"];
		update_option("sg_hoverPause", $sg_hoverPause);
        
        $sg_autoHeight = $_POST["sg_autoHeight"];
		update_option("sg_autoHeight", $sg_autoHeight);
        
        $sg_autoHeightSpeed = $_POST["sg_autoHeightSpeed"];
		update_option("sg_autoHeightSpeed", $sg_autoHeightSpeed);
        
        $sg_bigTarget = $_POST["sg_bigTarget"];
		update_option("sg_bigTarget", $sg_bigTarget);
        
        $sg_animationStart = $_POST["sg_animationStart"];
		update_option("sg_animationStart", $sg_animationStart);
        
        $sg_animationComplete = $_POST["sg_animationComplete"];
		update_option("sg_animationComplete", $sg_animationComplete);
        
        $sg_thumbSize = $_POST["sg_thumbSize"];
		update_option("sg_thumbSize", $sg_thumbSize);
       

		// Give an updated message
		echo "<div class='updated fade'><p><strong>" . __('Options updated', 'slides-gallery-jquery') . "</strong></p></div>";
		
	}

	// Show options page
	?>

		<div class="wrap">
		
			<div class="options">
		
				<form method="post" action="options-general.php?page=<?php global $sg_plugin_filename; echo $sg_plugin_filename; ?>">
			
				<h2><?php global $sg_plugin_name; printf(__('%s Settings', 'slides-gallery-jquery'), $sg_plugin_name); ?></h2>
	                                                          
                    <h3><?php _e("Preload", 'slides-gallery-jquery'); ?></h3>
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_preload' ";
					echo "id='sg_preload_0' ";
					echo "value='true' ";
					echo "true" == get_option('sg_preload') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("Yes.", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_preload' ";
					echo "id='sg_preload_1' ";
					echo "value='false' ";
					echo "false" == get_option('sg_preload') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("No. ", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					
					<p class="setting-description"><?php _e('boolean, Set true to preload images in an image based slideshow', 'slides-gallery-jquery') ?></p>
                
		
					<h3><?php _e("Container Class", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_container" id="sg_container" value="<?php echo get_option('sg_container') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('string, Class name for slides container. Default is "slides_container".', 'slides-gallery-jquery') ?></p>
					

					<h3><?php _e("Generate Next / Previous", 'slides-gallery-jquery'); ?></h3>
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_generateNextPrev' ";
					echo "id='sg_generateNextPrev_0' ";
					echo "value='true' ";
					echo "true" == get_option('sg_generateNextPrev') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("Yes.", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_generateNextPrev' ";
					echo "id='sg_generateNextPrev_1' ";
					echo "value='false' ";
					echo "false" == get_option('sg_generateNextPrev') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("No. ", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					
					<p class="setting-description"><?php _e('boolean, Auto generate next/prev buttons', 'slides-gallery-jquery') ?></p>


                    <h3><?php _e("Next Class", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_next" id="sg_next" value="<?php echo get_option('sg_next') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('string, Class name for next button', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Prev Class", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_prev" id="sg_prev" value="<?php echo get_option('sg_prev') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('string, Class name for prev button', 'slides-gallery-jquery') ?></p>

					<h3><?php _e("Use Pagination", 'slides-gallery-jquery'); ?></h3>
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_pagination' ";
					echo "id='sg_pagination_0' ";
					echo "value='true' ";
					echo "true" == get_option('sg_pagination') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("Yes.", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_pagination' ";
					echo "id='sg_pagination_1' ";
					echo "value='false' ";
					echo "false" == get_option('sg_pagination') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("No. ", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					
					<p class="setting-description"><?php _e('boolean, If you\'re not using pagination you can set to false, but don\'t have to', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Use Thumbs for Pagination?", 'slides-gallery-jquery'); ?></h3>
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_generatePagination' ";
					echo "id='sg_generatePagination_0' ";
					echo "value='true' ";
					echo "true" == get_option('sg_generatePagination') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("Yes.", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_generatePagination' ";
					echo "id='sg_generatePagination_1' ";
					echo "value='false' ";
					echo "false" == get_option('sg_generatePagination') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("No. ", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					
					<p class="setting-description"><?php _e('If true, small thumbs will be used for pagination', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Pagingation Class", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_paginationClass" id="sg_paginationClass" value="<?php echo get_option('sg_paginationClass') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('string, Class name for pagination', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Fade Speed", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_fadeSpeed" id="sg_fadeSpeed" value="<?php echo get_option('sg_fadeSpeed') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('number, Set the speed of the fading animation in milliseconds', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Slide Speed", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_slideSpeed" id="sg_slideSpeed" value="<?php echo get_option('sg_slideSpeed') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('number, Set the speed of the sliding animation in milliseconds', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Starting Slide", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_start" id="sg_start" value="<?php echo get_option('sg_start') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('number, Set which slide you\'d like to start with.', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Effect Type", 'slides-gallery-jquery'); ?></h3>
                    <select name="sg_effect" id="sg_effect">
                        <option value="slide,fade" <?php echo (get_option('sg_effect') == 'slide,fade') ? 'selected="selected"' : '';  ?>>Slide, Fade</option>
                        <option value="fade,slide" <?php echo (get_option('sg_effect') == 'fade,slide') ? 'selected="selected"' : '';  ?>>Fade, Slide</option>
                        <option value="fade" <?php echo (get_option('sg_effect') == 'fade') ? 'selected="selected"' : '';  ?>>Fade</option>
                        <option value="slide" <?php echo (get_option('sg_effect') == 'slide') ? 'selected="selected"' : '';  ?>>Slide</option>
                    </select>
					<br />
					
					<p class="setting-description"><?php _e('Set effect, slide or fade for next/prev and pagination. If you use just one effect name it\'ll be applied to both or you can state two effect names. The first name will be for next/prev and the second will be for pagination.', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Crossfade Images", 'slides-gallery-jquery'); ?></h3>
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_crossfade' ";
					echo "id='sg_crossfade_0' ";
					echo "value='true' ";
					echo "true" == get_option('sg_crossfade') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("Yes.", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_crossfade' ";
					echo "id='sg_crossfade_1' ";
					echo "value='false' ";
					echo "false" == get_option('sg_crossfade') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("No. ", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					
					<p class="setting-description"><?php _e('boolean, Crossfade images in a image based slideshow', 'slides-gallery-jquery') ?></p>

                    <h3><?php _e("Randomize Images", 'slides-gallery-jquery'); ?></h3>
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_randomize' ";
					echo "id='sg_randomize_0' ";
					echo "value='true' ";
					echo "true" == get_option('sg_randomize') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("Yes.", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_randomize' ";
					echo "id='sg_randomize_1' ";
					echo "value='false' ";
					echo "false" == get_option('sg_randomize') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("No. ", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					
					<p class="setting-description"><?php _e('boolean, Set to true to randomize slides', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Autoplay Slideshow", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_play" id="sg_start" value="<?php echo get_option('sg_play') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('number, Autoplay slideshow, a positive number will set to true and be the time between slide animation in milliseconds', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Pause Slideshow", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_pause" id="sg_pause" value="<?php echo get_option('sg_pause') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('number, Pause slideshow on click of next/prev or pagination. A positive number will set to true and be the time of pause in milliseconds', 'slides-gallery-jquery') ?></p>

                    <h3><?php _e("Pause on Hover", 'slides-gallery-jquery'); ?></h3>
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_hoverPause' ";
					echo "id='sg_hoverPause_0' ";
					echo "value='true' ";
					echo "true" == get_option('sg_hoverPause') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("Yes.", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_hoverPause' ";
					echo "id='sg_hoverPause_1' ";
					echo "value='false' ";
					echo "false" == get_option('sg_hoverPause') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("No. ", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					
					<p class="setting-description"><?php _e('boolean, Set to true and hovering over slideshow will pause it', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Auto-Adjust Height", 'slides-gallery-jquery'); ?></h3>
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_autoHeight' ";
					echo "id='sg_autoHeight_0' ";
					echo "value='true' ";
					echo "true" == get_option('sg_autoHeight') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("Yes.", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_autoHeight' ";
					echo "id='sg_autoHeight_1' ";
					echo "value='false' ";
					echo "false" == get_option('sg_autoHeight') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("No. ", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					
					<p class="setting-description"><?php _e('boolean, Set to true to auto adjust height', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("AutoHeight Speed", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_autoHeightSpeed" id="sg_autoHeightSpeed" value="<?php echo get_option('sg_autoHeightSpeed') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('number, Set auto height animation time in milliseconds', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Advance on Click", 'slides-gallery-jquery'); ?></h3>
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_bigTarget' ";
					echo "id='sg_bigTarget_0' ";
					echo "value='true' ";
					echo "true" == get_option('sg_bigTarget') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("Yes.", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					<label>
					<?php
					echo "<input type='radio' ";
					echo "name='sg_bigTarget' ";
					echo "id='sg_bigTarget_1' ";
					echo "value='false' ";
					echo "false" == get_option('sg_bigTarget') ? ' checked="checked"' : "";
					echo " />";
					?>
					<?php _e("No. ", 'slides-gallery-jquery'); ?>
					</label>
					<br />
					
					<p class="setting-description"><?php _e('boolean, Set to true and the whole slide will link to next slide on click', 'slides-gallery-jquery') ?></p>
                    
                    <h3><?php _e("Thumbnail Size", 'slides-gallery-jquery'); ?></h3>
					<input type="text" size="50" name="sg_thumbSize" id="sg_thumbSize" value="<?php echo get_option('sg_thumbSize') ?>" />
					<br />
					
					<p class="setting-description"><?php _e('string - The wordpress thumbnail size you\'d like to use. Possible values include thumbnail, medium, large, or any custom thumbs your theme has added.', 'slides-gallery-jquery') ?></p>
                                       
		
					<p class="submit">
						<?php if ( function_exists('settings_fields') ) settings_fields('sg_settings'); ?>
						<input type='submit' name='info_update' value='<?php _e('Save Changes', 'slides-gallery-jquery'); ?>' />
					</p>
				
				</form>
				
				
			</div><?php //.options ?>
			
		</div>

<?php
}



?>
