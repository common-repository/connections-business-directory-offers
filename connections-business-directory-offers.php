<?php
/*
Plugin Name: Connections Business Directory Offers
Plugin URI: https://tinyscreenlabs.com
Description: Manage Special Offers in Connections Business Directory.
Version: 1.1
Author: Tiny Screen Labs
Author URI: https://tinyscreenlabs.com
License: GPLv2+ or later
Text Domain: connections-business-directory-offers
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

include_once 'connections-business-directory-offers-tgm-class.php';
include_once 'uninstall-plugin/uninstall-plugin.php';

add_action( 'plugins_loaded', array( 'connections_business_directory_offers', 'init' ));

if ( ! class_exists('connections_business_directory_offers' ) ) {

    class connections_business_directory_offers{

        private $js_version = '1.0.1';

        public static function init(){
            $class = __CLASS__;
            new $class;
        }

        function __construct(){

            if ( !function_exists('get_plugins') ){
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
            }

            if( ! is_plugin_active( 'connections/connections.php' ) ) {
                return null;
            }

            if (! function_exists('Connections_Directory')) {
                return null;
            }

            if(isset($_REQUEST['tsl_date_format']) && isset($_REQUEST['tab'])){
                if($_REQUEST['tab'] == 'connections_offers') update_option( 'tsl_date_format' , $_REQUEST['tsl_date_format'] );
            }

            add_action( 'wp_enqueue_scripts', array( $this , 'enqueue_scripts' ));
            add_action( 'admin_enqueue_scripts', array( $this , 'enqueue_scripts' ));

            $plugin = plugin_basename(__FILE__);
            add_filter( "plugin_action_links_$plugin", array( $this ,'form_settings_link' ) );
            add_filter( 'plugin_row_meta', array( $this , 'mam_settings_link'), 10, 2 );

            /**  start of new class actions **/

            // Register the metabox and fields.
			add_action( 'cn_metabox' , array( $this , 'registerMetabox' ) );
            add_action( 'cn_meta_field-business_offers', array( $this , 'field' ), 10, 2 );

            if( is_admin() ) add_filter( 'cn_meta_sanitize_field-business_offers', array( $this , 'sanitize') );

            add_action( 'cn_output_meta_field-business_offers', array( $this , 'block' ), 10, 4 );

            add_filter( 'cn_content_blocks', array( $this , 'settingsOption') );

            add_action( 'wp_ajax_tsl_connections_offers_upload_voucher', array( $this , 'tsl_connections_offers_upload_voucher' ));

			add_filter( 'cn_register_settings_tabs', array( $this , 'registerSettingsTabs' ), 50, 1 );
            add_filter( 'cn_register_settings_sections', array( $this , 'registerSettingsSections' ), 50, 1 );
			add_filter( 'cn_register_settings_fields', array( $this , 'registerSettingsFields' ), 50, 1 );

            //create_admin_page

        }

        public  function registerSettingsFields( $fields ) {

            $settings = 'connections_page_connections_settings';

            $fields[] = array(
                'plugin_id'         => 'connections',
                'id'                => 'connections_offers_tab_date',
                'position'          => 10,
                'page_hook'         => $settings,
                'tab'               => 'connections_offers',
                'section'           => 'connections_offers_tab_1',
                'title'             => __('Date Format', 'connections-business-directory-offers' ),
                'desc'              => '',
                'help'              => '',
                'type'              => 'select',
                'options'           => connections_business_directory_offers::date_options(),
                'default'           => 'mm/dd/yy'
            );

            return $fields;

        }

        function date_options(){

            $dates = array('mm/dd/yy' , 'dd/mm/yy' , 'd M, yy' , 'd MM, yy');

            return $dates;
        }

        public static function registerSettingsSections( $sections ) {

            $settings = 'connections_page_connections_settings';

            $sections[] = array(
                'tab'       => 'connections_offers',
                'id'        => 'connections_offers_tab_1',
                'position'  => 20,
                'title'     => __('Connections Offers Settings', 'connections-business-directory-offers'),
                'callback'  => '',
                'page_hook' => $settings
            );

            $sections[] = array(
                'tab'       => 'connections_offers',
                'id'        => 'connections_offers_tab_2',
                'position'  => 40,
                'title'     => __( 'Feedback & Support', 'connections-business-directory-offers' ),
                'callback'  => create_function('', 'echo connections_business_directory_offers::feedback();' ),
                'page_hook' => $settings
            );

            $sections[] = array(
                'tab'       => 'connections_offers',
                'id'        => 'connections_offers_tab_3',
                'position'  => 60,
                'title'     => '',
                'callback'  => create_function('', 'echo connections_business_directory_offers::mobile_app_manager();' ),
                'page_hook' => $settings
            );


            return $sections;
        }

        public function registerSettingsTabs( $tabs ){

            $settings = 'connections_page_connections_settings';

            $tabs[] = array(
                'id'        => 'connections_offers' ,
                'position'  => 60 ,
                'title'     => __( 'Offers' , 'connections-business-directory-offers' ) ,
                'page_hook' => $settings
            );

            return $tabs;
        }

		public static function settingsOption( $blocks ) {

			$blocks['business_offers'] = __( 'Offers', 'connections-business-directory-offers' );

			return $blocks;
		}

        public static function block( $id, $value, $object = NULL, $atts ) {

            echo $value['connections-business-directory-offers-text'] ;
        }

        public static function registerMetabox() {

			$atts = array(
				'id'       => 'business-offers',
				'title'    => __( 'Offers', 'connections-business-directory-offers' ),
				'context'  => 'normal',
				'priority' => 'core',
				'fields'   => array(
					array(
						'id'    => 'business_offers',
						'type'  => 'business_offers',
						),
					),
				);

			cnMetaboxAPI::add( $atts );
		}


        public static function field( $field, $value ) {


            $offer_text = '';
            if(isset($value['connections-business-directory-offers-text'])){
                $offer_text = $value['connections-business-directory-offers-text'];
            }

            ?>

            <div class="connections-business-directory-offers-holder">

			<table name="business_offers" id="business_offers">
                <tr>
                    <td>
                    <?php

                    cnHTML::field(
									array(
										'type'     => 'textarea',
										'class'    => 'connections-business-directory-offers-textarea',
										'id'       => $field['id'] . '[connections-business-directory-offers-text]',
										'required' => false,
										'label'    => '',
										'before'   => '',
										'after'    => '',
										'return'   => false,
									),
                                    $offer_text
								);
                    ?>

                    </td>
                </tr>
                <tr>
                <td><div id="connections-business-directory-offers">&nbsp;</div></td>
                </tr>
                <tr><td>&nbsp;</td></tr>
                <tr>
                <td><input id="connections-business-directory-add-button" onclick='tsl_connections_offers_handler.edit_deal();' type='button' style='margin-top:1em;text-align: center;cursor: pointer;cursor:hand;' class='button button-small' value='<?php echo __('Add Item', 'connections-business-directory-offers'); ?>' ></td>
                </tr>
                <tr><td>&nbsp;</td></tr>

			</table>

            <div style="display: none;" id="tsl_connections_offers_table">&nbsp;</div><div id="tsl_connections_offers_workarea" style="display: none;">&nbsp;</div>

            <p><div id="tsl_connections_offers_save_message" style="display: none;font-weight: bold;color: red;"><?php echo __("Don't forget to update the page to save your changes!", 'connections-business-directory-offers') ?></div></p>
			<p><?php echo __( 'Edit offers to be displayed. Offers become visible on the visible date and are hidden after the end date.', 'connections-business-directory-offers' ) ?></p>
            </div>

            <?php

		}

        public static function sanitize( $value ) {

			if ( empty( $value ) ) return $value;

            $value[ 'connections-business-directory-offers-text' ]  = cnSanitize::string( 'html', $value[ 'connections-business-directory-offers-text' ] );

			return $value;
		}

        public static function print_debug($message){
                echo '<div style="margin-left:15em;">';
                echo '<pre>';
                print_r($message);
                echo '</pre>';
                echo '</div>';
        }

        function mam_settings_link( $links , $file) {

            $plugin = plugin_basename(__FILE__);
            if ( $file == $plugin ) {
                return array_merge( $links, array( '<a href="https://tinyscreenlabs.com/?tslref=connections" target="_blank">' . __('Mobile App Manager', 'connections-business-directory-offers') . '</a>' ) );
            }

            return $links;
        }

        function form_settings_link($links) {
            $mylinks = array( '<a href="' . admin_url( 'admin.php?page=connections_settings_offers' ) . '">'.__('Settings', 'connections-business-directory-offers').'</a>', );
            return array_merge( $mylinks , $links );
        }

        public static function feedback(){

            $html_line = '<div class="tsl_section" style="max-width:65em;">';
            $html_line .= '<p>If you need support, want to provide some feedback or have an idea for a new feature for Connections Business Directory Offers, drop us an email at <a href="mailto:info@tinyscreenlabs.com">info@tinyscreenlabs.com</a></p>';
            $html_line .= '</div>';
            return $html_line;

        }

        public static function mobile_app_manager(){

            $html_line = '';

            if(  is_plugin_active( 'wp-local-app/wp-local-app.php' ) ) {

                $html_line .= '<br><div class="tsl_section" style="max-width:65em;"><h2>' . __('Mobile App Manager (Premium)', 'connections-business-directory-offers') . '</h2>';
                $html_line .= '<p><span>' . __('TSL Mobile App Manager is a WordPress plugin and cloud based service that enables WordPress Admins to design a mobile app and complete the submission process right inside the WordPress dashboard.', 'connections-business-directory-offers') . '</span></p>';
                $html_line .= '<ul style="list-style-type:disc;margin-left:2em;">';
                $html_line .= '<li>' . __('WordPress administrators have the ability to manage content for their website and mobile apps in one place<', 'connections-business-directory-offers') . '/li>';
                $html_line .= '<li>' . __('Connections Business Directory Offers are displayed on your mobile app', 'connections-business-directory-offers') . '</li>';
                $html_line .= '<li>' . __('App Setup is a drag and drop interface where you design your mobile app before you purchase a TSL Pro Plan', 'connections-business-directory-offers') . '</li>';
                $html_line .= '<li>' . __('The TSL Local App Previewer is a WYSIWYG viewer that connects to your website', 'connections-business-directory-offers') . '</li>';
                $html_line .= '<li>' . __('Updates to app page content are automatically pushed to the mobile app whenever you update pages and posts in WordPress', 'connections-business-directory-offers') . '</li>';
                $html_line .= '<li>' . __('TSL publishes your app to iTunes and Google Play when you purchase the TSL Pro Plan', 'connections-business-directory-offers') . '</li>';
                $html_line .= '</ul>';

                $html_line .= '<p><span>' . __('For more information go to the ', 'connections-business-directory-offers') . '<a href="https://tinyscreenlabs.com/?tslref=connections" target="_blank">' . __('Tiny Screen Labs', 'connections-business-directory-offers') . '</a> (TSL) '.__('website or click here to', 'connections-business-directory-offers').' <a href="?page=tgmpa-install-plugins&plugin_status=install">'.__('install', 'connections-business-directory-offers').'</a> '.__('the Mobile App Manager plugin', 'connections-business-directory-offers').'.</span></p>';

                $html_line .= '</div>';
            }

            return $html_line;
        }

        function enqueue_scripts($hook = null ){

            if( is_admin() && ($hook != 'connections_page_connections_add' && $hook != 'connections_page_connections_manage' )) return;

            wp_register_script('connections-business-directory-offers-scripts', plugins_url( 'js/connections-business-directory-offers-scripts.js', __FILE__ ), array(), $this->js_version, true);
            wp_register_script('moment', plugins_url( 'js/moment.js', __FILE__ ), array(), $this->js_version, true);
            wp_register_script('qtip',   plugins_url( 'js/jquery.qtip.min.js', __FILE__ ), array(), $this->js_version, true);

            if(get_option( 'tsl_date_format' )){
                $date_format = get_option( 'tsl_date_format' );
            }

            if(!isset($date_format)) $date_format = 'mm/dd/yy';

            wp_localize_script( 'connections-business-directory-offers-scripts', 'tsl_manager', $date_format);
            wp_localize_script( 'connections-business-directory-offers-scripts', 'tsl_ajax_url', admin_url( 'admin-ajax.php' ) );
            wp_localize_script( 'connections-business-directory-offers-scripts', 'tsl_connections_lng', $this->language());

            wp_enqueue_script(array(  'jquery' , 'jquery-ui-datepicker' , 'jquery-ui-dialog' , 'connections-business-directory-offers-scripts' , 'moment' , 'qtip' ));

            wp_register_style('qtip', plugins_url( "css/jquery.qtip.min.css", __FILE__ ), array(), $this->js_version, 'screen');
            wp_register_style('qtip-settings', plugins_url( "css/jquery.qtip.css", __FILE__ ), array(), $this->js_version, 'screen');
            wp_register_style('fontawesome', plugins_url( "css/font-awesome.min.css", __FILE__ ), array(), $this->js_version, 'screen');
            wp_register_style('connections-business-directory-offers', plugins_url( "css/connections-business-directory-offers.css", __FILE__ ), array(), $this->js_version, 'screen');

            wp_enqueue_style(array( 'fontawesome' , 'qtip', 'qtip-settings' , 'connections-business-directory-offers' ));

        }

        function language(){

            $lng['save_alert'] = __("Don't forget to update the page to save your changes!", 'connections-business-directory-offers');
            $lng['title'] = __('Title', 'connections-business-directory-offers');
            $lng['visible_date'] = __('Visible Date', 'connections-business-directory-offers');
            $lng['start_date'] = __('Start Date', 'connections-business-directory-offers');
            $lng['end_date'] = __('End Date', 'connections-business-directory-offers');
            $lng['always'] = __('Always', 'connections-business-directory-offers');
            $lng['edit_item'] = __('Edit Item', 'connections-business-directory-offers');
            $lng['add_item'] = __('Add Item', 'connections-business-directory-offers');
            $lng['close'] = __('Close', 'connections-business-directory-offers');
            $lng['delete_item'] = __('Delete Item', 'connections-business-directory-offers');
            $lng['offer_title'] = __('Offer Title', 'connections-business-directory-offers');
            $lng['visible_always'] = __('Make this offer visible all the time', 'connections-business-directory-offers');
            $lng['offer_description'] = __('Offer Description', 'connections-business-directory-offers');
            $lng['cancel'] = __('Cancel', 'connections-business-directory-offers');
            $lng['select_file'] = __('Select File', 'connections-business-directory-offers');
            $lng['voucher_image'] = __('Voucher Image', 'connections-business-directory-offers');
            $lng['upload_instructions'] = __('Upload an image and then use the short code [VOUCHER Coupon] in the offer text above to insert a link to the image. You can change the word Coupon to anything you want for the link name.', 'connections-business-directory-offers');
            $lng['manage_special_offers'] = __('Manage Special Offers', 'connections-business-directory-offers');
            $lng['edit_offers'] = __('Edit Offers', 'connections-business-directory-offers');
            $lng['save_item'] = __('Save Item', 'connections-business-directory-offers');
            $lng['save'] = __('Save', 'connections-business-directory-offers');
            $lng['starts'] = __('Starts', 'connections-business-directory-offers');
            $lng['ends'] = __('Ends', 'connections-business-directory-offers');

            $parts = explode('_',get_locale());

            $lng['locale'] = $parts[0] ;

            return $lng;
        }

        function tsl_connections_offers_upload_voucher(){

            $condition_img = 7;
            $img_count = 0;
            if(isset($_POST["image_gallery"])) $img_count = count(explode(',', $_POST["image_gallery"]));

            if (!empty($_FILES["tsl_voucher_file_upload"])) {

                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');


                $files = $_FILES["tsl_voucher_file_upload"];
                $attachment_ids = array();

                if ($img_count >= 1) {
                    $imgcount = $img_count;
                } else {
                    $imgcount = 1;
                }

                $ul_con = '';

                foreach ($files['name'] as $key => $value) {
                    if ($files['name'][$key]) {
                        $file = array(
                            'name' => $files['name'][$key],
                            'type' => $files['type'][$key],
                            'tmp_name' => $files['tmp_name'][$key],
                            'error' => $files['error'][$key],
                            'size' => $files['size'][$key]
                        );
                        $_FILES = array("tsl_voucher_file_upload" => $file);


                        foreach ($_FILES as $file => $array) {

                            if ($imgcount >= $condition_img) {
                                break;
                            }

                            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
                            require_once(ABSPATH . "wp-admin" . '/includes/media.php');

                            $attach_id = media_handle_upload($file, 0 );
                            $attachment_ids[] = $attach_id;

                            $image_link = wp_get_attachment_image_src($attach_id, 'thumbnail');
                            $image_link_large = wp_get_attachment_image_src($attach_id, 'full');

                            //Correct protocol for https connections
                            list($protocol, $uri) = explode('://', $image_link[0], 2);

                            if(is_ssl()) {
                                if('http' == $protocol) {
                                  $protocol = 'https';
                                }
                                } else {
                                if('https' == $protocol) {
                                  $protocol = 'http';
                                }
                            }

                            $image_link[0] = $protocol.'://'.$uri;

                            list($protocol, $uri) = explode('://', $image_link_large[0], 2);

                            if(is_ssl()) {
                                if('http' == $protocol) {
                                  $protocol = 'https';
                                }
                                } else {
                                if('https' == $protocol) {
                                  $protocol = 'http';
                                }
                            }

                            $image_link_large[0] = $protocol.'://'.$uri;

                            $ul_con = '<div class="plupload-thumbs" style="clear:inherit; margin-top:0; margin-left:5em; padding-top:10px; float:left; width:50%;">
                                            <div class="thumb">
                                                <div class="thumbi">
                                                <a onclick="tsl_connections_offers_handler.remove_voucher()" href="javascript:;" class="delete check">Remove</a>
                                               </div>
                                               <img style="margin-top:.5em" src="' . $image_link[0] . '" >
                                           </div>
                                        </div>
                                        <div id="tsl_thumbnail" style="display:none;">'.$image_link[0].'</div>
                                        <div id="tsl_image" style="display:none;">'.$image_link_large[0].'</div>';

                        }
                        if ($imgcount > $condition_img) {
                            break;
                        }
                        $imgcount++;
                    }
                }


            }
            /*img upload */

            if(isset($_POST["image_gallery"])) $image_gallery = $_POST['image_gallery'];

            $attachment_idss = array_filter($attachment_ids);
            $attachment_idss = implode(',', $attachment_idss);

            if (isset($image_gallery)) {
                $attachment_idss = $image_gallery . "," . $attachment_idss;
            }

            $arr = array();
            $arr['attachment_idss'] = $attachment_idss;
            $arr['ul_con'] = $ul_con;

            echo json_encode($arr);
            die();

        }


    }
}
?>