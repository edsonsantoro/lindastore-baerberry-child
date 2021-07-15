
<?php
/**
 * Theme functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 */

/*
 * If your child theme has more than one .css file (eg. ie.css, style.css, main.css) then
 * you will have to make sure to maintain all of the parent theme dependencies.
 *
 * Make sure you're using the correct handle for loading the parent theme's styles.
 * Failure to use the proper tag will result in a CSS file needlessly being loaded twice.
 * This will usually not affect the site appearance, but it's inefficient and extends your page's loading time.
 *
 * @link https://developer.wordpress.org/themes/advanced-topics/child-themes/
 */
function barberry_child_enqueue_styles() {
    wp_enqueue_style( 'barberry-style' , get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'barberry-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'barberry-style' ),
        wp_get_theme()->get('Version')
    );
	if ( is_rtl() ) {
	wp_enqueue_style(  'barberry-rtl',  get_template_directory_uri(). '/rtl.css', array(), '1', 'screen' );
	}
}

add_action(  'wp_enqueue_scripts', 'barberry_child_enqueue_styles', 100 );

#----------------------------------------------------------------#
# Definir usuário como convidado para pedidos criados pela equipe
# possibilitando que qualquer pessoa com o link efetue pagamento
#----------------------------------------------------------------#

function update_customer_user( $post_id, $post, $update ) {

    // Não vamos rodar a função se o $post_id não existe OU se o post type não foi ORDER OU se estiverem atualizando $update = 1
    if ( ! $post_id || get_post_type( $post_id ) != 'shop_order' ) {
        return;
    }

    $order = wc_get_order( $post_id );

    // Se o pedido foi criado manualmente, vamos reduzir o estoque.
    if($order && $order->status == "pending") {
	wc_reduce_stock_levels($post_id);
    }

    // Se temos o pedido e o consumidor não é convidade (0) e se o usuário existe (!= false) mudamos ele para convidado
    if ( $order && $order->customer_id > 0 && get_user_by('id', $order->customer_id) != false ){

	// Vamos tentar atualizar o consumidor para convidado caso o pedido tenha sido criado por funcionários.
        $role = array_values( get_userdata( $order->customer_id )->roles )[0];
        if($role = 'shop_manager' || $role = 'shop_accountant' || $role = 'shop_vendor' || $role = 'outlet_manager' || $role = 'shop_worker' || $role = 'administrator') {
	    update_post_meta($post_id, '_customer_user', 0);
	}

    }
}

function update_customer_user2( $order_id ) {

    // Não vamos rodar a função se o $post_id não existe OU se o post type não foi ORDER OU se estiverem atualizando $update = 1
    if ( ! $order_id ) {
	return;

    }

    $order = wc_get_order( $order_id );

    // Se temos o pedido e o consumidor não é convidade (0) e se o usuário existe (!= false) mudamos ele para convidado
    if ( $order && $order->customer_id > 0 && get_user_by('id', $order->customer_id) != false ){

        // Vamos tentar atualizar o consumidor para convidado caso o pedido tenha sido criado por funcionários.
        $role = array_values( get_userdata( $order->customer_id )->roles )[0];
        if($role = 'shop_manager' || $role = 'shop_accountant' || $role = 'shop_vendor' || $role = 'outlet_manager' || $role = 'shop_worker' || $role = 'administrator') {
            update_post_meta($post_id, '_customer_user', 0);
        }

    }
}
add_action( 'wp_insert_post', 'update_customer_user', 10, 3 );
add_action( 'woocommerce_checkout_process', 'update_customer_user2', 10, 1 );

#-----------------------------------------------------------------#
# Devolver ao estoque para pedidos cancelados
#-----------------------------------------------------------------#

add_action('woocommerce_order_status_pending_to_cancelled', 'increase_stock_levels', 10, 2);
add_action('woocommerce_order_status_on-hold_to_cancelled', 'increase_stock_levels', 10, 2);
add_action('woocommerce_order_status_processing_to_cancelled', 'increase_stock_levels', 10, 2);
add_action('woocommerce_order_status_completed_to_cancelled', 'increase_stock_levels', 10, 2);
add_action('woocommerce_order_status_reservado_to_cancelled', 'increase_stock_levels', 10, 2);

function increase_stock_levels($id, $instance){
	wc_increase_stock_levels($id);
}

#-----------------------------------------------------------------#
# Reduzir estoque para pedidos criados manualmente que estão em pagamento pendente.
#-----------------------------------------------------------------#

add_action('woocommerce_order_status_pending', 'reduce_stock_for_manual_orders', 10, 1);

function reduce_stock_for_manual_orders($order_id){
	wc_reduce_stock_levels($order_id);
}

#-----------------------------------------------------------------#
# Talvez Reduzir Estoque
#-----------------------------------------------------------------#

function reduce_stock_levels($order_id, $instance) {
	wc_maybe_reduce_stock_levels($order_id);
}

#-----------------------------------------------------------------#
# Quando se muda um pedido de status completo para aguardando, o WooCommerce devolve os itens do pedido para o estoque. Não queremos este comportamento, queremos que o WooCommerce segure o estoque. Para isso temos que trazer de volta o que foi retirado.
#-----------------------------------------------------------------#

add_action('woocommerce_order_status_on-hold_to_pending', 'reduce_stock_levels', 10, 2);
add_action('woocommerce_order_status_processing_to_pending', 'reduce_stock_levels', 10, 2);
add_action('woocommerce_order_status_completed_to_pending', 'reduce_stock_levels', 10, 2);
add_action('woocommerce_order_status_reservado_to_pending', 'reduce_stock_levels', 10, 2);
add_action('woocommerce_order_status_reserved_to_pending', 'reduce_stock_levels', 10, 2);

#-----------------------------------------------------------------#
# Remover color picker 
#-----------------------------------------------------------------#

if( is_admin() ){
        //add_action( 'wp_default_scripts', 'wp_default_custom_scripts' );
        function wp_default_custom_scripts( $scripts ){
                //$scripts->add( 'wp-color-picker', "/wp-admin/js/color-picker$suffix.js", array( 'iris' ), false, 1 );
                did_action( 'init' ) && $scripts->localize(
                        'wp-color-picker',
                        'wpColorPickerL10n',
                        array(
                                'clear'            => __( 'Clear' ),
                                'clearAriaLabel'   => __( 'Clear color' ),
                                'defaultString'    => __( 'Default' ),
                                'defaultAriaLabel' => __( 'Select default color' ),
                                'pick'             => __( 'Select Color' ),
                                'defaultLabel'     => __( 'Color value' ),
                        )
                );
        }
}

#-----------------------------------------------------------------#
# Bloquear a saída da página de pedidos
#-----------------------------------------------------------------#

add_action( 'woocommerce_admin_order_data_after_order_details', 'woo_nav_away');

function woo_nav_away(){
	echo "<script type='text/javascript'>

	var woo_nav_away = function(event) {
		// Cancel the event as stated by the standard.
		event.preventDefault();
		// Chrome requires returnValue to be set.
		event.returnValue = '';
	}

	window.addEventListener('beforeunload', woo_nav_away);

	jQuery('button[class=wc-reload], button[name=save], button[class=do-manual-refund], p[class=invoice-actions] > a[href*=update').on('click', function(event){
		window.removeEventListener('beforeunload', woo_nav_away);
	});

	</script>";
}


#-----------------------------------------------------------------#
# Remover Campo de Reserva de Horário para produtos na pré-venda
#-----------------------------------------------------------------#

//add_action('woocommerce_before_checkout_form', 'remove_shipping_schedule');
function remove_shipping_schedule() {
    foreach ( WC()->cart->get_cart() as $cart_item ) {
	$product = $cart_item['data'];
	if(!empty($product)){;
		$terms = get_the_terms( $product->parent_id, 'product_cat' );
		foreach($terms as $category){
			if($category->name == 'Pré-Venda'){
				remove_filters_with_method_name( 'woocommerce_before_order_notes', 'delivery_date_designation', 10 );
				add_action( 'woocommerce_before_order_notes', 'pre_order_notice', 9 );
			}
		}
	}
    }
}

function pre_order_notice() {
	echo "<h3>".__('Nota', 'woocommerce-for-japan' )."</h3>";
	echo "<p>".__('Um ou mais produtos que você adicionou ao carrinho estão em pré-venda, portanto, não podemos aceitar agendamento para entrega para estes produtos. Os produtos sob reserva estão agendados para chegarem em nosso estoque no dia 30/11. Assim que seus produtos chegarem, enviaremos o mais rápido possível. Você receberá uma notificação via email sobre o envio. Muito obrigada pela sua compreensão!')."</p>";
}


add_action("init", function () {
    // removing the woocommerce hook
    remove_action('woocommerce_order_status_pending', 'wc_maybe_increase_stock_levels');
});

#-----------------------------------------------------------------#
# Remover ações do Wordpress
#-----------------------------------------------------------------#

function remove_filters_with_method_name( $hook_name = '', $method_name = '', $priority = 0 ) {
    global $wp_filter;
    // Take only filters on right hook name and priority
    if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {
        return false;
    }
    // Loop on filters registered
    foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
        // Test if filter is an array ! (always for class/method)
        if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
            // Test if object is a class and method is equal to param !
            if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && $filter_array['function'][1] == $method_name ) {
                // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
                if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {
                    unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                } else {
                    unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                }
            }
        }
    }
    return false;
}


#-----------------------------------------------------------------#
# Trocar texto de em estoque para disponível para reserva 
#-----------------------------------------------------------------#

//add_filter( 'woocommerce_get_availability_text', 'bbloomer_custom_get_availability_text', 99, 2 );
function bbloomer_custom_get_availability_text( $availability, $product ) {
  $stock = $product->get_stock_quantity();
  if ( $product->is_in_stock() && $product->managing_stock() ){
	$terms = get_the_terms( $product->parent_id, 'product_cat' );
                foreach($terms as $category){
                        if($category->name == 'Pré-Venda'){
				$availability = __( $stock . ' disponíveis para reserva.', 'woocommerce' );
                        }
                }
  }
  return $availability;
}

#-----------------------------------------------------------------#
# Share Button
#-----------------------------------------------------------------#

add_action('woocommerce_single_product_summary', 'sds_barberry_share', 10);

function sds_barberry_share() {
        global $post, $product;
        $src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), false, '');

        if ( ! TDL_Opt::getOption('product_share') ) {
            return;
        }

        echo "<span style='font-weight:400;font-size:17px;display:block;margin-bottom:30px;'>Data estimada de envio: " . do_shortcode('[wcst_show_estimated_date]') . "</span>";
    ?>

    <div class="box-share-master-container" data-name="<?php esc_attr_e( 'Share', 'woocommerce' )?>" data-share-elem="<?php echo implode(',', TDL_Opt::getOption('social_sharing'));?>">
        <a href="javascript:;" class="social-sharing" data-shareimg="<?php echo esc_url($src[0]) ?>" data-name="<?php echo get_the_title(); ?>">
            <?php esc_attr_e('Share', 'woocommerce'); ?>
        </a>
    </div>
<?php }

#-----------------------------------------------------------------#
# Remove old share buttom
#-----------------------------------------------------------------#
if ( !function_exists('barberry_share')){
	function barberry_share() {
	}
}

#-----------------------------------------------------------------#
# Add freight box and links
#-----------------------------------------------------------------#

add_action( 'woocommerce_single_product_summary', 'add_freight_data', 10 );
add_action('woocommerce_after_single_product_summary', 'add_freight_box', 10);

function add_freight_data() {

        $guide = "";
        if(ICL_LANGUAGE_CODE == 'pt-br'){
                $guide = get_post(30908);
        } elseif (ICL_LANGUAGE_CODE == 'ja') {
                $guideid = apply_filters('wpml_object_id', 30908, 'barberry_size_guide', FALSE,  ICL_LANGUAGE_CODE);
                $guide = get_post($guideid);
        } else {
                $guide = get_post(30908);
        }

	$slug = str_replace("-", "", $guide->post_name);
	echo "<div id='".$slug."' class='guide-links' >";
	echo "<a data-open='".$slug."Modal' class='barberry-open-popup barberry-".$slug."-btn' aria-controls='".$slug."Modal' aria-haspopup='true' tabindex='1'>" . $guide->post_title ."</a>";
	echo "</div>";
}

function add_freight_box() {

        $guide = "";
        if(ICL_LANGUAGE_CODE == 'pt-br'){
                $guide = get_post(30908);
        } elseif (ICL_LANGUAGE_CODE == 'ja') {
                $guideid = apply_filters('wpml_object_id', 30908, 'barberry_size_guide', FALSE,  ICL_LANGUAGE_CODE);
                $guide = get_post($guideid);
        } else {
                $guide = get_post(30908);
        }

	$slug = str_replace("-", "", $guide->post_name);
?>
<style type="text/css">
	.guide-links {
		display: inline-block;
    	margin-top: 0;
	}

	.guide-links a {
		position: relative;
		font-weight: 400;
		font-size: 1rem;
		line-height: 1rem;
		margin-right: 1.25rem;
		padding-left: 1.5rem;
		display: flex;
		align-items: center;
	}
	
	.guide-links a:before {
		opacity: 1;
		font-family: 'Barberry' !important;
		speak: none;
		font-style: normal;
		font-weight: normal;
		font-variant: normal;
		text-transform: none;
		line-height: 1;
		-webkit-font-smoothing: antialiased;
		-moz-osx-font-smoothing: grayscale;
		content: "";
		margin-right: 0.5rem;
		font-size: 1rem;
		position: absolute;
		top: -2px;
		left: 0;
	}


	#<?php echo $slug; ?>Modal .barberry-<?php echo $slug; ?>-title {
		border-color: #000000;
		color: #000000;
		display: -webkit-box;
	    display: -ms-flexbox;
	    display: flex;
	    -webkit-box-align: center;
	    -ms-flex-align: center;
	    align-items: center;
	    -webkit-box-pack: justify;
	    -ms-flex-pack: justify;
	    justify-content: space-between;
	    -ms-flex-wrap: wrap;
	    flex-wrap: wrap;
	    margin-bottom: 25px;
	    padding-bottom: 10px;
	    line-height: 1;
	    border-bottom: 3px solid;
	}

	#<?php echo $slug; ?>Modal {
	    max-width: 800px;
	    height: 100%;
	    padding: 0;
	}

	#<?php echo $slug; ?>Modal .nano-content {
		padding: 30px 30px;
	}	

	.<?php echo $slug; ?>-link a {
	    position: relative;
	    font-weight: 400;
	    font-size: 1rem;
	    line-height: 1rem;
	    padding-left: 1.5rem;
	    display: flex;
	    align-items: center;
	}

	.<?php echo $slug; ?>-link a:before {
	    opacity: 1;
	    font-family: 'Barberry' !important;
	    speak: none;
	    font-style: normal;
	    font-weight: normal;
	    font-variant: normal;
	    text-transform: none;
	    line-height: 1;
	    -webkit-font-smoothing: antialiased;
	    -moz-osx-font-smoothing: grayscale;
	    content: "";
	    margin-right: 0.5rem;
	    font-size: 1rem;
	    position: absolute;
	    top: -2px;
	    left: 0;
	}

	#<?php echo $slug; ?>Modal .close-icon {
	    position: absolute;
	    left: auto;
	    right: 40px;
	    top: 30px;
	}
</style>
<script>
	jQuery(function($) {
		"use strict";
		$('.barberry-<?php echo $slug; ?>-btn').on('click', function(){
			$(".nano2").nanoScroller({ iOSNativeScrolling: true });
		});
	});
</script>
<div class="reveal-overlay" style="display: none;">
	<div class="reveal" id="<?php echo $slug; ?>Modal" data-reveal="" data-close-on-click="true" data-animation-in="fade-in" data-animation-out="fade-out" role="dialog" aria-hidden="true" data-yeti-box="<?php echo $slug; ?>Modal" data-resize="<?php echo $slug; ?>Modal" data-e="55jrzj-e" tabindex="-1" style="display: none; top: 187px;" data-events="resize">
		<div class="nano2 has-scrollbar">
			<div class="nano-content" tabindex="0" style="right: -15px;">
				<h3 class="barberry-<?php echo $slug; ?>-title"><?php echo $guide->post_title; ?></h3>
				<div class="barberry-<?php echo $slug; ?>-content"><?php  echo $guide->post_content; ?></div>
				<div class="close-icon" data-close="" aria-label="Close modal">
					<span class="close-icon_top"></span>
					<span class="close-icon_bottom"></span>
				</div>
			</div>
			<div class="nano-pane" style="display: none;">
				<div class="nano-slider" style="height: 20px; transform: translate(0px, 0px);"></div>
			</div>
		</div>
	</div>
</div>
<?php
}

#--------------------------------------------------------#
# Format price on payments page
#--------------------------------------------------------#

function custom_wc_price( $returning, $price, $args, $unformatted_price ) {

    // modify the price using: $return, $price, $args, $unformatted_price

    if(is_page("finalizar-compra") OR isset($_GET['pay_for_order']) ){
    	$returning = get_woocommerce_currency_symbol() . $unformatted_price;
    }

    return $returning;

}

add_filter( 'wc_price', 'custom_wc_price', 99, 4 );
