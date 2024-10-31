<?php

/*
Plugin Name: Really Simple Affiliate
Description: Allows you create an easy affiliate signup page on your website, which people can use to get an affiliate code to share your product or service with others. Using that affiliate code and link, you will see whenever that person brings in a referral from the dashboard build right into Wordpress.
Version: 2.1.1
Author: Maplespace Inc.
Author URI: https://choosemaple.space
License: GPLv2 or later
Text Domain: really-simple-affiliate-program
*/


/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2022 Maplespace, Inc.
*/


// Functions for DB Creation and Setup

global $rsap_db_version;
$rsap_db_version = '1.0';

function rsap_install() {
	global $wpdb;
	global $rsap_db_version;

	$table_name = $wpdb->prefix . 'rsap_affiliates';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        firstname varchar(30) NOT NULL,
        lastname varchar(30) NOT NULL,
        email varchar(50) NOT NULL,
        code int(8) NOT NULL,
        successful_referrals int(8) NOT NULL DEFAULT '0',
        dollars_of_referrals decimal(8) NOT NULL DEFAULT '0.00',
		PRIMARY KEY  (id)
    ) $charset_collate;";
    
    

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );


    $table_name_log = $wpdb->prefix . 'rsap_referral_log';
	$charset_collate = $wpdb->get_charset_collate();

    $sqlLogTable = "CREATE TABLE $table_name_log (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        code int(8) NOT NULL,
        logged timestamp default now(),
        source_ip varchar(20) NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

    dbDelta( $sqlLogTable );

	add_option( 'rsap_db_version', $rsap_db_version );
}


function rsap_install_data() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'rsap_affiliates';
    
    $checkIfTableIsEmpty = $wpdb->get_results("SELECT * from $table_name");
    if(count($checkIfTableIsEmpty) == 0)
    {
        // Table is empty
        $demo_first_name = 'John';
        $demo_last_name = 'Smith';
        $demo_email = 'john_smith@example.com';
        $demo_initial_code = '4050';
        
        
        
        $wpdb->insert( 
            $table_name, 
            array( 
                'firstname' => $demo_first_name, 
                'lastname' => $demo_last_name, 
                'email' => $demo_email, 
                'code' => $demo_initial_code
            ) 
        );
        
        // Log table
        $demo_log_code = '4050';
        $demo_log_ip = '8.8.8.8';
        
        $table_name_log = $wpdb->prefix . 'rsap_referral_log';
        
        $wpdb->insert( 
            $table_name_log, 
            array( 
                'code' => $demo_log_code,
                'source_ip' => $demo_log_ip
            ) 
        );
    }
    else
    {
        // Table is not empty

        // No need to put initial data in

    }
}

// Run the install function when the plugin is activated
register_activation_hook( __FILE__, 'rsap_install' );

// Initial data that can be inserted on activation
register_activation_hook( __FILE__, 'rsap_install_data' );



// Register Settings For a Plugin so they are grouped together
function rsap_register_settings() {
    add_option( 'rsap_option_main_url', 'http://example.com/affiliate-form');
    register_setting( 'rsap_options_group', 'rsap_option_main_url', 'rsap_callback' );

    add_option( 'rsap_option_conversion_form_wrapper_id', 'contact-form');
    register_setting( 'rsap_options_group', 'rsap_option_conversion_form_wrapper_id', 'rsap_callback' );

    add_option( 'rsap_option_enable_ip_restriction', false);
    register_setting( 'rsap_options_group', 'rsap_option_enable_ip_restriction', 'rsap_callback' );

    add_option( 'rsap_option_days_before_new_submission_allowed', '7');
    register_setting( 'rsap_options_group', 'rsap_option_days_before_new_submission_allowed', 'rsap_callback' );

    add_option( 'rsap_option_social_blurb_email', '');
    register_setting( 'rsap_options_group', 'rsap_option_social_blurb_email', 'rsap_callback' );

    add_option( 'rsap_option_social_blurb_facebook', '');
    register_setting( 'rsap_options_group', 'rsap_option_social_blurb_facebook', 'rsap_callback' );

    add_option( 'rsap_option_social_blurb_instagram', '');
    register_setting( 'rsap_options_group', 'rsap_option_social_blurb_instagram', 'rsap_callback' );

    add_option( 'rsap_option_social_image');
    register_setting( 'rsap_options_group', 'rsap_option_social_image', 'rsap_callback' );
    
 }
 add_action( 'admin_init', 'rsap_register_settings' );

// Create an options page
function rsap_register_options_page() {
    add_options_page('RSAP Settings', 'Affiliate Program', 'manage_options', 'really-simple-affiliate-program', 'rsap_options_page');
  }
  add_action('admin_menu', 'rsap_register_options_page');

// Display Settings on Option’s Page
function rsap_options_page() {

if(get_option('rsap_option_enable_ip_restriction') == true) {
    $checked = 'checked=checked';
} else {
    $checked = '';
}

?>
  <div>
  <h2>RSAP Setup Instructions</h2>
  <ol>
      <li>To create the affiliate signup form, add this shortcode to any page you like: <code>[rsap_form]</code></li>
      <li>Add the URL of the website page that holds your conversion form page in the field below (this would be a 'order now' or 'request a quote' form page).</li>
      <li>Inspect your conversion form page to find the 'ID' that wraps around it, and enter that into the field below. Consult your web developer if you need assistance with this.</li>
      <li>If you would like to prevent people from submitting a lot of fake referrals from the same house/IP address, enable 'IP Restriction' below. You can set the number of days you want someone at one house/IP address to wait before another submission will be counted as valid (good number is probably 7 or 30 days).</li>
    </ol>
 
 <h2>For Outputting the Affiliate Code in a Form Submission Email (CF7) or on a Website Page</h2>
 <ul>
 <li>This shortcode can be used to display the affiliate code on the 'conversion form page', or added to a form submission email (like in Contact Form 7): <code>[rsap_referrer_code]</code>. </li>
 </ul>
 <h2>For Outputting the Affiliate Code in a Gravity Forms Form</h2>
 <ol>
 <li>In the Gravity Forms form editor, add a 'Hidden' input field.</li>
 <li>Open that new field, and title it 'Affiliate Code'.</li>
 <li>Under the 'Advanced' tab of that new field, set the default value to "RSAP" without the quotes.</li>
 <li>Click 'Update' on the form to save your changes.</li>
 <li>Now when the form is submitted within 30 days after an affiliate linked was clicked leading to your site, you will see that code in the email you receive. You can test this by adding <code>?rfr=4050</code> to the end of the form page's URL.</li>
 <li>Note that you may need to adjust your email template so that this new field is included when it is sent to you for tracking purposes.</li>
 </ol>
  <h2>RSAP Settings</h2>
  <form method="post" action="options.php">
  <?php settings_fields( 'rsap_options_group' ); ?>
  <table width=650px>
  <tr valign="top">
  <th scope="row"><label for="rsap_option_main_url">Main Referral URL:</label></th>
  <td width=350px><input type="text" id="rsap_option_main_url" name="rsap_option_main_url" value="<?php echo get_option('rsap_option_main_url'); ?>" style="width:100%;" /></td>
  </tr>
  <tr>
  <th scope="row"><label for="rsap_option_conversion_form_wrapper_id">Conversion Form Wrapper ID:</label></th>
  <td width=350px><input type="text" id="rsap_option_conversion_form_wrapper_id" name="rsap_option_conversion_form_wrapper_id" value="<?php echo get_option('rsap_option_conversion_form_wrapper_id'); ?>" style="width:100%;" /></td>
  </tr>
  <tr>
  <th scope="row"><label for="rsap_option_conversion_form_wrapper_id">Enable IP Block to Reduce Fake Submissions:</label></th>
  <td width=350px><input type="checkbox" id="rsap_option_enable_ip_restriction" name="rsap_option_enable_ip_restriction" <?php echo $checked?> /></td>
  </tr>
  <tr valign="bottom">
  <th scope="row"><label for="rsap_option_days_before_new_submission_allowed">Days before new submission from same IP allowed:</label></th>
  <td width=350px><input type="text" id="rsap_option_days_before_new_submission_allowed" name="rsap_option_days_before_new_submission_allowed" value="<?php echo get_option('rsap_option_days_before_new_submission_allowed'); ?>" style="width:100%;" /></td>
  </tr>
  </table>
  <h2>Social Blurbs for New Affiliates</h2>
  <table width=650px>
  <tr>
  <th scope="row"><label for="rsap_option_social_blurb_email">Social Blurb for Email:</label></th>
  <td width=350px><textarea id="rsap_option_social_blurb_email" name="rsap_option_social_blurb_email" rows="7" placeholder="This is the text that an affiliate can copy/paste into the emails they send to share about your campaign. Note that the affiliate link will automatically be added to the end of this paragraph to make sharing easier." style="width:100%;" /><?php echo get_option('rsap_option_social_blurb_email'); ?></textarea></td>
  </tr>

  <tr>
  <th scope="row"><label for="rsap_option_social_blurb_facebook">Social Blurb for Facebook:</label></th>
  <td width=350px><textarea id="rsap_option_social_blurb_facebook" name="rsap_option_social_blurb_facebook" rows="7" placeholder="This is the text that an affiliate can copy/paste into the Facebook post they send to share about your campaign. Note that the affiliate link will automatically be added to the end of this paragraph to make sharing easier." style="width:100%;" /><?php echo get_option('rsap_option_social_blurb_facebook'); ?></textarea></td>
  </tr>

  <tr>
  <th scope="row"><label for="rsap_option_social_blurb_instagram">Social Blurb for Instagram:</label></th>
  <td width=350px><textarea id="rsap_option_social_blurb_instagram" name="rsap_option_social_blurb_instagram" rows="7" placeholder="This is the text that an affiliate can copy/paste into the Instagram post they send to share about your campaign. NOTE: the affiliate link will need to be added to the user's Instagram Bio 'website' field for it to be clickable. This is noted in the email the new affiliate receives." style="width:100%;" /><?php echo get_option('rsap_option_social_blurb_instagram'); ?></textarea></td>
  </tr>

  <tr>
  <th scope="row"><label for="rsap_option_social_image">Social Media Image (Recommended 1080x1080):</label></th>
  <td width=350px><?php rsap_arthur_image_uploader('rsap_option_social_image', $width = 115, $height = 115)?></td>
 
  </tr>

  </table>
  <?php  submit_button(); ?>
  </form>

  <h3>Affiliate Member Stats</h3>
  <p>Below you can find the list of all people who have signed up for your affiliate program, as well as the number of successful referrals they have brought in. In addition, each person's affiliate code and affiliate link can be found here.</p>
  <table>
  <tr>
  <th>First_Name&nbsp;&nbsp;</th>
  <th>Last_Name&nbsp;&nbsp;</th>
  <th>Email&nbsp;&nbsp;</th>
  <th>Code&nbsp;&nbsp;</th>
  <th>Successful_Referrals</th>
  <th>Affiliate_Link</th>
  </tr>
  <?php
  global $wpdb;
  $referralUrlBase = get_option('rsap_option_main_url') . '?rfr=';
  $table_name = $wpdb->prefix . 'rsap_affiliates';
	
      $results = $wpdb->get_results( "SELECT * FROM $table_name order by id DESC"  );
        foreach($results as $res) { 
            echo '<tr>';

        echo '<td>' . $res->firstname . '</td>';
        echo '<td>' . $res->lastname . '</td>';
        echo '<td>' . $res->email . '</td>';
        echo '<td>' . $res->code . '</td>';
        echo '<td>' . $res->successful_referrals . '</td>';
        echo '<td><a target="_blank" href="' . $referralUrlBase . $res->code .'">Link</a></td>';
        echo '</tr>';
     }
     ?>
     </table>
  </div> <!-- end wrapper div -->
<?php
} 

// Create API Endpoint to receive new user creations from the form

function rsap_add_affiliate( WP_REST_Request $request ) {

    $parameters = $request->get_json_params();

    $firstname = (string)$parameters[0]['firstname'];
    $lastname = (string)$parameters[0]['lastname'];
    $email = (string)$parameters[0]['email'];

    global $wpdb;
    $table_name = $wpdb->prefix . 'rsap_affiliates';

    $lastAffiliateCode = $wpdb->get_results( "SELECT `code` FROM $table_name ORDER BY id DESC LIMIT 1" );
    $lastAffiliateCode = (int)$lastAffiliateCode[0]->code;


    $nextAffiliateCode = $lastAffiliateCode + 1;

    $data         = array( 'firstname'  => $firstname, 'lastname' => $lastname, 'email' => $email, 'code' => $nextAffiliateCode );
    $where        = array( 'email' => $email );
    $data_format  = array('%s', '%s', '%s', '%s');
    $where_format = null;

    //$updated = $wpdb->update( 'wp_rsap_affiliates', $data, $where, $data_format, $where_format );
    //if( ! $updated )
    $wpdb->insert( $table_name, $data, $data_format );

    

    // Send welcome email
    $referralUrl = get_option('rsap_option_main_url') . '?rfr=' . $nextAffiliateCode;
    $websiteUrl = get_site_url();
    $socialblurb_email = get_option('rsap_option_social_blurb_email');
    $socialblurb_facebook = get_option('rsap_option_social_blurb_facebook');
    $socialblurb_instagram = get_option('rsap_option_social_blurb_instagram');
    $socialimage = get_option('rsap_option_social_image');
    
    $socialblurb_emailtext = '';

    if ($socialblurb_email != '' || $socialblurb_facebook != '' || $socialblurb_instagram != '') {
        $socialblurb_emailtext .= '<p><strong>' . 'For your convenience, we have prepared the following text you can use for easily sharing your referral link: ' . '</strong></p><ul>' ;
    }

    if ($socialblurb_email != '') {
        $socialblurb_emailtext .= '<li>' . 'Email: ' . '"' . $socialblurb_email . ' ' . $referralUrl . '"</li>';
    }

    if ($socialblurb_facebook != '') {
        $socialblurb_emailtext .= '<li>' . 'Facebook: ' . '"' . $socialblurb_facebook . ' ' . $referralUrl . '"</li>';
    }

    if ($socialblurb_instagram != '') {
        $socialblurb_emailtext .= '<li>' . 'Instagram (<em>Note: You will need to place this link in your Instagram bio website field so it can be clicked: ' . $referralUrl . '</em>): ' . '"' . $socialblurb_instagram . '"<br /></li>';
    }

    if ($socialblurb_email != '' || $socialblurb_facebook != '' || $socialblurb_instagram != '') {
        $socialblurb_emailtext .= '</ul>' ;
    }

    if ($socialimage != '') {
        $socialblurb_emailtext .= '<p>We have also attached an image you can use in your social media posts - enjoy!</p>' ;
    }

    

    include( plugin_dir_path( __FILE__ ) . 'affiliateCodeEmailTemplate.php');

    $to = $email;
    $subject = 'Your Personal Affiliate Code: ' . $nextAffiliateCode;
    $body = $affiliateCodeEmailTemplate;
    $headers = array('Content-Type: text/html; charset=UTF-8');

    $mail_attachment_image_id = get_option( 'rsap_option_social_image' );
    $mail_attachment_image_attributes = wp_get_attachment_image_src( $mail_attachment_image_id, array( 1080, 1080 ) );

    $mail_attachment_image_src_url = wp_parse_url( $mail_attachment_image_attributes[0] );
    $mail_attachment_image_src_path = $mail_attachment_image_src_url['path'];
    $mail_attachment_image_src_path_arr = $arr = explode('wp-content', $mail_attachment_image_src_path);
    $mail_attachment_image_src_path_useful = $mail_attachment_image_src_path_arr[1];
    $mail_attachment = array(WP_CONTENT_DIR . $mail_attachment_image_src_path_useful);  
    
    wp_mail( $to, $subject, $body, $headers, $mail_attachment );
}
 
    
add_action( 'rest_api_init', function () {
  register_rest_route( 'rsap/v1', '/affiliate//', array(
    'methods' => 'POST',
    'callback' => 'rsap_add_affiliate',
  ) );
} );




// Create API Endpoint to increment sale for an affiliate

function rsap_log_sale( WP_REST_Request $request ) {

    global $wpdb;
    $sourceIpAddress = (string)$_SERVER['REMOTE_ADDR'];

    $parameters = $request->get_json_params();
    $referrerCode = (string)$parameters[0]['rfr'];

    // Check the log before logging sale if IP restriction is on
    if(get_option('rsap_option_enable_ip_restriction') == true) {
        $table_name_log = $wpdb->prefix . 'rsap_referral_log';

        $checkIfIpUsedBefore = null;
        $checkIfIpUsedBefore = $wpdb->get_results( $wpdb->prepare( "SELECT `source_ip`,`logged` FROM $table_name_log WHERE code = %s AND source_ip = %s ORDER BY `logged` DESC", $referrerCode, $sourceIpAddress) );

        try {
            $checkIfIpUsedBeforeTimestamp = $checkIfIpUsedBefore[0]->logged;
            $checkIfIpUsedBeforeTimestamp = $checkIfIpUsedBefore[0]->logged;
            $checkIfIpUsedBeforeTimestamp = DateTime::createFromFormat ( "Y-m-d H:i:s", $checkIfIpUsedBeforeTimestamp );
            $checkIfIpUsedBefore = (string)$checkIfIpUsedBefore[0]->source_ip;
        }
        catch(Exception $e) {
            // IP has not been used before
        }
        

        if($checkIfIpUsedBefore === $sourceIpAddress) {
            // This IP address has logged a referral before

            $currentDate = new DateTime(date('Y-m-d H:i:s'));
            $daysBackToCheck =  '+' . (string)get_option('rsap_option_days_before_new_submission_allowed') . ' days';

            if($checkIfIpUsedBeforeTimestamp->modify($daysBackToCheck) < $currentDate) { 

                //Last referral from this IP was more than 7 days ago, so do the table addition like normal
                rsap_log_referral_normally($referrerCode, $sourceIpAddress);

            }else {
                //Last referral from this IP was less than 7 days ago

                // Do nothing
            }

        } else {
            // This IP has not logged a referral before, so log as usual
            rsap_log_referral_normally($referrerCode, $sourceIpAddress);
        }
    // End if IP restriction is on
    } else {
        // No IP restriction, so do the table insert like normal
        rsap_log_referral_normally($referrerCode, $sourceIpAddress);
    }
    
}


function rsap_log_referral_normally($referrerCode, $sourceIpAddress){
    global $wpdb;

    $table_name = $wpdb->prefix . 'rsap_affiliates';

        $lastSuccessfulReferralValue = $wpdb->get_results( $wpdb->prepare( "SELECT `successful_referrals` FROM $table_name WHERE code = %s", $referrerCode) );
        $lastSuccessfulReferralValue = (int)$lastSuccessfulReferralValue[0]->successful_referrals;
    
        $nextSuccessfulReferralValue = $lastSuccessfulReferralValue + 1;
    
        $data         = array( 'successful_referrals' => $nextSuccessfulReferralValue );
        $where        = array( 'code' => $referrerCode );
        $data_format  = array( '%s' );
        $where_format = array( '%s' );
    
        // Increment successful referrals
        $wpdb->update( $table_name, $data, $where, $data_format, $where_format );
    
        // Log the referral
        $table_name_log = $wpdb->prefix . 'rsap_referral_log';
    
        $wpdb->insert( 
            $table_name_log, 
            array( 
                'code' => $referrerCode,
                'source_ip' => $sourceIpAddress
            ),
            array(
                '%s',
                '%s'
            ) 
        );
}


 
    
add_action( 'rest_api_init', function () {
  register_rest_route( 'rsap/v1', '/affiliate/sale//', array(
    'methods' => 'POST',
    'callback' => 'rsap_log_sale',
  ) );
} );


// Shortcode for the user to put an affiliate code in a form output email
function rsap_output_referrer_code() {

    $urlReferrerCode = '';

    if (isset($_GET['rfr'])) {
        $urlReferrerCode = $_GET['rfr'];
    }

    if ($urlReferrerCode != '') {
        return '' . $urlReferrerCode . '';
    } else {
        global $wpdb;

        $sourceIpAddress = (string)$_SERVER['REMOTE_ADDR'];
        $table_name_log = $wpdb->prefix . 'rsap_referral_log';
        $getLastReferralCodeFromIp = '';
        $getLastReferralIp = '';
    
        // Get the latest referral code logged from this IP address
        $getLastReferralCodeFromIpQuery = $wpdb->get_results( $wpdb->prepare( "SELECT `code`,`logged`,`source_ip` FROM $table_name_log WHERE source_ip = '%s' ORDER BY `logged` DESC", $sourceIpAddress) );

    
        try {
            $getLastReferralCodeFromIp = (string)$getLastReferralCodeFromIpQuery[0]->code;
            $getLastReferralIp = (string)$getLastReferralCodeFromIpQuery[0]->source_ip;
        }
        catch(Exception $e) {
            // Log entry for that IP not found
        }

        return '' . $getLastReferralCodeFromIp . '';
    }

   
}
add_shortcode('rsap_referrer_code', 'rsap_output_referrer_code');


// Enable shortcode for contact form 7 -  code reference from https://www.howtosnippets.net/wordpress/make-custom-shortcodes-work-in-contact-form-7-mail-form-templates/
    function rsap_special_mail_tag( $output, $name, $html ) {
    
        if ( 'rsap_referrer_code' == $name )
        	$output = do_shortcode( "[$name]" );
     
        return $output;
    }
    add_filter( 'wpcf7_special_mail_tags', 'rsap_special_mail_tag', 10, 3 );

// Shortcode for the form
function rsap_affiliate_form_shortcode() {
    return '
    <div class="rsap_affiliate_form_holder">
        <form>
        <input type="text" name="firstname" placeholder="First Name">
        <input type="text" name="lastname" placeholder="Last Name">
        <input type="email" name="email" placeholder="Email">
        <input type="submit" value="Submit">
        </form>
    </div>
    <style>
    .rsap_affiliate_form_holder {
        display: flex;
        flex-direction: column;
        margin-left: auto !important;
        margin-right: auto !important;
    }
    .rsap_affiliate_form_holder input {
        width: 100%;
        margin: 10px 0px;
    }
    </style>
    ';
}
add_shortcode('rsap_form', 'rsap_affiliate_form_shortcode');


// Enqueue JS file that will listed for the form submit

function rsap_enqueue_script() {
    wp_enqueue_script( 'rsap-main-js', plugins_url( 'js/rsap_main.js', __FILE__ ), array('jquery'), false, true );
    wp_localize_script('rsap-main-js', 'rsap_script_vars', array(
        'conversion_form_wrapper_id' => get_option('rsap_option_conversion_form_wrapper_id'),
        'url_rfr_parameter' => $_GET['rfr']
        )
    );
    
}

add_action( 'wp_enqueue_scripts', 'rsap_enqueue_script' );

function rsap_enqueue_admin_script($hook) {
    // Only add to the edit.php admin page.
    // See WP docs.
    //if ('edit.php' !== $hook) {
    //     return;
    //}

    // WordPress library for media uploads
    wp_enqueue_media();

    wp_enqueue_script( 'rsap-admin-js', plugins_url( 'js/rsap_admin.js', __FILE__ ), array('jquery'), false, true );
}

add_action('admin_enqueue_scripts', 'rsap_enqueue_admin_script');


// Settings link on plugin page
function rsap_add_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=really-simple-affiliate-program">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'rsap_add_settings_link' );


/**
 * Image Uploader
 *
 * author: Arthur Gareginyan www.arthurgareginyan.com
 */
function rsap_arthur_image_uploader( $name, $width, $height ) {

    // Set variables
    $options = get_option( 'rsap_option_social_image' );
    $default_image = plugins_url('img/no-image.png', __FILE__);

    if ( !empty( $options ) ) {
        $image_attributes = wp_get_attachment_image_src( $options, array( $width, $height ) );
        $src = $image_attributes[0];
        $value = $options;
    } else {
        $src = $default_image;
        $value = '';
    }

    $text = __( 'Upload', RSSFI_TEXT );

    // Print HTML field
    echo '
        <div class="upload">
            <img data-src="' . $default_image . '" src="' . $src . '" width="' . $width . 'px" height="' . $height . 'px" />
            <div>
                <input type="hidden" name="rsap_option_social_image" id="rsap_option_social_image" value="' . $value . '" />
                <button type="submit" class="upload_image_button button">' . $text . '</button>
                <button type="submit" class="remove_image_button button">&times;</button>
            </div>
        </div>
    ';
}