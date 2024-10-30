/**
 * Created by Tiny Screen Labs, LLC on 02/20/17.
 */

if (typeof tsl_uninstall_plugin !== 'function') {

    var tsl_uninstall_plugin = function () {

        var dialog;

        this.uninstall = function () {

            jQuery('.deactivate').click(function (e) {
                e.preventDefault;

                var href = jQuery(this).find('a:first').attr('href');
                var plugin_notifier_url;
                var process_plugin = false;
                var screen_title = '';
                var plugin_slug;
                var message;

                var html_line = '';
                var questions = null;

                if( ! ajaxurl && tsl_uninstall_ajax_url ){
                    var ajaxurl = tsl_uninstall_ajax_url;
                }else{
                    location.href = href;
                    return true;
                }

                jQuery(tsl_uninstall_plugins).each(function(index , value){

                    if (href.indexOf('plugin=' + value['plugin_slug']) > -1) {
                        plugin_notifier_url = value['plugin_notifier_url'];
                        screen_title = value['plugin_screen_title'];
                        plugin_slug = value['plugin_slug'];
                        questions = value['questions'];
                        message = value['message'];
                        process_plugin = true;
                    }

                });

                if ( process_plugin ) {

                    if (!jQuery('#tsl_uninstall_dialog').length) {
                        jQuery('body').append('<div id="tsl_uninstall_dialog" title="'+screen_title+'"><div id="tsl_uninstall_message" ></div></div>');
                    }else{
                        jQuery('#tsl_uninstall_dialog').attr('title', screen_title );
                    }

                    if(message) html_line = html_line.concat('<h2 style="text-align: center;">'+ message + '</h2>');

                    jQuery(questions).each(function(index , value){

                        if(value['type'] == 'checkboxes') {
                            html_line = html_line.concat(tsl_uninstall_plugin.create_checkboxes(value));
                        }
                        if(value['type'] == 'textarea') {
                            html_line = html_line.concat(tsl_uninstall_plugin.create_textarea(value));
                        }

                    });

                    var buttons = {
                        "Save": {
                            text: 'Send Feedback and Deactivate',
                            click: function () {
                                //send feedback then contnue
                                jQuery('#tsl_uninstall_dialog').css('cursor','progress');
                                jQuery('.ui-button').css('cursor','progress').attr('disabled', true);

                                var response_data = [];

                                jQuery(questions).each(function(index , value){
                                    if(value['type'] == 'checkboxes') {
                                        response_data.push(tsl_uninstall_plugin.get_checkboxes(value));
                                    }
                                    if(value['type'] == 'textarea') {
                                        response_data.push(tsl_uninstall_plugin.get_textarea(value));
                                    }
                                });

                                var data = {
                                    'action': 'tsl_uninstall_send_feedback',
                                    'feedback_url': plugin_notifier_url,
                                    'plugin_slug': plugin_slug,
                                    'feedback': response_data
                                };

                                jQuery.post(ajaxurl, data, function (result) {
                                    dialog.dialog("close");
                                    location.href = href;
                                    return true;
                                })
                            }
                        },
                        "Deactivate": {
                            text: 'Deactivate',
                            click: function () {
                                jQuery('#tsl_uninstall_dialog').css('cursor','progress');
                                jQuery('.ui-button').css('cursor','progress').attr('disabled', true);
                                dialog.dialog("close");
                                location.href = href;
                                return true;
                            }
                        },
                        "Cancel": {
                            text: 'Cancel',
                            click: function () {
                                dialog.dialog("close");
                                return true;
                            }
                        }
                    };

                    dialog = jQuery("#tsl_uninstall_dialog").dialog({
                        closeOnEscape: true,
                        height: 600,
                        width: 700,
                        modal: true,
                        open: function (event, ui) {

                            jQuery(".ui-dialog-titlebar-close").hide();

                            jQuery('#tsl_uninstall_message').html(html_line);

                        },
                        buttons: buttons
                    })

                    return false;
                } else {
                    //not a plugin we are watching - continue to uninstall
                    return true;
                }
            });
        }

        this.create_checkboxes = function(question){

            var html_line = '<br><h4>'+question['question']+'</h4>';
            html_line = html_line.concat('<table id="tsl-uninstall-qid-'+question['q_id']+'" data-id="'+question['q_id']+'">');

            jQuery(question['answers']).each(function(index , value){
                html_line = html_line.concat('<tr><td>'+value+'</td><td><input type="checkbox" name="question_group_'+question['q_id']+'[]" data-question="'+index+'" value="'+index+'"></td></tr>');
            });


            html_line = html_line.concat('</table>');

            return html_line;
        };

        this.get_checkboxes = function(question){

            var html_line = '';
            jQuery.each(jQuery('input[name="question_group_'+question['q_id']+'[]"]:checked'), function() {
                var this_question = jQuery(this).data('question');
                jQuery(question['answers']).each(function(index , value) {
                    if(this_question == index){
                        html_line = html_line.concat(value + ', ');
                    }
                });
            });
            return html_line;
        };

        this.create_textarea = function(question){
            var html_line = '<br><h4>'+question['question']+'</h4>';

            html_line = html_line.concat('<textarea rows="6" style="width:100%;" id="tsl-uninstall-qid-'+question['q_id']+'" data-id="'+question['q_id']+'"></textarea>')

            return html_line;
        };

        this.get_textarea = function(question){

            return jQuery('#tsl-uninstall-qid-'+question['q_id']).val();

        };
    }
}

jQuery(document).ready(function(){
    try {
        tsl_uninstall_plugin = new tsl_uninstall_plugin;
        tsl_uninstall_plugin.uninstall();
    }catch(err){
        console.log(err.message);
    }
});