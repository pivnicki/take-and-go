<?php
/**
 * Plugin Name:       Take and Go
 * Plugin URI:        https://easyit.rs
 * Description:       Tracking scrapper
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * AuthorName:            Stevan Pivnicki
 * AuthorURI:        https://easyit.rs
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-woo-addon
 * Domain Path:       /takeandgo
 */

defined( 'ABSPATH' ) or die( 'Skippy, plugin file cannot be accessed directly.' );
include_once( plugin_dir_path( __FILE__ ) . 'updater.php');
class TakeAndGo{

	public function __construct(){
		add_action( 'add_meta_boxes',array($this,'mv_add_meta_boxes')); 
        add_action('save_post', array($this, 'mv_save_wc_order_other_fields'));
        add_action('admin_menu',array($this, 'woo_scrapper_menu'));
        add_shortcode('scrappe_form', array($this,'woo_shortcode_form')); 
        add_filter('plugins_api', array($this,'misha_plugin_info', 20, 3));
        add_filter('site_transient_update_plugins', array($this,'misha_push_update' ));
    }

    /*add metabox*/
    public function mv_add_meta_boxes()
    {
        add_meta_box( 'mv_other_fields', __('Paste your code','woocommerce'),
           array($this,'mv_add_other_fields_for_packaging'), 'shop_order', 'side', 'core' );
    }
    /*creates metabox html and gets value*/
    public function mv_add_other_fields_for_packaging()
    {
        global $post;
  //       $post_id=$post->ID;
  //       $order = new WC_Order($post_id);
        // echo $order->get_billing_email();

        $this->$meta_field_data = get_post_meta( $post->ID, '_my_field_slug', true ) ? get_post_meta( $post->ID, '_my_field_slug', true ) : '';

        $this->wk_custom_meta_box_content($this->$meta_field_data);

        echo '<input type="hidden" name="mv_other_meta_field_nonce" value="' . wp_create_nonce() . '">
        <p style="border-bottom:solid 1px #eee;padding-bottom:13px;">
        <input type="text" style="width:250px;";" name="my_field_name" placeholder="' . $meta_field_data . '" value="' . $meta_field_data . '"></p>';

    }

    /*save metabox value*/
    public function mv_save_wc_order_other_fields( $post_id ) {

        // We need to verify this with the proper authorization (security stuff).

        // Check if our nonce is set.
        if ( ! isset( $_POST[ 'mv_other_meta_field_nonce' ] ) ) {
            return $post_id;
        }
        $nonce = $_REQUEST[ 'mv_other_meta_field_nonce' ];

        //Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce ) ) {
            return $post_id;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check the user's permissions.
        if ( 'page' == $_POST[ 'post_type' ] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }
        // --- Its safe for us to save the data ! --- //

        // Sanitize user input  and update the meta field in the database.
        update_post_meta( $post_id, '_my_field_slug', $_POST[ 'my_field_name' ] );
    }

    public  function wk_custom_meta_box_content($meta_field_data) {
        global $post;
    // Check for the custom field value
        $product = wc_get_product( $post->ID );
    //$title = $product->get_meta( 'custom_text_field_title' );

        if( empty($meta_field_data) ) {
            echo "Nothing";
        }else{
            global $post;
            $post_id=$post->ID;
            $order = new WC_Order($post_id);
            $billing_email=$order->get_billing_email();
            $mail='';

            $code= get_post_meta($post->ID, 'custom_text_field_title', true);

            $url="https://global.cainiao.com/detail.htm?mailNoList=".$meta_field_data."&spm=a3708.7860688.0.d01";
            $src = file_get_contents($url);
            preg_match('/(?<=originCountry&quot;:&quot;).*?(?=&quot;,&quot;originCpList)/',$src,$originMatch);
            preg_match('/(?<=destCountry&quot;:&quot;).*?(?=&quot;,&quot;destCpList)/',$src,$destinationMatch);
            preg_match('/(?<=time&quot;:&quot;).*?(?=&quot;,&quot;)/',$src,$destinationTime);
            preg_match('/(?<=Handed over to Airlines&quot;,&quot;status&quot;:&quot;&quot;,&quot;time&quot;:&quot;).*?(?=&quot;,&quot;)/',$src,$handedTime);


            echo "Origin country: ".$origin = preg_replace('[^A-za-z]','',$originMatch[0])."<br>";
            echo "Destination country: ".$destination = preg_replace('[^A-za-z]','',$destinationMatch[0])."<br>";
            echo "Arrival at Destination: ".$time = preg_replace("/^[0-9]{4}-(0[1-9]|1[0-2])-$/",'',$destinationTime[0])."<br>";
            echo "Handed over to Airlines: ".$handedtime = preg_replace("/^[0-9]{4}-(0[1-9]|1[0-2])-$/",'',$handedTime[0]);

            $message = 'Tracking information: ';

            $message .= '<p>';
            $message .= '<ul>';
            $message .= '<li>Origin country: ' . $origin . '</li>';
            $message .= '<li>Destination country: ' . $destination . '</li>';
            $message .= '<li>Arrival at Destination:  ' . $time . '</li>';
            $message .= '<li>Handed over to Airlines:' . $handedtime . '</li>';
            $message .= '</ul>';
            $message .= '</p>';

            add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html";' ) );
            wp_mail( $billing_email, 'Your package tracking data', $message );
        }
    }

    public function woo_scrapper_menu() {
        add_options_page('Woo Scrapper Menu', 'Woo Scrapper Menu', 'manage_options', 'my-woo-addon',
            array($this, 'woo_scrapper_function'));
    }

    public function woo_scrapper_function() {
        if (!current_user_can('manage_options'))  {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }
        ?>
        <div class="wrap"> 
           <p>
            Put this shortcode on any page you want [scrappe_form]
        </p> 
    </div> 

    <?php
} 

public function woo_shortcode_form() {
    ob_start();
    ?>  
    <form class="main-div" method="post" action="<?php the_permalink(); ?>">
        <input   type="text" name="code_value">

        <input   type="submit" name="submit" value="Submit">
    </form>
    <?php

    $value=$_POST['code_value'];

    $this->scrapper_function($value);
    return ob_get_clean();
}

public function scrapper_function($meta_field_data){



    $url="https://global.cainiao.com/detail.htm?mailNoList=".$meta_field_data."&spm=a3708.7860688.0.d01";
    $src = file_get_contents($url);
    preg_match('/(?<=originCountry&quot;:&quot;).*?(?=&quot;,&quot;originCpList)/',$src,$originMatch);
    preg_match('/(?<=destCountry&quot;:&quot;).*?(?=&quot;,&quot;destCpList)/',$src,$destinationMatch);
    preg_match('/(?<=time&quot;:&quot;).*?(?=&quot;,&quot;)/',$src,$destinationTime);
    preg_match('/(?<=Handed over to Airlines&quot;,&quot;status&quot;:&quot;&quot;,&quot;time&quot;:&quot;).*?(?=&quot;,&quot;)/',$src,$handedTime);


    if( empty($meta_field_data) ) {
        echo "<center>Add a code to a form</center>";
    }
    else{
       ?>

       <center>

           <?php

           echo "Origin country: ". preg_replace('[^A-za-z]','',$originMatch[0])."<br>";
           echo "Destination country: ".   preg_replace('[^A-za-z]','',$destinationMatch[0])."<br>";
           echo "Arrival at Destination: ".  preg_replace("/^[0-9]{4}-(0[1-9]|1[0-2])-$/",'',$destinationTime[0])."<br>";
           echo "Handed over to Airlines: ".   preg_replace("/^[0-9]{4}-(0[1-9]|1[0-2])-$/",'',$handedTime[0]);
           ?>
       </center>

       <?php
   }
}

/*
 * $res empty at this step
 * $action 'plugin_information'
 * $args stdClass Object ( [slug] => woocommerce [is_ssl] => [fields] => Array ( [banners] => 1 [reviews] => 1 [downloaded] => [active_installs] => 1 ) [per_page] => 24 [locale] => en_US )
 */
public function misha_plugin_info( $res, $action, $args ){
 
    // do nothing if this is not about getting plugin information
    if( 'plugin_information' !== $action ) {
        return false;
    }
 
    $plugin_slug = 'take_and_go'; // we are going to use it in many places in this function
 
    // do nothing if it is not our plugin
    if( $plugin_slug !== $args->slug ) {
        return false;
    }
 
    // trying to get from cache first
    if( false == $remote = get_transient( 'misha_update_' . $plugin_slug ) ) {
 
        // info.json is the file with the actual plugin information on your server
        $remote = wp_remote_get( 'https://easyit.rs/wp-content/uploads/info.json', array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json'
            ) )
        );
 
        if ( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {
            set_transient( 'misha_update_' . $plugin_slug, $remote, 43200 ); // 12 hours cache
        }
 
    }
 
    if( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && ! empty( $remote['body'] ) ) {
 
        $remote = json_decode( $remote['body'] );
        $res = new stdClass();
 
        $res->name = $remote->name;
        $res->slug = $plugin_slug;
        $res->version = $remote->version;
        $res->tested = $remote->tested;
        $res->requires = $remote->requires;
        $res->author = '<a href="https://easyit.rs">Stevan Pivnicki</a>';
        $res->author_profile = 'https://profiles.wordpress.org/labyrinthman';
        $res->download_link = $remote->download_url;
        $res->trunk = $remote->download_url;
        $res->requires_php = '5.3';
        $res->last_updated = $remote->last_updated;
        $res->sections = array(
            'description' => $remote->sections->description,
            'installation' => $remote->sections->installation,
            'changelog' => $remote->sections->changelog
            // you can add your custom sections (tabs) here
        );
 
        // in case you want the screenshots tab, use the following HTML format for its content:
        // <ol><li><a href="IMG_URL" target="_blank"><img src="IMG_URL" alt="CAPTION" /></a><p>CAPTION</p></li></ol>
        if( !empty( $remote->sections->screenshots ) ) {
            $res->sections['screenshots'] = $remote->sections->screenshots;
        }
 
        // $res->banners = array(
        //     'low' => 'https://YOUR_WEBSITE/banner-772x250.jpg',
        //     'high' => 'https://YOUR_WEBSITE/banner-1544x500.jpg'
        // );
        return $res;
 
    }
 
    return false;
 
}

public function misha_push_update( $transient ){
 
    if ( empty($transient->checked ) ) {
            return $transient;
        }
 
    // trying to get from cache first, to disable cache comment 10,20,21,22,24
    if( false == $remote = get_transient( 'misha_upgrade_take_and_go' ) ) {
 
        // info.json is the file with the actual plugin information on your server
        $remote = wp_remote_get( 'https://easyit.rs/wp-content/uploads/info.json', array(
            'timeout' => 10,
            'headers' => array(
                'Accept' => 'application/json'
            ) )
        );
 
        if ( !is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 && !empty( $remote['body'] ) ) {
            set_transient( 'misha_upgrade_take_and_go', $remote, 43200 ); // 12 hours cache
        }
 
    }
 
    if( $remote ) {
 
        $remote = json_decode( $remote['body'] );
 
        // your installed plugin version should be on the line below! You can obtain it dynamically of course 
        if( $remote && version_compare( '2.0', $remote->version, '<' ) && version_compare($remote->requires, get_bloginfo('version'), '<' ) ) {
            $res = new stdClass();
            $res->slug = 'take_and_go';
            $res->plugin = 'take_and_go/take-and-go.php'; // it could be just YOUR_PLUGIN_SLUG.php if your plugin doesn't have its own directory
            $res->new_version = $remote->version;
            $res->tested = $remote->tested;
            $res->package = $remote->download_url;
                $transient->response[$res->plugin] = $res;
                //$transient->checked[$res->plugin] = $remote->version;
            }
 
    }
        return $transient;
}

}

new TakeAndGo;
$updater = new Smashing_Updater( __FILE__ ); // instantiate our class
$updater->set_username( 'pivnicki' ); // set username
$updater->set_repository( 'takeandgo' ); // set repo
$updater->initialize(); 
