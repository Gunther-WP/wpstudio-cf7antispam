<?php
/*
Plugin Name: 		WP-Studio CF7Antispam
Plugin URI: 		https://www.wp-studio.org/plugins/wpstudio-cf7antispam
Description: 		A plugin for CF7, Antispam Contact Form 7
Version: 			1.0.0
Author: 			Gunther Schöbinger
Author URI: 		https://www.wp-studio.org
Text Domain: 		wpstudio-cf7antispam
Domain Path: 		/languages
License: 			GPL3

Requires at least: 	6.0
Requires PHP:      	7.4
Requires Plugins: contact-form-7


*/


/*-----------------------------------

	ADMIN 

-------------------------------------*/

include_once ( plugin_dir_path( __FILE__ ) . 'admin/wpstudio-cf7antispam-admin.php');


/*-----------------------------------

	TAG 

-------------------------------------*/

add_action( 'wpcf7_init', 'custom_add_form_tag_wpsantispam', 10, 0);
 
function custom_add_form_tag_wpsantispam(): void {
	wpcf7_add_form_tag( 'wpsantispam', 'custom_wpsantispam_form_tag_handler', ['name-attr' => true, 'do_not_store' => true] ); 
}
 
function custom_wpsantispam_form_tag_handler( $tag ) {

	$settings 	= get_option('wpsantispam_option_settings');

	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = wpcf7_get_validation_error( $tag->name );

	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
	}

	$atts = array();
	$atts['name'] 	= '_wpcf7_answer_';
	$atts['class']	= ' wpcf7-form-control wpcf7-text wpcf7-answer';

	$answer = generateAnswer();

	$label = strlen($settings['labeltext']) > 0 ? $settings['labeltext'] : __( 'Fill in (anti-spam protection):', 'wpstudio-cf7antispam' );

	$html = '<span class="wpcf7-form-control-wrap" data-name="'.$tag->name.'">
		<label class="wpcf7antispam"><span class="question"><span>'.$label.'&nbsp;</span><span class="wpcf7-'.$tag->name.'-label"><strong>'.antispambot($answer['text']).'</strong></span><span class="wpcf7antispam-input"><input '.wpcf7_format_atts( $atts ).' /></span></label>
		<input type="hidden" name="wpcf7_hp" value="" />
		<input type="hidden" name="wpcf7_answer_hash_'.$tag->name.'" value="wpcf7_'.$tag->name.'_'.$answer['hash'].'" />
		'.$validation_error.'</span>';

	return $html;
}

add_filter( 'wpcf7_validate_wpsantispam', 'wpcf7_wpsantispam_validation_filter', 10, 2 );
function wpcf7_wpsantispam_validation_filter( $result, $tag ) {
	
	$settings 		= get_option('wpsantispam_option_settings');

	$myhash 		= str_replace('wpcf7_'.$tag->name.'_', '', sanitize_text_field($_POST['wpcf7_answer_hash_'.$tag->name])); 
	$response 		= sanitize_text_field($_POST['_wpcf7_answer_']);
	$hp				= sanitize_text_field($_POST['wpcf7_hp']);

	$invalide 	= 0;
	$label 		= array();

	if (strlen($hp) > 0) {
		$invalide = 1;
	}
	if (!wp_check_password($response, base64_decode($myhash))) {
		$invalide = 2;
	}

	$label[1] = __( 'Technical error.', 'wpstudio-cf7antispam' );
	$label[2] = strlen($settings['labelerror']) > 0 ? $settings['labelerror'] : __( 'The characters entered are not correct.', 'wpstudio-cf7antispam' );


	if ($invalide > 0) {
		$result->invalidate( $tag, print_r ($label[$invalide],1));
	}
	
	return $result;
}



add_action('wpcf7_admin_init', 'wpcf7_wpsantispam_add_tag_generator', 99, 0);
function wpcf7_wpsantispam_add_tag_generator(){
	$tag_generator = WPCF7_TagGenerator::get_instance();
	$tag_generator->add( 
		'wpsantispam', 
		__( 'Antispam', 'wpstudio-cf7antispam' ),
		'wpcf7_wpsantispam_pane_confirm', array( 'nameless' => 0, 'version'=>'2') );
}

function wpcf7_wpsantispam_pane_confirm( $contact_form, $options) {
	
	$field_types = array(
		'wpsantispam' => array(
			'display_name' => __( 'Antispam field', 'wpstudio-cf7antispam' ),
			'heading' => __( 'Antispam field form-tag generator', 'wpstudio-cf7antispam' ),
			'description' => __( 'Generate a form-tag for a wpsantispam button.', 'wpstudio-cf7antispam' ),
		)
	);

	$basetype = $options['id'];

	if ( ! in_array( $basetype, array_keys( $field_types ) ) ) {
		$basetype = 'wpsantispam';
	}

	$tgg = new WPCF7_TagGeneratorGenerator( $options['content'] );
	
	$out = '';
	$out .= '<header class="description-box">';
	$out .= '<h3>'.$field_types[$basetype]['heading'].'</h3>';
	$out .= '<p>'.$field_types[$basetype]['description'].'</p>';
	$out .= '</header>';

	$out .= '<div class="control-box">';
	echo $out;

	$tgg->print( 'field_type', array(
		'with_required' => false,
		'select_options' => array(
			$basetype => $field_types[$basetype]['display_name'],
		),
	) );
	$tgg->print( 'field_name', array(
		'ask_if' => $field_types[$basetype]['maybe_purpose']
	) );

	echo '<fieldset>
		<legend id="tag-generator-panel-wpsantispam-class-legend">Class-Attribute</legend>
		<input type="text" data-tag-part="option" data-tag-option="class:" pattern="[A-Za-z0-9_\-\s]*" aria-labelledby="tag-generator-panel-wpsantispam-class-legend">
	</fieldset>';

	/*echo '<fieldset>
		<legend id="tag-generator-panel-wpsantispam-labels">Labels</legend>
		<textarea rows="4" data-tag-part="value" aria-labelledby="tag-generator-panel-wpsantispam-labels">Test</textarea></fieldset>';
	*/

	$out = '</div><footer class="insert-box">';	
	echo $out;

	$tgg->print( 'insert_box_content' );
	$tgg->print( 'mail_tag_tip' );

	echo '</footer>';

}



function generateAnswer (){

	$settings = get_option('wpsantispam_option_settings');

	$answer['text'] = '';
	for($i = 0; $i < $settings['stringlength']; $i++) {
		$answer['text'] .= mb_substr($settings['letters'],rand(0,strlen($settings['letters'])-1),1);
	}
	$answer['text'] = $answer['text'];

	$answer['hash'] = base64_encode(wp_hash_password($answer['text']));

	return $answer;

}


