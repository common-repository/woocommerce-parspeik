<?php
/**
 * Shipping Calculator
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.8
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;


?>

<?php do_action( 'woocommerce_before_shipping_calculator' ); ?>

<form class="shipping_calculator" action="<?php echo esc_url( $woocommerce->cart->get_cart_url() ); ?>" method="post" style="width: 65%!important;">

	<p class="selectcitynotice" style="display:none;padding: 10px 5px; background: #F2EABB; font:12px tahoma;border-radius: 5px;">استان و شهر خود را انتخاب کنید تا روش های ارسال ، هزینه هر روش و  جمع کل سفارش شما محاسبه شود</p>

	<section class="shipping-calculator">

        <script type="text/javascript">

function submitchform(){
     jQuery('input.checkout-button').click();
    }
jQuery(document).ready(function($) {
    function select_list_sync_to_input(iid, iinput) {
        $("select#"+iid).change(function(){
            var val_now = $("select#"+iid+" option:selected").val();
            if(val_now != 0){
                $("input#"+iinput).val($("select#"+iid+" option:selected").val()+'-'+$("select#"+iid+" option:selected").text());  
            }else{
                $("input#"+iinput).val('');
            }
        
        });
    }
    
    select_list_sync_to_input('id_province', 'shipping_state');
    select_list_sync_to_input('id_city', 'shipping_city');
    
    function set_initial_val(iid, ival) {
        jQuery("select#"+iid).val(ival).trigger('onchange');
    }
    
    
    <?php 
    
    $my_state = $woocommerce->customer->get_shipping_state();
    $my_state = explode('-', $my_state);
    if(isset($my_state) && intval($my_state[0]) > 0 ){
        ?>
        set_initial_val('id_province', <?php echo $my_state[0]; ?>);
        <?php
    }
    
    $my_city = $woocommerce->customer->get_shipping_city();
    $my_city = explode('-', $my_city);
    if(isset($my_city) && intval($my_city[0]) > 0 ){
        ?>
        set_initial_val('id_city', <?php echo $my_city[0]; ?>);
        <?php
    }
    
    ?>

});
    </script>
    <style>
    select{font:12px tahoma; padding: 2px 1px;}
    </style>
    <p class="form-row form-row-last" id="billing_state_field" data-o_class="form-row form-row-last address-field"><label for="billing_state" class="">استان<abbr class="required" title="ضروری">*</abbr></label>
        <select name='id_province' id='id_province' onchange="p24_load_cities_list(this.value, 'id_city');">
			<option value='0'>استان را انتخاب نمایید ...</option>
		</select>
        <input type="hidden" name="calc_shipping_state" id="shipping_state" value="" />
    </p>
    <script type='text/javascript'>p24_load_province_list('id_province');</script>
    
    <p class="form-row form-row-first address-field  update_totals_on_change" id="billing_city_field" data-o_class="form-row form-row-first address-field"><label for="billing_city" class="">شهر <abbr class="required" title="ضروری">*</abbr></label>
        <select name='id_city' id='id_city'>
			<option value='0'>ابتدا استان را انتخاب نمایید ...</option>
		</select>
        <input type="hidden" name="calc_shipping_city" id="shipping_city" value="" />
	</p>

		<p><button type="submit" name="calc_shipping" value="1" class="button" style="width: 60%;"><?php echo $have_city ? 'محاسبه مجدد جمع کل' : 'محاسبه جمع کل'; ?></button></p>

		<?php wp_nonce_field( 'woocommerce-cart' ); ?>
	</section>
        
        <!--<input type="hidden" name="calc_shipping_country" id="shipping_country" value="IR" />
        <input type="hidden" name="calc_shipping_postcode" value="" />-->
</form>

<?php do_action( 'woocommerce_after_shipping_calculator' ); ?>