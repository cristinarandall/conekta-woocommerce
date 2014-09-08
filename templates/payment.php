<?php
/*
 * Title   : Conekta Payment extension for WooCommerce
 * Author  : Cristina Randall
 * Url     : https://github.com/cristinarandall/conekta-woocommerce 
 */
?>
<div id="conekta_pub_key" class="hidden" style="display:none" data-publishablekey="<?=$this->publishable_key ?>"> </div>
<div class="clear"></div>
<span style="width: 100%; float: left; color: red;" class='payment-errors required'></span>
<p class="form-row form-row-first">
  <label>Card Number <span class="required">*</span></label>
  <input class="input-text" type="text" size="19" maxlength="19" data-conekta="card[number]" />
</p>
<p class="form-row form-row-last">
<label> Cardholder Name <span class="required">*</span></label>
<input type="text" data-conekta="card[name]" class="input-text" />
</p>
<div class="clear"></div>
<p class="form-row form-row-first">
  <label>Expiration Month <span class="required">*</span></label>
  <input type="text" data-conekta="card[exp_month]" class="input-text" />
</p>
<p class="form-row form-row-last">
  <label>Expiration Year  <span class="required">*</span></label>
  <input type="text" data-conekta="card[exp_year]" class="input-text" />
</p>
<div class="clear"></div>
<p class="form-row form-row-first">
    <label>Card Verification Number <span class="required">*</span></label>
    <input class="input-text" type="text" maxlength="4" data-conekta="card[cvc]" value=""  style="border-radius:6px"/>
</p>
<div class="clear"></div>

<script>

  var initConektaCheckout = function(){
    jQuery(function($) {
    var $form = $('form.checkout,form#order_review');

    var conektaMap = {

        billing_address_1:  'address_line1',
        billing_address_2:  'address_line2',
        billing_city:       'address_city',
        billing_country:    'address_country',
        billing_state:      'address_state',
        billing_postcode:   'address_zip',
    }
    var card_name = '';
    $('form.checkout').find('input[id*=billing_],select[id*=billing_]').each(function(idx,el){
        var mapped = conektaMap[el.id];
        if (mapped)
        {
            $(el).attr('data-conekta',mapped);
            
        }
    });

           var conektaErrorResponseHandler = function(response) {
           console.log("fail")

           $form.find('.payment-errors').text(response.message);
           $form.unblock();

             };
           
    var conektaSuccessResponseHandler = function(response) {
           console.log("success")

      $form.append($('<input type="hidden" name="conektaToken" />').val(response.id));
      $form.submit();

  };

    $('body').on('click', '#place_order,form#order_review input:submit', function(){
      if(jQuery('.payment_methods input:checked').val() !== 'ConektaCard')
      {
        return true;
      }
      Conekta.setPublishableKey($('#conekta_pub_key').data('publishablekey'));
      Conekta.token.create($form, conektaSuccessResponseHandler, conektaErrorResponseHandler);
      return false;
    });


    $('body').on('click', '#place_order,form.checkout input:submit', function(){
      if(jQuery('.payment_methods input:checked').val() !== 'ConektaCard')
      {
        return true;
      }
      $('form.checkout').find('[name=conektaToken]').remove()
    })

    $('form.checkout').bind('#place_order,checkout_place_order_ConektaCard', function(e){

      if($('input[name=payment_method]:checked').val() != 'ConektaCard'){
          return true;
      }

      $form.find('.payment-errors').html('');
      $form.block({message: null,overlayCSS: {background: "#fff url(" + woocommerce_params.ajax_loader_url + ") no-repeat center",backgroundSize: "16px 16px",opacity: .6}});

      // Pass if we have a token
      if( $form.find('[name=conektaToken]').length)
        return true;

      Conekta.setPublishableKey($('#conekta_pub_key').data('publishablekey'));
      Conekta.token.create($form, conektaSuccessResponseHandler, conektaErrorResponseHandler);
      return false;
    });
  });
};

if(typeof jQuery=='undefined')
{
    var headTag = document.getElementsByTagName("head")[0];
    var jqTag = document.createElement('script');
    jqTag.type = 'text/javascript';
    jqTag.src = 'https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js';
    jqTag.onload = initConektaCheckout;
    headTag.appendChild(jqTag);
} else {
   initConektaCheckout()
}
</script>
