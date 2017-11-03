<?php


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Patreon_Frontend {

	function __construct() {

		add_action( 'login_enqueue_scripts', array($this,'patreonEnqueueCss'), 10 );
		add_action( 'login_enqueue_scripts', array($this,'patreonEnqueueJs'), 1 );
		add_action( 'wp_enqueue_scripts', array($this,'patreonEnqueueCss') );
		add_action( 'wp_enqueue_scripts', array($this,'patreonEnqueueJs') );

		if(get_option('patreon-enable-register-with-patreon', false)) {
			add_action( 'register_form', array($this, 'showPatreonButton' ) );
			add_action( 'woocommerce_register_form_end', array($this, 'showPatreonButton') );
		}
		if(get_option('patreon-enable-login-with-patreon', false)) {
			add_action( 'login_form', array($this, 'showPatreonButton' ) );
			add_action( 'woocommerce_login_form_end', array($this, 'showPatreonButton' ) );
		}

		add_filter( 'the_content', array($this, 'protectContentFromUsers'), PHP_INT_MAX );

		if(get_option('patreon-enable-walled-garden', false)) {
			add_action( 'wp', array($this, 'patreonWalledGarden') );
		}

	}

	public function showPatreonButton() {

		global $wp;

		$client_id = get_option('patreon-client-id', false);

		$login_with_patreon = get_option('patreon-enable-login-with-patreon', false);
		$admins_editors_login_with_patreon = get_option('patreon-enable-allow-admins-login-with-patreon', false);

		if($client_id == false) {
			return '';
		}

		$redirect_uri = site_url().'/patreon-authorization/';

		$href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id='.$client_id.'&redirect_uri='.$redirect_uri;

		if($login_with_patreon == true && $admins_editors_login_with_patreon == false && isset($_REQUEST['patreon-msg']) && $_REQUEST['patreon-msg'] == 'login_with_wordpress') {
			echo '<p class="patreon-msg">You can now login with your wordpress username/password.</p>';
		} else if( $login_with_patreon == false && isset($_REQUEST['patreon-msg']) && $_REQUEST['patreon-msg'] == 'login_with_patreon') {
			echo '<p class="patreon-msg">You can now login with your wordpress username/password.</p>';
		} else {
			echo apply_filters('ptrn/login_button', '<a href="'.$href.'" class="ptrn-branded-button ptrn-login" data-ptrn_nonce="' . wp_create_nonce( 'patreon-nonce' ).'"><img class="logo" src="'.PATREON_PLUGIN_ASSETS.'/img/patreon-logomark-on-coral.svg" alt=""> Login with Patreon</a>', $href);
		}

	}

	function patreonEnqueueJs() {
		wp_register_script( 'patreon-wordpress-js', PATREON_PLUGIN_ASSETS.'/js/app.js', array( 'jquery' ) );
		wp_enqueue_script( 'patreon-wordpress-js', PATREON_PLUGIN_ASSETS.'/js/app.js', false );
	}

	function patreonEnqueueCss() {
		wp_register_style( 'patreon-wordpress-css', PATREON_PLUGIN_ASSETS.'/css/app.css', false );
		wp_enqueue_style('patreon-wordpress-css', PATREON_PLUGIN_ASSETS.'/css/app.css' );
	}

	public static function displayPatreonCampaignBanner($patreon_level = false) {

		global $wp;
		global $post;

		$login_with_patreon = get_option('patreon-enable-login-with-patreon', false);

        $creator_id = get_option('patreon-creator-id', false);

        $contribution_required = '';
        if($patreon_level != false) {

        	$contribution_required = '<p>Min. Contribution required: $'.$patreon_level .'</p>';
        	$contribution_required = apply_filters('ptrn/contribution_required',$contribution_required,$patreon_level);

        }
		
		if($login_with_patreon)
		{
			$login_with_patreon_button = self::patreonMakeLoginButton();
		}
        if ($creator_id) {

        	$patreon_post_banner = get_post_meta($post->ID, 'patreon_post_banner', true);

        	if(empty($patreon_post_banner ) == false) {
        		return $patreon_post_banner;
        	}
			
			$be_a_patron_button = self::patreonMakePatronButton($creator_id);
	
			// Wrap message and buttons in divs for responsive interface mechanics
			
			$contribution_required = '<div class="patreon-locked-content-message">'.$contribution_required.'</div>';
			
			$be_a_patron_button = '<div class="patreon-be-patron-button">'.$be_a_patron_button.'</div>';
			
			if(isset($login_with_patreon_button))
			{
				$login_with_patreon_button = '<div class="patreon-login-refresh-button">'.$login_with_patreon_button.'</div>';
			}
			
			// Wrap all of them in a responsive div
			
			$campaign_banner = '<div class="patreon-campaign-banner">'.
									$contribution_required.
									$be_a_patron_button.
									$login_with_patreon_button.
								'</div>';
			
			// This extra button is solely here to test whether new wrappers cause any design issues in different themes. For easy comparison with existing unwrapped button. Remove when confirmed.
			

        	$campaign_banner = apply_filters('ptrn/campaign_banner', $campaign_banner, $patreon_level);

            return $campaign_banner;
        }

	}
	
	function patreonMakePatronButton($creator_id=false) {
		global $post;
		
		if(!$creator_id)
		{
			$creator_id = get_option('patreon-creator-id', false);
		}
		
		/* patreon banner when user patronage not high enough */
				
		$paywall_img = get_option('patreon-paywall-img-url', false);
		
        if ($paywall_img == false) {
        	$paywall_img = '<div class="patreon-responsive-button-wrapper"><div class="patreon-responsive-button"><img class="patreon_logo" src="'.PATREON_PLUGIN_ASSETS.'/img/patreon-logomark-on-coral.svg" alt=""> Support on Patreon</div></div>';
        } else {
        	$paywall_img = '<img src="'.$paywall_img.'" />';
        }		
		
		$login_with_patreon = get_option('patreon-enable-login-with-patreon', false);

		if($login_with_patreon) {
			$redirect_uri = wp_login_url().'?patreon-msg=login_with_patreon&patreon-redirect='.$post->ID;
		} else {
			$redirect_uri = wp_login_url().'?patreon-user-redirect='.$post->ID;
		}

		if(Patreon_Wordpress::isPatron() AND isset($post)) {
			$redirect_uri = get_permalink($post->ID);
		}
		
		$href = 'https://www.patreon.com/bePatron?u='.$creator_id.'&redirect_uri='.urlencode($redirect_uri);
		
		return apply_filters('ptrn/patron_button', '<a href="'.$href.'">'.$paywall_img.'</a>');
	
	
	}
	function patreonMakeLoginButton($client_id=false) {
		
		if(!$client_id)
		{
			$client_id = get_option('patreon-client-id', false);
		}
		
		// Check if user is logged in to WP, for determination of label text
		
		// Set login label to default
		$login_label = PATREON_TEXT_CONNECT;
		
		if(is_user_logged_in()){
			// User is logged into WP. Check if user has valid patreon data :
			
			$user = wp_get_current_user();

			if($user) {
				
				$user_response = Patreon_Wordpress::getPatreonUser($user);
				// ^ REVISIT - whats above may be a concern - it connects to API to check for valid user for every generation of button. If we could cache it it would be better

				if($user_response) {
					// This is a user logged into Patreon. use refresh text
					$login_label = PATREON_TEXT_REFRESH;
				}					
			}
			
			
		}
		
		$redirect_uri = site_url().'/patreon-authorization/';

		$href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id='.$client_id.'&redirect_uri='.$redirect_uri;
	
		return apply_filters('ptrn/login_button', '<a href="'.$href.'" data-ptrn_nonce="' . wp_create_nonce( 'patreon-nonce' ).'"><div class="patreon-responsive-button-wrapper"><div class="patreon-responsive-button"><img class="patreon_logo" src="'.PATREON_PLUGIN_ASSETS.'/img/patreon-logomark-on-coral.svg" alt=""> '.$login_label.'</div></div></a>', $href);

	}	

	function protectContentFromUsers($content) {

		global $post;

		$post_types = get_post_types(array('public'=>true),'names');

		if(in_array(get_post_type(),$post_types)) {

			if(current_user_can('manage_options')) {
				return $content;
			}

			$patreon_level = get_post_meta( $post->ID, 'patreon-level', true );

			if($patreon_level == 0) {
				return $content;
			}

			$user_patronage = Patreon_Wordpress::getUserPatronage();

			if( $user_patronage == false || $user_patronage < ($patreon_level*100) ) {

				//protect content from user

				$content = self::displayPatreonCampaignBanner($patreon_level);

				$content = apply_filters('ptrn/post_content', $content, $user_patronage);


			}

		}

		return $content;

	}

	function patreonWalledGarden() {

		$walled_garden_minimum = get_option('patreon-enable-walled-garden-minimum', 0);
		$walled_garden_page = get_option('patreon-enable-walled-garden-page', false);

		if(is_user_logged_in() && is_admin() == false && current_user_can('manage_options') == false) {

			$user_patronage = Patreon_Wordpress::getUserPatronage();

			if($user_patronage == false) {
				$user_patronage = 0;
			}

			$walled_garden_page = apply_filters('ptrn/walled_garden_page', $walled_garden_page, $user_patronage);

			if($user_patronage < ($walled_garden_minimum*100) ) {
				if($walled_garden_page != false && is_numeric($walled_garden_page)) {

					if(is_page($walled_garden_page) == false) {
						$url = get_permalink($walled_garden_page);
						wp_redirect($url);
						exit;
					}

				}
			}

		}

	}

	public static function returnPatreonEmbeddedContent($the_content) {

		$safety_embeds = get_option('patreon-enable-safe-patreon-embeds', false);

		if($safety_embeds) {

			$the_content = wptexturize($the_content);
			$the_content = convert_smilies($the_content);
			$the_content = convert_chars($the_content);
			$the_content = wpautop($the_content);
			$the_content = shortcode_unautop($the_content);
			$the_content = prepend_attachment($the_content);
			$the_content = do_shortcode($the_content);

			return $the_content;
		}

		return apply_filters('the_content', $the_content );

	}


}

?>
