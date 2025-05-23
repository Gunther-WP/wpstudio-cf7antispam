<?php

class WpsantispamSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'CF7 Antispam', 
            'manage_options', 
            'wpsantispam-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'wpsantispam_option_settings' );
        ?>
        <div class="wrap">
            <h1>CF7 Antispam by WP-Studio</h1>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'wpsantispam_option_group' );
                do_settings_sections( 'wpsantispam-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        

        // set default
        $settings                   = get_option('wpsantispam_option_settings');

        $default['letters']         =  isset($settings['letters'])      ?   $settings['letters'] : 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghikmnpqrstuvwxyz123456789';
        $default['stringlength']    =  intval($settings['stringlength']) > 4 ?  intval($settings['stringlength']) : '5';
        $default['labeltext']       =  isset($settings['labeltext'])    ?   $settings['labeltext'] : __( 'Fill in (anti-spam protection):', 'wpstudio-cf7antispam' );
        $default['labelerror']      =  isset($settings['labelerror'])   ?   $settings['labelerror'] : __( 'The characters entered are not correct.', 'wpstudio-cf7antispam' );

        update_option('wpsantispam_option_settings', $default);

        
        register_setting(
            'wpsantispam_option_group',                 
            'wpsantispam_option_settings',              
            array( $this, 'sanitize' )                
        );

        // section 1 - settings
        add_settings_section(
            'setting_section_1',                       
            'Settings',                                 
            array( $this, 'print_section_info' ),       
            'wpsantispam-setting-admin'                 
        );  

        add_settings_field(
            'stringlength',                             
            'Length of question (min. 5)',                       
            array( $this, 'stringlength_callback' ),    
            'wpsantispam-setting-admin',               
            'setting_section_1'                             
        );

        add_settings_field(
            'letters', 
            'Letters', 
            array( $this, 'letters_callback' ), 
            'wpsantispam-setting-admin', 
            'setting_section_1'
        );
        
        // section 2 - labels
        add_settings_section(
            'setting_section_2',                       
            'Labels',                                 
            array( $this, 'print_section_labels' ),      
            'wpsantispam-setting-admin'                 
        );  

        add_settings_field(
            'stringlength',                             
            'Infotext',                       
            array( $this, 'labeltext_callback' ),    
            'wpsantispam-setting-admin',               
            'setting_section_2'                                  
        );

        add_settings_field(
            'letters', 
            'Error message', 
            array( $this, 'labelerror_callback' ), 
            'wpsantispam-setting-admin', 
            'setting_section_2'
        );

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        
        $new_input['stringlength']  = absint( $input['stringlength'] );
        $new_input['letters']       = sanitize_text_field( $input['letters'] );
        $new_input['labeltext']     = sanitize_text_field( $input['labeltext'] );
        $new_input['labelerror']    = sanitize_text_field( $input['labelerror'] );
        return $new_input;
    }

    /** 
     * Print the settings
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }
    public function stringlength_callback()
    {
        $out = '<input type="text" id="stringlength" size="2" name="wpsantispam_option_settings[stringlength]" value="'.esc_attr( $this->options['stringlength']).'" />';
        echo $out;
    }
    public function letters_callback()
    {
        $out = '<input required minlength="10" type="text" id="letters" size="80" name="wpsantispam_option_settings[letters]" value="'.esc_attr( $this->options['letters']).'" />';
        echo $out;
    }

    /** 
     * Print the labels
     */
    public function print_section_labels()
    {
        print 'Enter your labels below:';
    }

    public function labeltext_callback()
    {
        $out = '<input type="text" size="60" name="wpsantispam_option_settings[labeltext]" value="'.esc_attr( $this->options['labeltext']).'" /><p>Default: <i>'.__( 'Fill in (anti-spam protection):', 'wpstudio-cf7antispam' ).'</i></p>';
        echo $out;
    }

    public function labelerror_callback()
    {
        $out = '<input type="text" size="60" name="wpsantispam_option_settings[labelerror]" value="'.esc_attr( $this->options['labelerror']).'" /><p>Default: <i>'.__( 'The characters entered are not correct.', 'wpstudio-cf7antispam' ).'</i></p>';
        echo $out;
    }
}

if( is_admin() )
    $wpsantispam_settings_page = new WpsantispamSettingsPage();