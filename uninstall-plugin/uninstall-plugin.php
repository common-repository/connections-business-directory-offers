<?php

global $tsl_uninstall_js_version;
$this_js_version = 1;
if( $tsl_uninstall_js_version < $this_js_version ){
    $tsl_uninstall_js_version = $this_js_version;
}

global $tsl_uninstall_plugins;

$uninstall_message = 'Please give us your feedback to help us improve our plugin.';

$questions = array();

$questions[] = array( 'q_id' => 'Q1', 'question' => 'Reasons for uninstalling - select all that apply:', 'type' => 'checkboxes' , 'answers' => array('Just updating the Plugin', 'Alternative Found' , 'No Longer Needed' , 'Stopped Working' , 'Complicated' , 'Translation Issue'));
$questions[] = array( 'q_id' => 'Q2', 'question' => 'Please tell us more about why you are uninstalling:', 'type' => 'textarea' );

$tsl_uninstall_plugins[] = array( 'plugin_slug' => 'connections-business-directory-offers' , 'message' => $uninstall_message , 'plugin_notifier_url' => 'https://tinyscreenlabs.com/wp-admin/admin-ajax.php' , 'plugin_screen_title' => 'Uninstall Connections Business Directory Offers' , 'questions' => $questions);

add_action('admin_init', 'tsl_uninstall_plugin_init' );

if(!function_exists('tsl_uninstall_plugin_init')) {

    function tsl_uninstall_plugin_init()
    {
        global $tsl_uninstall_manager;

        if (!$tsl_uninstall_manager) {

            $tsl_uninstall_manager = new tsl_plugin_uninstall();

        }
    }
}

if ( ! class_exists('tsl_plugin_uninstall' ) ) {

    class tsl_plugin_uninstall {

        function __construct(){

            add_action( 'admin_enqueue_scripts', array( $this ,'tsl_uninstall_scripts' ) , 50);
            add_action( 'wp_ajax_tsl_uninstall_send_feedback', array( $this ,'tsl_uninstall_send_feedback' ));
            add_action( 'wp_ajax_nopriv_tsl_uninstall_send_feedback', array( $this ,'tsl_uninstall_send_feedback' ));

        }

        function tsl_uninstall_send_feedback(){

            $querystring = $_REQUEST['feedback_url'] .'?action=tsl_uninstall_get_nonce';
            $response = wp_remote_get($querystring);

            if (is_array($response) && !is_wp_error($response)) {

                global $tsl_uninstall_plugins;

                $feedback = '';

                for($x=0;$x<sizeof($tsl_uninstall_plugins);$x++){
                    if($tsl_uninstall_plugins[$x]['plugin_slug'] == $_REQUEST['plugin_slug']) {
                        for($y=0;$y<sizeof($tsl_uninstall_plugins[$x]['questions']);$y++) {
                            $feedback .= $tsl_uninstall_plugins[$x]['questions'][$y]['question'];
                            $feedback .= $_REQUEST['feedback'][$y];
                        }
                    }
                }

                $api_response = json_decode( wp_remote_retrieve_body( $response ), true );
                $url = str_replace('wp-admin/admin-ajax.php','', admin_url('admin-ajax.php'));
                $querystring = $_REQUEST['feedback_url'] . '?action=tsl_uninstall_send_feedback&_wpnonce='.$api_response['nonce'].'&url=' . $url .'&plugin='.$_REQUEST['plugin_slug'] .'&feedback='.$feedback;
                $response = wp_remote_get($querystring);

                if (is_array($response) && !is_wp_error($response)) {
                    $update_status['status'] = 'done';
                } else {
                    $update_status['status'] = 'failed';
                }
            }else{
                $update_status['status'] = 'failed';
            }

            wp_send_json( $update_status );
            die();

        }

        function tsl_uninstall_scripts(){

            global $tsl_uninstall_plugins;
            global $tsl_uninstall_js_version;

            wp_register_script('uninstall-plugin', plugins_url('uninstall-plugin.js', __FILE__), array(), $tsl_uninstall_js_version, true);

            wp_localize_script('uninstall-plugin', 'tsl_uninstall_ajax_url', admin_url('admin-ajax.php'));
            wp_localize_script('uninstall-plugin', 'tsl_uninstall_plugins', $tsl_uninstall_plugins);

            wp_enqueue_script(array('jquery', 'jquery-ui-dialog', 'uninstall-plugin'));

            wp_enqueue_style(array( 'wp-jquery-ui-dialog' ));

        }
    }
}
?>