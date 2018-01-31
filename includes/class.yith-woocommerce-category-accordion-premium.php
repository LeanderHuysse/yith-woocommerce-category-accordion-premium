<?php
if( !defined( 'ABSPATH' ) )
    exit;

if( !class_exists( 'YITH_WC_Category_Accordion_Premium' ) ) {

    class YITH_WC_Category_Accordion_Premium extends YITH_WC_Category_Accordion
    {


        protected $custom_filename;
        protected $_rules = array();

        public function __construct()
        {
            parent::__construct();

            $this->custom_filename = 'ywcca_dynamics.css';
            add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
            add_action( 'init', array( $this, 'check_file_exists' ) );
            add_action( 'admin_init', array( $this, 'register_plugin_for_updates' ) );
            add_action( 'init', array( $this, 'update_dynamics_css' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_premium_style_script' ) );
            add_filter('ywcca_script_params', array( $this, 'add_script_params' ) );

            if( is_admin() ) {

                add_action( 'admin_init', array( $this, 'ywcca_add_shortcodes_button' ) );
                add_action( 'media_buttons_context', array( &$this, 'ywcca_media_buttons_context' ) );
                add_action( 'admin_print_footer_scripts', array( &$this, 'ywcca_add_quicktags' ) );
                add_action( 'admin_enqueue_scripts', array( $this, 'include_admin_style_script' ) );
                add_filter( 'yith_category_accordion_admin_tabs', array( $this, 'add_premium_admin_tabs'), 10, 1 );

                $this->_include();
                add_action( 'woocommerce_admin_field_typography', 'YWCCA_Typography::output' );

            }
        }

        /**Returns single instance of the class
         * @author YITHEMES
         * @since 1.0.0
         * @return YITH_WooCommerce_Category_Accordion_Premium
         */
        public static function get_instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }


        private function _include()
        {
            include_once(YWCCA_TEMPLATE_PATH . '/admin/typography.php');
        }

        /**
         * Register plugins for activation tab
         *
         * @return void
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function register_plugin_for_activation()
        {

            if (!class_exists('YIT_Plugin_Licence')) {
                require_once YWCCA_DIR.'plugin-fw/licence/lib/yit-licence.php';
                require_once YWCCA_DIR.'plugin-fw/licence/lib/yit-plugin-licence.php';
            }
            YIT_Plugin_Licence()->register(YWCCA_INIT, YWCCA_SECRET_KEY, YWCCA_SLUG);
        }

        /**
         * Register plugins for update tab
         *
         * @return void
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function register_plugin_for_updates()
        {
            if (!class_exists('YIT_Upgrade')) {
                require_once( YWCCA_DIR.'plugin-fw/lib/yit-upgrade.php');
            }
            YIT_Upgrade()->register(YWCCA_SLUG, YWCCA_INIT);
        }


        /**add script params, for extend script free
         * @author YITHEMES
         * @use ywcca_script_params
         * @param $args
         * @return mixed
         */
        public function add_script_params( $args )
        {
            $args['highlight_current_cat'] =  get_option( 'ywcca_highlight_category' )=='yes';
            $args['event_type'] = get_option('ywcca_event_type_start_acc');
            $args['accordion_speed'] = get_option('ywcca_accordion_speed');
            $args['accordion_close'] = get_option('ywcca_accordion_macro_cat_close') == 'yes';
            $args['open_sub_cat_parent'] = get_option('ywcca_open_sub_cat_parent_visit') == 'yes';
            $args['toggle_always'] = true;

            return $args;
        }

        /**include style and script premium for frontend
         * @author YITHEMES
         * @since 1.0.0
         *
         */
        public function enqueue_premium_style_script()
        {

            wp_register_script( 'hover_intent', YWCCA_ASSETS_URL . 'js/jquery.hoverIntent.min.js', array('jquery'), YWCCA_VERSION, true );
            wp_enqueue_script( 'hover_intent' );

            wp_register_style( 'ywcca_dynamics', YWCCA_URL . 'cache/' . $this->_get_stylesheet_name() );
            wp_enqueue_style( 'ywcca_dynamics' );
        }

        /**include admin premium style and premium script
         * @author YITHEMES
         * @since 1.0.0
         * @param $hook
         */
        public function include_admin_style_script( $hook )
        {
            wp_register_script( 'ywcca_admin_script', YWCCA_ASSETS_URL . 'js/ywcca_admin'.$this->suffix.'.js', array('jquery'), YWCCA_VERSION, true );
            wp_enqueue_script( 'ywcca_admin_script' );


            global $woocommerce;


           if ( $hook == 'widgets.php' ) {

                wp_enqueue_script( 'select2', $woocommerce->plugin_url() . '/assets/js/select2/select2' . $this->suffix . '.js', array( 'jquery' ), '3.5.2' );
                wp_enqueue_script( 'wc-enhanced-select', $woocommerce->plugin_url() . '/assets/js/admin/wc-enhanced-select' . $this->suffix . '.js', array( 'jquery', 'select2' ), WC_VERSION  );
                wp_enqueue_script( 'ywcca_widget', YWCCA_ASSETS_URL .'js/ywcca_widget'.$this->suffix.'.js', array( 'jquery' ), YWCCA_VERSION, true );

            }

        }

        /**check if the ywcca_dynamics.css exists (for first installation)
         * @author YITHEMES
         * @since 1.0.0
         * @return bool|int
         */
        public function check_file_exists()
        {

            $file_path = YWCCA_DIR . 'cache/' . $this->_get_stylesheet_name();

            if ( !file_exists( $file_path ) ) {
                return $this->write_dynamic_css();
            } else
                return true;
        }

        /**write dynamic css
         * @author YITHEMES
         * @since 1.0.0
         * @return bool|int
         */
        public function write_dynamic_css()
        {
            global $wpdb;

            $css = array();

            // collect all css rules

            if ( empty( $this->_rules ) ) {
                $this->get_theme_options_css_rules();
            }

            foreach ( $this->_rules as $rule => $args ) {
                $args_css = array();
                foreach ( $args as $arg => $value ) {

                    $args_css[] = $arg . ': ' . $value . ';';
                }
                $css[] = $rule . ' { ' . implode(' ', $args_css) . ' }' . "\n\n";
            }

            $css = apply_filters( 'ywcca_dynamics_style', implode( "", $css ) );

            // save the css in the file
            $index = $wpdb->blogid != 0 ? '-' . $wpdb->blogid : '';

            $directory = YWCCA_DIR.'cache/';
            $file = $directory. str_replace( '.css', $index . '.css', $this->custom_filename );

            if( !is_dir( $directory ) ){

                wp_mkdir_p( $directory );
            }

            if ( !is_writable( dirname( $file ) ) ) {
                return false;
            }


            return file_put_contents( $file, $css, FS_CHMOD_FILE );
        }

        /**get the css rules form theme option
         * @author YITHEMES
         * @since 1.0.0
         */
        public function get_theme_options_css_rules()
        {
            $styles = array( 'style1', 'style2', 'style3', 'style4' );

            foreach ( $styles as $style ) {

                $ywcca_options_rules = include( YWCCA_DIR . 'plugin-options/' . $style . '-options.php' );

                foreach ( $ywcca_options_rules as $sections => $fields ) {

                    foreach ( $fields as $field ) {

                        if ( isset( $field['id'] ) )
                            $this->add_by_option( $field, get_option( $field['id'] ), $field );

                    }
                }
            }
        }

        /**
         * return the stylesheet name of dynamics css
         *@author YITHEMES
         *@since 1.0.0
         */
        private function _get_stylesheet_name()
        {
            global $wpdb;
            $index = $wpdb->blogid != 0 ? '-' . $wpdb->blogid : '';
            return str_replace( '.css', $index . '.css', $this->custom_filename );
        }

        /**
         * Css Option Parse -> Transform a panel options in a css rules
         *
         * @param $option string
         * @param $value string
         * @param $options mixed array
         *
         * @return mixed
         * @since  1.0.0
         * @access public
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function add_by_option( $option, $value, $options )
        {

            if ( !isset( $option['style'] ) ) {
                return;
            }

            // used to store the properties of the rules
            $args = array();

            if ( isset( $option['style']['selectors'] ) ) {
                $style = array(
                    array(
                        'selectors' => $option['style']['selectors'],
                        'properties' => $option['style']['properties']
                    )
                );
            } elseif ( isset($option['variations'] ) ) {
                $style = array($option['style']);
            } else {
                $style = $option['style'];
            }

            foreach ( $style as $style_option ) {
                $args = array();
                $option['style'] = $style_option;

                if ( $option['type'] == 'color' ) {

                    $properties = explode( ',', $option['style']['properties'] );

                    foreach ( $properties as $property )
                        $args[$property] = $value;

                    $this->add($option['style']['selectors'], $args);

                } elseif ( $option['type'] == 'bgpreview' ) {

                    $this->add( $option['style']['selectors'], array( 'background' => "{$value['color']} url('{$value['image']}')" ) );

                } elseif ( $option['type'] == 'typography' ) {

                    if ( isset( $value['size'] ) && isset( $value['unit'] ) ) {

                        $args['font-size'] = $value['size'] . $value['unit'];
                    }

                    if ( isset( $value['color'] ) ) {
                        $args['color'] = $value['color'];
                    }

                    if ( isset( $value['background'] ) ) {
                        $args['background'] = $value['background'];
                    }

                    if ( isset($value['style'] ) ) {

                        switch ( $value['style'] ) {

                            case 'bold' :
                                $args['font-style'] = 'normal';
                                $args['font-weight'] = '700';
                                break;

                            case 'extra-bold' :
                                $args['font-style'] = 'normal';
                                $args['font-weight'] = '800';
                                break;

                            case 'italic' :
                                $args['font-style'] = 'italic';
                                $args['font-weight'] = 'normal';
                                break;

                            case 'bold-italic' :
                                $args['font-style'] = 'italic';
                                $args['font-weight'] = '700';
                                break;

                            case 'regular' :
                            case 'normal' :
                                $args['font-style'] = 'normal';
                                $args['font-weight'] = '400';
                                break;

                            default:
                                if (is_numeric($value['style'])) {
                                    $args['font-style'] = 'normal';
                                    $args['font-weight'] = $value['style'];
                                } else {
                                    $args['font-style'] = 'italic';
                                    $args['font-weight'] = str_replace('italic', '', $value['style']);
                                }
                                break;
                        }

                    }

                    if ( isset ($value['align'] ) ) {
                        $args['text-align'] = $value['align'];
                    }

                    if ( isset ( $value['transform'] ) ) {
                        $args['text-transform'] = $value['transform'];
                    }

                    $this->add( $option['style']['selectors'], $args );

                } elseif ( $option['type'] == 'upload' && $value ) {

                    $this->add( $option['style']['selectors'], array( $option['style']['properties'] => "url('$value')" ) );

                } elseif ( $option['type'] == 'number' ) {

                    $this->add( $option['style']['selectors'], array( $option['style']['properties'] => "{$value}px" ) );

                } elseif ( $option['type'] == 'select' ) {

                    $this->add( $option['style']['selectors'], array( $option['style']['properties'] => "$value" ) );
                }
            }

        }

        /**
         * Add the rule css
         *
         * @param string $rule
         * @param array $args
         *
         * @return bool
         * @since  1.0.0
         * @access public
         * @author Andrea Grillo <andrea.grillo@yithemes.com>
         */
        public function add( $rule, $args = array() )
        {

            if ( isset( $this->_rules[$rule] ) ) {
                $this->_rules[$rule] = array_merge( $this->_rules[$rule], $args );
            } else {
                $this->_rules[$rule] = $args;
            }
        }

        /**
         * Update the dynamic style
         * @author YITHEMES
         * @since 1.0.0
         *
         */
        public function update_dynamics_css() {
            global $pagenow;
           // if ( isset( $_GET["page"] ) && $_GET["page"] == $this->_panel_page ) {
                $this->write_dynamic_css();

           // }
        }


        /**
         *Add shortcode button to TinyMCE editor, adding filter on mce_external_plugins
         * @author YITHEMES
         * @since 1.0.0
         * @use admin_init
         *
         */
        public function ywcca_add_shortcodes_button(){

            if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
                return;

            }
            if ( get_user_option( 'rich_editing' ) == 'true' ) {
                add_filter( 'mce_external_plugins', array( &$this, 'ywcca_add_shortcodes_tinymce_plugin' ) );
                add_filter( 'mce_buttons', array( &$this, 'ywcca_register_shortcodes_button' ) );

            }
        }

        /**
         * Add a script to TinyMCE script list
         *
         * @since   1.0.0
         *
         * @param   $plugin_array
         *
         * @return  array
         * @author  Alberto Ruggiero
         */
        public function ywcca_add_shortcodes_tinymce_plugin( $plugin_array ) {

            $plugin_array['ywcca_shortcode'] = YWCCA_ASSETS_URL . 'js/ywcca-tinymce' .$this->suffix . '.js';

            return $plugin_array;
        }

        /**
         * Make TinyMCE know a new button was included in its toolbar
         *
         * @since   1.0.0
         *
         * @param   $buttons
         *
         * @return  array()
         * @author  Alberto Ruggiero
         */
        public function ywcca_register_shortcodes_button( $buttons ) {

            array_push( $buttons, "|", "ywcca_shortcode" );

            return $buttons;

        }

        /**
         * The markup of shortcode
         *
         * @since   1.0.0
         *
         * @param   $context
         *
         * @return  mixed
         * @author  Alberto Ruggiero
         */
        public function ywcca_media_buttons_context( $context ) {

            $out = '<a id="ywcca_shortcode" style="display:none" href="#" class="hide-if-no-js" title="' . __( 'Add YITH WooCommerce Category Accordion shortcode', 'yith-woocommerce-category-accordion' ) . '"></a>';

            return $context . $out;

        }

        /**
         * Add quicktags to visual editor
         *
         * @since   1.0.0
         * @return  void
         * @author  Alberto Ruggiero
         */
        public function ywcca_add_quicktags() {

            global $post_ID, $temp_ID;

            $query_args   = array(
                'post_id'   => (int) ( 0 == $post_ID ? $temp_ID : $post_ID ),
                'KeepThis'  => true,
                'TB_iframe' => true
            );
            $lightbox_url = esc_url( add_query_arg( $query_args, YWCCA_URL . '/templates/admin/lightbox.php' ) );



            ?>
            <script type="text/javascript">

                if ( window.QTags !== undefined ) {
                    QTags.addButton( 'ywcca_shortcode', 'add ywcca shortcode', function () {
                        jQuery('#ywcca_shortcode').click()
                    } );
                }

                jQuery('#ywcca_shortcode').on( 'click', function () {

                    tb_show('Add YITH WooCommerce Category Accordion shortcode', '<?php echo $lightbox_url ?>');

                    ywcca_resize_thickbox(450,500);

                });

            </script>
        <?php
        }
        
        public function add_premium_admin_tabs( $tabs ){
            
            unset( $tabs['premium-landing'] );
            $tabs['settings']   =   __( 'Settings', 'yith-woocommerce-category-accordion' );
            $tabs['style1']     =   __( 'Style 1', 'yith-woocommerce-category-accordion' );
            $tabs['style2']     =   __( 'Style 2', 'yith-woocommerce-category-accordion' );
            $tabs['style3']     =   __( 'Style 3', 'yith-woocommerce-category-accordion' );
            $tabs['style4']     =   __( 'Style 4', 'yith-woocommerce-category-accordion' );
            
            return $tabs;
        }

    }
}