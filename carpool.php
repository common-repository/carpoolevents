<?php
/*
Plugin Name: Carpool.events
Plugin URI: https://www.carpool.events
Description: Add carpooling to your WordPress site. Fully integrated. No hassles. Click SETTINGS in the left WP menu-bar and select carpool.events to configure this plugin.
Version: 1.5.0
Author: Errel
Author URI: https://www.carpool.events
License:  This plugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or (at your option) any later version. Carpool Events is freemium ware. A version is
available free of charge and a pro version with advanced extras is available for a small charge per year.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
*/
defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

$var_carheader = "not_yet_set";
$carpoolevents_server = 'https://www.easyapps.io';
//$carpoolevents_server = 'http://192.168.0.90:8081';
$carpoolevents_appserver = 'https://app50.easyapps.io';
//$carpoolevents_appserver = 'http://192.168.0.90:3000';

$characters = "abcdefghijklmnopqrstuvwxyz0123456789"; $randstring = '';
for ($i = 0; $i < 20; $i++) { $randstring .= $characters[rand(0, strlen($characters)-1)]; }
$wpsessionsecuritycode = "wpses_" . $randstring;

function carpoolevents_saveoption() {

    // generate the response
    $response = json_encode( $_GET );

    // response output
    header("content-type: application/javascript; charset=utf-8");

    //header("access-control-allow-origin: *");
    $tmp_allfields =  json_decode(json_encode($_GET));

    if($tmp_allfields->carpooleventswpses == $wpsessionsecuritycode) {

        if( !isset( $tmp_allfields->boPw ) || $tmp_allfields->boPw == '' || !isset( $tmp_allfields->carpooleventsid ) || $tmp_allfields->carpooleventsid == '' || !isset( $tmp_allfields->carpooleventsmail ) || $tmp_allfields->carpooleventsmail == '' ) {
            header('Content-Type: application/json');

            $res = array( 'success' => true, 'error' => "Security check failed (1)" );
            wp_send_json($res);
            exit;

        } else {
            header('Content-Type: application/json');

            update_option( "carpooleventsid", $tmp_allfields->carpooleventsid);
            update_option( "carpooleventsregion", $tmp_allfields->carpooleventsregion);
            update_option( "carpooleventsmail", $tmp_allfields->carpooleventsmail);
            update_option( "carpooleventspw", $tmp_allfields->boPw);                 //received via https encrypted connection - is encodeuricompo
            update_option( "carpooleventssession", $tmp_allfields->boSession);       //received via https encrypted connection
            update_option( "carpooleventsaccountcode", $tmp_allfields->boAccountcode);       //received via https encrypted connection
            //update_option( "carpooleventsdomain", $tmp_allfields->boDomain);       //received via https encrypted connection
            update_option( "carpool_header", "");       //received via https encrypted connection

            $res = array( 'success' => true, 'message' => "all ok " . $tmp_allfields->carpooleventswpses );
            wp_send_json($res);
            exit;
        }

    } else {
            update_option("carpool_cov_social", "set in saveoption after security failed");

            $res = array( 'success' => true, 'error' => "Security check failed (2)" . $tmp_allfields->carpooleventswpses);
            wp_send_json($res);
            exit;
    }

}

if(!class_exists('WP_carpoolclass')) {
  class WP_carpoolclass {

    /**
     * Tag identifier used by file includes and selector attributes.
     * @var string
     */
    protected $tag = 'carpool';

    /**
     * User friendly name used to identify the plugin.
     * @var string
     */
    protected $name = 'carpool.events';

    /**
     * Current version of the plugin.
     * @var string
     */
    protected $version = '1.5';


    protected $repeat = 0;

    /**
     * List of options to determine plugin behaviour.
     * @var array
     */
    protected $options = array();

      /**
     * Construct the plugin object
     */
    public function __construct() {

      //add_shortcode( $this->tag, array( &$this, 'shortcode' ) );
      
      add_shortcode( $this->tag, array( &$this, 'shortcode' ) );
      
      // register actions
      if ( is_admin() ) {
        $k = "0123456789";
        $randstring = '';
        for ($i = 0; $i < 4; $i++) {
            $randstring .= $k[rand(0, strlen($k)-1)];
        }

        update_option("carpool_cache", $randstring);
        add_action('admin_init', array(&$this, 'admin_init'));
        add_action('admin_menu', array(&$this, 'add_menu'));
      }

    } // END public function __construct

    /**
     * Allow the shortcode to be used.
     *
     * @access public
     * @param array $atts
     * @param string $content
     * @return string
     */
    public function shortcode( $atts, $content = "") {

      extract( shortcode_atts( array(
          'date',
          'title',
          'btntitle'
      ), $atts ) );

      // Enqueue the required styles and scripts...
      $this->_enqueue();

      if (is_array($atts)) {
          if (array_key_exists('date', $atts) ) {
              if ( $atts['date'] !== null && is_numeric( $atts['date'] ) ) {
                $par_date = esc_attr($atts['date']);
              }
          };
          if (array_key_exists('title', $atts) ) {
              if ( $atts['title'] !== null) {
                   $par_title = esc_attr($atts['title']);
              }
          };
          if (array_key_exists('btntitle', $atts) ) {
              if ( $atts['btntitle'] !== null) {
                   $par_btntitle = esc_attr($atts['btntitle']);
              }
          };

          if($par_btntitle==""){ $par_btntitle="Carpool"; }
          if($par_date != "") {
            //$cpout = "[carpool:" . $par_date . ":" . $par_title . "]";
            $cpout = "[carpool date='" . $par_date . "' title='" . $par_title . "' btntitle='" . $par_btntitle . "']";
            return $cpout;
          } else {
            $cpout = "[carpool";
            for ($x = 0; $x <= 20; $x++) {
              if($atts[$x] !== null){
                $cpout .= $atts[$x] . " ";
              }
            }
            return $cpout . "]";
          }

      };
  }

    /**
     * Enqueue the required scripts and styles, only if they have not
     * previously been queued.
     *
     * @access public
     */
  protected function _enqueue() {
    // Define the URL path to the plugin...
    $plugin_path = plugin_dir_url( __FILE__ );
    $carpool_cache = get_option("carpool_cache");
    $carpool_region = get_option("carpooleventsregion");
    
    $carpoolurlparams = "1.50&data-id=" . get_option("carpooleventsaccountcode") . "&data-region=" . get_option("carpooleventsregion");
    global $carpoolevents_server;

       // Enqueue the scripts if not already...
    if ( !wp_script_is( $this->tag, 'enqueued' ) ) {
        wp_enqueue_script('jquery' );

        wp_enqueue_script(
            'easyappsloader',
            $carpoolevents_server . '/s1.5/car.js',
            array( 'jquery' ),
            $carpoolurlparams,  
            true
        );

        wp_enqueue_script( $this->tag );
    } else {

    }
  }

    /**
     * hook into WP's admin_init action hook
     */
  public function admin_init() {
    wp_enqueue_script('jquery');
    $var_carpoolevents_id = get_option("carpooleventsid");

    add_action( 'wp_ajax_carpoolevents', 'carpoolevents_saveoption' );
    add_action( 'wp_ajax_nopriv_carpoolevents', 'carpoolevents_saveoption' );

    // Possibly do additional admin_init tasks
  } // END public static function activate


 /**
 * add a menu
 */
  public function add_menu() {
    // Add a page to manage this plugin's settings
    add_options_page(
        'carpool.events',
        'Carpool.events',   //title in Settings sub-menu
        'manage_options',
        'wp_carpoolevents-page',
        array(&$this, 'plugin_settings_page')
    );
  } // END public function add_menu()

  /**
  * Menu Callback
  */
  public function plugin_settings_page() {
    $plugin_path = plugin_dir_url( __FILE__ );
    global $carpoolevents_server;
    global $carpoolevents_appserver;

    if(!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // This does not work:
    // wp_enqueue_script(
    //     'jStorage',
    //     $plugin_path . 'jstorage.min.js',
    //     array( 'jquery' ),
    //     "0.4.12",  //cache reset after each settingspage refresh
    //     true
    // );

    ?>
<!-- HTML +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<script src="<?php echo $carpoolevents_appserver; ?>/wp-admin-carpool.js?v=1.5"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jStorage/0.4.12/jstorage.min.js"></script>


<div class="wrap">
  <style>
    .marleft {
        margin-left: 50px;
    }

    .carpl_greenbar {
        background-color: #B3CFEC;  /* wtf++ -> blue is the new green */
        padding-top: 10px;
        padding-bottom: 10px;
        padding-left: 5px;
        cursor: pointer;
    }

    .managementcontent {
        height: 0;
        overflow: hidden;
    }

    .carpl_status,
    .carpl_bostatus {
        /*                    float: right;*/
        padding-right: 2px;
        padding-left: 2px;
    }

    .carpl_green {
        color: #45A168; /* wtf++ */
    }

    .carpl_chevron,
    .carpl_bochevron,
    .floatr {
        float: right;
        padding-right: 5px;
        height: 100%;
    }

    .notvisible {
        display: none;
        visibility: hidden;
    }

    .carpl_importopt {
        visibility: hidden;
    }

    .carpl_importopt_active {
        visibility: visible;
        font-weight: bold;
    }

    .carpl_importopt_nonactive {
        visibility: hidden;
    }

    .carpl_exportopt {
        visibility: hidden;
    }

    .carpl_exportopt_active {
        visibility: visible;
        font-weight: bold;
    }

    .carpl_exportopt_nonactive {
        visibility: hidden;
    }

    #openboframeExt {
        padding: 5px;
    }

    .carpl_imgonbtn{
        vertical-align: middle;
        max-width: 40px;
        max-height: 25px;
    }
    .carpl_txtonbtn{
        vertical-align: middle;
        padding: 3px;
        font-weight: bold;
    }

  </style>

  <a href="https://www.carpool.events" target="_blank">
      <img src="<?php echo plugins_url('carpool_events_95x50.png', __FILE__ ); ?>" alt="Manage your carpool events with carpool.events">
  </a>

  <p style="font-size:105%">
  <table>
    <tr>
      <td>
        <?php if ( get_option("carpool_version") != "150" && get_option("carpooleventsid") != "" ) { ?>
          <?php
            update_option( "carpool_version", "150");
          ?>
          <h3>HOW TO UPGRADE?</h3>
          <p style="font-size:105%">
          <ol>
            <li>It is necessary to let the software generate a new format settings file.</li>
            <li>Click at the 'Manage Carpool.events' button.</li>
            <li>In the Carpool.events window -> read the upgrade info and click OK.</li>
            <li>Back in WP -> empty the WP cache and check the existing carpool buttons in your site (if any)</li>
            <li>(To check the active version on your site: move your mouse over the event title in the pop-up)</li>
          </ol>
          </p>
        <?php } elseif (get_option("carpool_version") != "150") { ?>
          <?php
            update_option( "carpool_version", "150");
          ?>
        <?php } else { ?>
          <?php $covregion = "us" ?>
          <h3>Carpool.events</h3>
          <p style="font-size:105%">
            Now the plugin is active the [carpool shortcodes will be transformed into carpool-buttons.<br>
            Your users can click the carpool button to add their offer or demand, or contact existing carpoolers.<br>
            You can create new shortcodes for new events, manage the carpoolers and the settings by clicking at the 'Manage Carpool.events' button.<br>
            Carpool.events is a cloud based solution, it will not slow down your WP site in any way.</p>
        <?php } ?>

      </td>
      <td>
        <?php if ( get_option("carpooleventsaccountcode") ) { ?>
        <a href='https://sales.easyapps.io/sales.html?checkout={"accountcode":"<?php echo get_option("carpooleventsaccountcode"); ?>","mid":"ee","lang":"0","showonly":"cov","newlics":false}' target="_blank" class="button button-primary carpl_upgr notvisible floatr"> Upgrade to Pro version </a>
        <?php } ?>
      </td>
    </tr>
  </table>
  </p>

  <div id="accordion">

  <?php if (get_option("carpooleventspw") && get_option("carpooleventsaccountcode")) { ?>

    <h3 id="setupbar" class="carpl_greenbar">Setup <span class="carpl_status"><span class="dashicons dashicons-yes carpl_green"></span></span><span class="carpl_chevron"><span class="dashicons dashicons-minus"></span></span></h3>

    <div class="setupcontent">
      <button class="btn btn-default carpl_export floatr"> Export account </button>
      <table>
        <tr>
          <td><b>ID</b>:</td>
          <td>
            <?php echo get_option('carpooleventsid'); ?>
          </td>
          <td><span class="carpl_exportopt"><b>Password</b>:</span></td>
          <td>
            <span class="carpl_exportopt">
              <span id="carpl_exporttmppw"></span>
              <button onclick="jQuery('#carpl_exporttmppw').html(makeEpw(decodeURIComponent(carpooleventspw) ) );">Show password</button>
            </span>
          </td>
        </tr>
        <tr>
          <td><b>E-mail</b>:</td>
          <td>
              <?php echo get_option('carpooleventsmail'); ?>
          </td>
          <td><span class="carpl_exportopt"><b>Account code</b>:</span></td>
          <td>
              <span class="carpl_exportopt"><?php echo get_option("carpooleventsaccountcode"); ?></span>
          </td>
        </tr>
        <tr>
          <td><!-- <b>Domain name</b>: --></td>
          <td>
              <?php //echo get_option('carpooleventsdomain'); ?>
          </td>
          <td></td>
          <td>
              <span class="carpl_exportopt">
                  </b><i>Install the plugin in the destination WordPress,<br> click the Import button<br> and copy and paste the id, password and accountcode.</i><b>
              </span>
          </td>
        </tr>
      </table>

      <br>
      <div class="instructionscontent">

      </div>

      <span id="InfoPanel"></span>

    </div>

  <?php } else { ?>
    <h3 class="carpl_greenbar">Setup <span class="carpl_status"><span class="dashicons dashicons-warning"></span> Setup required</span> <span class="carpl_chevron"><span class="dashicons dashicons-minus"></span></span></h3>
    <div class="setupcontent">
        <br> The e-mail address is required and used for identification and security messages only.
        <button class="btn btn-default carpl_import floatr"> Import account </button>
        <div class="marleft">
          <div id="newaccount1512">

            <form id='carpooleventsform'>
              <table>
              <tr class="carpl_importopt">
                  <td>Account code:</td>
                  <td>
                      <input type='text' id='carpooleventsform_accountcode' value="<?php echo $accountcode; ?>">
                      <input type='hidden' id='carpooleventsform_region' value="<?php echo $covregion; ?>">
                  </td>
                  <td>
                  </td>
              </tr>

              <tr>
                  <td>Login name: <span class="carpl_importopt"> (from the account to import) </span></td>
                  <td>
                      <input type='text' id='carpooleventsform_id' value='<?php echo get_option('carpooleventsid'); ?>' readonly>
                  </td>
                  <td>
                  </td>
              </tr>
              <tr class="carpl_importopt">
                  <td>Password:</td>
                  <td>
                      <input type='text' id='carpooleventsform_pw' value="">
                  </td>
                  <td>
                  </td>
              </tr>

              <tr>
                  <td>E-mail:</td>
                  <td>
                      <input type='text' id='carpooleventsform_mail' value='<?php echo get_option('carpooleventsmail'); ?>'>
                  </td>
                  <td>
                  </td>
              </tr>
              <tr>
                  <td>&nbsp; </td>
                  <td>
                      <input type="hidden" id='carpooleventsform_wpses' value="<?php echo $wpsessionsecuritycode; ?>">
                      <input type="submit" name="submit" id="carpooleventssubmit1" class="button button-primary" value="Create account">
                      <input type="submit" name="submit" id="carpooleventssubmit2" class="button button-primary carpl_importopt" value="Import account">
                  </td>
                  <td>
                  </td>
              </tr>
              </table>
            </form>
        </div>

        <br>
        <div class="instructionscontent">

        </div>

        <span id="InfoPanel"></span>

      </div>
    </div>
  <?php } ?>


<h3 class="carpl_greenbar">Manage settings and carpoolers:  <span class="carpl_bostatus"></span><span class="carpl_bochevron"></span></h3>

  <div class="marleft">
    Create shortcodes, manage settings and carpoolers with this button:<br>
    <button data-src="" id="openboframeExt">
      <img src="<?php echo plugins_url('carpool_events_95x50.png', __FILE__ ); ?>" alt="carpool.events" class="carpl_imgonbtn">
      <span class="carpl_txtonbtn">Manage Carpool.events</span>
    </button>
    <div class="managementcontent">
    </div>

  </div>
  <!-- /accordion -->

      <hr>
      <a href="https://www.carpool.events" target="_blank">Carpool.events</a>
      <hr>

  </div>
  <!-- /wrap-->

  <script type="text/javascript">
    var appserver = "<?php echo $carpoolevents_appserver; ?>";
    var modeimport = false;
    var modeexport = false;
    var carpl_defstatus = "<?php
    if ($var_carpoolevents_id == FALSE) {
        echo "0";
    } else {
        echo "1";
    } ?>";

    var carpl_acceptfrms = false;
    var k = 123456789;
    var carpooleventsid = "<?php
    $var_carpoolevents_id = get_option("carpooleventsid");
    if ($var_carpoolevents_id == FALSE) {
        $k = "0123456789";
        $randstring = '';
        for ($i = 0; $i < 20; $i++) {
            $randstring .= $k[rand(0, strlen($k)-1)];
        }
        $var_carpoolevents_id = "user_".$randstring;
    }
    echo $var_carpoolevents_id; ?> ";
    var carpooleventsmail = "<?php echo get_option("carpooleventsmail"); ?>";
    var carpooleventsregion = "<?php echo get_option("carpooleventsregion"); ?>";
    var carpooleventspw = "<?php echo get_option("carpooleventspw"); ?>";
    var carpooleventssession = "<?php echo get_option("carpooleventssession"); ?>";
    var carpooleventsaccountcode = "<?php echo get_option("carpooleventsaccountcode"); ?>";
  </script>
  <!-- end HTML +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<?php
} // END public function plugin_settings_page()

    /**
     * Settings intro
     */
    public function wp_carpooleventsclass_cb() {
        // Think of this as help text for the section.
        //reserved

    }

    /**
     * This function provides text inputs for settings fields
     */
    public function settings_field_input_text($args) {
        // Get the field name from the $args array
        $field = $args['field'];
        // Get the value of this setting
        $value = get_option($field);
        // echo a proper input type="text"
        echo sprintf('<input type="text" cols=50 name="%s" id="%s" value="%s"></input>', $field, $field, $value);
    } // END public function settings_field_input_text($args)

    public function settings_field_input_textarea($args) {
        // Get the field name from the $args array
        $field = $args['field'];
        // Get the value of this setting
        $value = get_option($field);
        // echo a proper input type="text"
        echo sprintf('<textarea cols=50 rows=5 name="%s" id="%s">%s</textarea>', $field, $field, $value);
    } // END public function settings_field_input_text($args)


    /**
     * Activate the plugin
     */
    public static function activate() {
        // Do nothing
        //reserved
    } // END public static function activate

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        delete_option("carpooleventsid");
        delete_option("carpooleventsmail");
        delete_option("carpooleventspw");
        delete_option("carpooleventsregion");
        delete_option("carpooleventssession");
        delete_option("carpooleventsaccountcode");
        delete_option("carpooleventsdomain");
    } // END public static function deactivate

  } // END class WP_carpoolclass

} // END if(!class_exists('WP_carpoolclass'))

if( !class_exists( 'WP_Http' ) ) {
    include_once( ABSPATH . WPINC. '/class-http.php' );
}

if(class_exists('WP_carpoolclass')) {

    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_carpoolclass', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_carpoolclass', 'deactivate'));

    // instantiate the plugin class
    $plugininstance = new WP_carpoolclass();

    $var_carheader = get_option("plugin_header");
    if(strlen($var_carheader)<1) { $var_carheader = "alert('Please setup first!')"; }
    // Add a link to the settings page onto the plugin page

    if(isset($plugininstance)) {
        // reserved
    }
}

?>
