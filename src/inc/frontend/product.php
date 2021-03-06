<?php
namespace MKL\PC;
/**
 *	
 *	
 * @author   Marc Lacroix
 $ 
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists('MKL\PC\Frontend_Product') ) {

	class Frontend_Product {
		
		public function __construct() {
			$this->_hooks();
			$this->options = get_option( 'mkl_pc__settings' );
			$this->button_class = isset( $this->options['mkl_pc__button_classes'] ) ? Utils::sanitize_html_classes( $this->options['mkl_pc__button_classes'] ) : 'btn btn-primary';
		}

		private function _hooks() {
			add_action( 'wp' , array( &$this, 'wp_init' ) ); 
			add_filter( 'woocommerce_product_add_to_cart_text', array( &$this, 'add_to_cart_text' ), 30, 2 ); 
			add_filter( 'woocommerce_product_add_to_cart_url',array( &$this, 'add_to_cart_link' ), 30, 2 ); 
			add_filter( 'woocommerce_product_supports', array( &$this, 'simple_product_supports' ), 10, 3 ); 
			
			// add button after form, as form will be moved.
			add_action( 'woocommerce_after_add_to_cart_form', array( &$this, 'add_configure_button' ) );
			// add hidden input to store configurator data into form
			add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'add_configure_hidden_field' ) ); 
			add_action( 'mkl_pc_frontend_configurator_footer_form',array( $this, 'configurator_form' ), 20 ); 
			add_action( 'mkl_pc_templates_empty_viewer', array( &$this, 'variable_empty_configurator_content'), 20 );
			add_action( 'wp_footer', array( &$this, 'print_product_configuration' ) );
		}

		public function add_to_cart_text( $text, $product ) {
			if ( mkl_pc_is_configurable( $product->get_id() ) && $product->get_type() == 'simple' ) {
				$text = __( 'Select options', 'woocommerce' );
			} 
			return $text;

		}
		// Changes Removes add to cart link for simple + configurable products 
		// From add to cart link to premalink
		public function add_to_cart_link( $link, $product ) { 
			//( is_shop() || is_product_category() ) && 
			if ( mkl_pc_is_configurable( $product->get_id() ) && $product->get_type() == 'simple' ) {
				$link = $product->get_permalink();
			}
			return $link;
		}

		public function simple_product_supports( $value, $feature, $product ) {
			if ( mkl_pc_is_configurable( $product->get_id() ) && $product->get_type() == 'simple' ) {
				if ( $feature == 'ajax_add_to_cart' ) $value = false;
			}
			return $value;
		}

		public function add_configure_button() { 
			global $product;
			if ( mkl_pc_is_configurable( get_the_id() ) ) {
				$options = get_option( 'mkl_pc__settings' );
				if ( isset( $options['mkl_pc__button_label'] ) && $options['mkl_pc__button_label'] ) {
					$label = $options['mkl_pc__button_label']; 
				} else {
					$label = __( 'Configure', 'product-configurator-for-woocommerce' );
				}
				echo apply_filters( 'mkl_pc_configure_button', '<button class="configure-product configure-product-'. $product->get_type().' '. $this->button_class .'" type="button">'. $label .'</button>' );
			}
		}

		public function add_configure_hidden_field() {
			if ( mkl_pc_is_configurable( get_the_id() ) ) {
				echo '<input type="hidden" name="pc_configurator_data">'; 
			}
		}

		public function configurator_form() {
			global $product;
			if ( $product && ! $product->is_sold_individually() ) {
				woocommerce_quantity_input( array(
					'min_value'   => apply_filters( 'woocommerce_quantity_input_min', 1, $product ),
					'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->backorders_allowed() ? '' : $product->get_stock_quantity(), $product ),
					'input_value' => ( isset( $_POST['quantity'] ) ? wc_stock_amount( intval( $_POST['quantity'] ) ) : 1 )
				) );
			}
			?>
				<# if ( data.show_form ) { #>
					<form class="cart" method="post" enctype='multipart/form-data'>
						<input type="hidden" name="pc_configurator_data">
						<input type="hidden" name="add-to-cart" value="{{data.product_id}}">
						<# if ( data.show_qty ) { #>
							<?php woocommerce_quantity_input(); ?>
						<# } #>
					</form>
				<# } #>

				<button type="button" class="<?php echo $this->button_class ?> configurator-add-to-cart">
					<?php echo $this->get_cart_icon(); ?>
					<span><?php echo apply_filters( 'mkl_pc/add_to_cart_button/label', __( 'Add to cart', 'woocommerce' ) ); ?></span>
				</button>
			<?php
		}

		private function get_cart_icon() {
			return apply_filters( 'mkl_pc/get_cart_icon', '<svg xmlns="http://www.w3.org/2000/svg" width="37.118" height="33" viewBox="0 0 37.118 33"><path id="Path_2" data-name="Path 2" d="M34.031-9.475a1.506,1.506,0,0,1-.548.9,1.5,1.5,0,0,1-.935.322H13.664l.387,2.062H31.389a1.406,1.406,0,0,1,1.16.58,1.56,1.56,0,0,1,.322,1.289l-.387,1.611A3.491,3.491,0,0,1,34-1.386a3.5,3.5,0,0,1,.548,1.9,3.474,3.474,0,0,1-1.063,2.546,3.579,3.579,0,0,1-5.092,0A3.511,3.511,0,0,1,27.328.483a3.357,3.357,0,0,1,1.1-2.546H14.889a3.357,3.357,0,0,1,1.1,2.546,3.511,3.511,0,0,1-1.063,2.578,3.579,3.579,0,0,1-5.092,0A3.474,3.474,0,0,1,8.766.516a3.551,3.551,0,0,1,.483-1.8A3.8,3.8,0,0,1,10.57-2.643L6.059-24.75H1.547a1.492,1.492,0,0,1-1.1-.451A1.492,1.492,0,0,1,0-26.3v-1.031a1.492,1.492,0,0,1,.451-1.1,1.492,1.492,0,0,1,1.1-.451H8.186a1.411,1.411,0,0,1,.935.354,1.637,1.637,0,0,1,.548.87l.58,2.9h25.33a1.469,1.469,0,0,1,1.225.58,1.4,1.4,0,0,1,.258,1.289Z" transform="translate(0 28.875)" fill="#707070"/></svg>' );
		}
		public function print_product_configuration(){
			if ( ! mkl_pc()->frontend->load_configurator_on_page() ) return;
			include( 'views/html-product-configurator-templates.php' );
		}

		public function variable_empty_configurator_content() {
			_e( 'Please select a variation to configure', 'product-configurator-for-woocommerce' );
		}

		public function body_class( $classes ) {
			// global $post;
			if ( is_product() ) {
				
				if ( mkl_pc_is_configurable() ) {
					$classes[] = 'is_configurable';
				}
			}
			return $classes;
		}

		public function wp_init() {
			add_filter('body_class', array($this, 'body_class') ) ;			
		}



	}
}