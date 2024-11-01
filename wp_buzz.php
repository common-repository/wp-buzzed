<?php
/*
Plugin Name: WP-Buzz
Plugin URI: http://job.achi.idv.tw/wp-buzz
Description: post to Buzz whenever post is saved
Author: Stephen Liu
Version: 0.1
Author URI: http://job.achi.idv.tw/
*/

require_once 'src/buzz.php';
require_once "includes/createBuzz.php";

add_action( 'admin_menu', 'wpbuzz_add_menu' );

function wpbuzz_add_menu()
{
    add_options_page('Setup WP-Buzzs', 'wp-buzzs', 8, 'wp_buzz.php', 'wpbuzz_options');
}

function wpbuzz_options()
{
    ?>
    <div class="wrap">
    <h2>WP-Buzz Options</h2>

    <form method="post" action="options.php">
    <?php wp_nonce_field('update-options'); ?>

    <table class="form-table">
    <tr valign="top">
        <th scope="row">Buzz Sitename</th>
        <td><input type="text" name="wpbuzz_site_name" value="<?php echo get_option('wpbuzz_site_name'); ?>" size="20" /></td>
    </tr>
    <tr valign="top">
        <th scope="row">Buzz oauth_consumer_key</th>
        <td><input type="text" name="wpbuzz_oauth_consumer_key" value="<?php echo get_option('wpbuzz_oauth_consumer_key'); ?>"  size="20" /></td>
    </tr>
    <tr valign="top">
        <th scope="row">Buzz oauth_consumer_secret</th>
        <td><input type="text" name="wpbuzz_oauth_consumer_secret" value="<?php echo $tpl = get_option('wpbuzz_oauth_consumer_secret'); ?>"  size="50" /></td>
    </tr>
     <tr valign="top">
        <th scope="row">Buzz domain</th>
        <td><input type="text" name="wpbuzz_domain" value="<?php echo $tpl = get_option('wpbuzz_domain'); ?>"  size="50" /></td>
    </tr>
    </table>

    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="wpbuzz_site_name,wpbuzz_oauth_consumer_key,wpbuzz_oauth_consumer_secret,wpbuzz_domain" />

    <p class="submit">
    <input type="submit" class="button-primary" value="Save" />
    </p>

    </form>
    </div>
    <?php
}

add_action('publish_post', 'wpbuzz_dobuzz');

function wpbuzz_dobuzz($post_id)
{
     
    if (wp_is_post_revision($post_id))
        return $post_id;

    //is already buzzed?
    $is_buzzed = get_post_meta( $post_id, '_buzzed', true );
    if ($is_buzzed)
        return $post_id;

    $buzz_site_name = get_option('wpbuzz_site_name');
    $buzz_oauth_consumer_key = get_option('wpbuzz_oauth_consumer_key');
    $buzz_oauth_consumer_secret = get_option('wpbuzz_oauth_consumer_secret');
    $buzz_domain = get_option('wpbuzz_domain');
    
    //username or password not set, no need to buzz
    if (!$buzz_oauth_consumer_key || !$buzz_oauth_consumer_secret || !$buzz_domain)
        return $post_id;

    $post = get_post( $post_id );
    $title = $post->post_title;
    $permalink = post_permalink($post_id);
      
    $buzz_text = strip_tags($post->post_content);
    global $buzzConfig;
    $buzzConfig = array(
          'site_name' => $buzz_site_name,
          'oauth_consumer_key' => $buzz_oauth_consumer_key,
          'oauth_consumer_secret' => $buzz_oauth_consumer_secret,
          'oauth_rsa_key' => '',

          // Don't change these values unless you know what you're doing
          'base_url' => 'https://www.googleapis.com/buzz/v1',

          /* Google's OAuth end-points */
          'access_token_url' => 'https://www.google.com/accounts/OAuthGetAccessToken',
          'request_token_url' => 'https://www.google.com/accounts/OAuthGetRequestToken',
          'authorization_token_url' => 'https://www.google.com/buzz/api/auth/OAuthAuthorizeToken?scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fbuzz&domain='.$buzz_domain.'&oauth_token=',
          'oauth_scope' => 'https://www.googleapis.com/auth/buzz'
         );         
    $buzz = createBuzz();    
 
    $newPost = false;
     
    if (isset($buzz_text)) {
        $object = new buzzObject($buzz_text);
                
        //FIXME this shouldn't be so difficult .. add addLink and addPhoto misc functions to the object
        if (isset($permalink)) {
            if (isset($title)) {
                $buzzPostLinkTitle = $title;
        } else {
            $buzzPostLinkTitle = null;
        }
        $attachment = new buzzAttachment('article', $buzzPostLinkTitle);
  
        $attachment->links = array('alternate' => array(new buzzLink($permalink, 'text/html')));
        $object->attachments = array($attachment);
        }
        $postbuzz = buzzPost::createPost($object);
        $newPost = $buzz->createPost($postbuzz);        
        
    }   

    //mark as buzzed
    add_post_meta( $post_id, '_buzzed', '1' );
    
    return $post_id;
}

?>