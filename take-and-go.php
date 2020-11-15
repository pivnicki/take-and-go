<?php
/**
 * Plugin Name:       Take and Go
 * Plugin URI:        https://easyit.rs
 * Description:       Tracking scrapper
 * Version:           1.10.3
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Stevan Pivnicki
 * Author URI:        https://easyit.rs
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-woo-addon
 * Domain Path:       /takeandgo
 */

defined( 'ABSPATH' ) or die( 'Skippy, plugin file cannot be accessed directly.' );

class TakeAndGo{

	public function __construct(){
		add_action( 'add_meta_boxes',array($this,'mv_add_meta_boxes'));
		add_action( 'save_post',array($this, 'mv_save_wc_order_other_fields'));
	}

	public function mv_add_meta_boxes()
    {
        add_meta_box( 'mv_other_fields', __('Paste your code','woocommerce'), 'mv_add_other_fields_for_packaging', 'shop_order', 'side', 'core' );
    }

    public function mv_add_other_fields_for_packaging()
    {
        global $post;
  //       $post_id=$post->ID;
  //       $order = new WC_Order($post_id);
        // echo $order->get_billing_email();

         $meta_field_data = get_post_meta( $post->ID, '_my_field_slug', true ) ? get_post_meta( $post->ID, '_my_field_slug', true ) : '';

       wk_custom_meta_box_content($meta_field_data);

        echo '<input type="hidden" name="mv_other_meta_field_nonce" value="' . wp_create_nonce() . '">
        <p style="border-bottom:solid 1px #eee;padding-bottom:13px;">
            <input type="text" style="width:250px;";" name="my_field_name" placeholder="' . $meta_field_data . '" value="' . $meta_field_data . '"></p>';

    }

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
}

new TakeAndGo;