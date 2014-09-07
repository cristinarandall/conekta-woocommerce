 var initConektaCheckout = function(){

    jQuery(function($) {

    var $form = $('form.checkout,form#order_review');
           
    var conektaErrorResponseHandler  = function(response) {
           $form.find('.payment-errors').text(response.message);
           $form.unblock();
 
             };
           
    var conektaSuccessResponseHandler = function(response) {
      $form.append($('<input type="hidden" name="conektaToken" />').val(response.id));
      $form.submit();

  };

    $('body').on('click', '#place_order,form#order_review input:submit', function(){
      if(jQuery('.payment_methods input:checked').val() !== 'ConektaCheckout')
      {
        return true;
      }
      Conekta.setPublishableKey($('#conekta_pub_key').data('publishablekey'));
      Conekta.token.create($form, conektaSuccessResponseHandler, conektaErrorResponseHandler);
      return false;
    });


    $('body').on('click', '#place_order,form.checkout input:submit', function(){
      if(jQuery('.payment_methods input:checked').val() !== 'ConektaCheckout')
      {
        return true;
      }
      $('form.checkout').find('[name=conektaToken]').remove()
    })

    $('form.checkout').bind('#place_order,checkout_place_order_ConektaCheckout', function(e){
      if($('input[name=payment_method]:checked').val() != 'ConektaCheckout'){
          return true;
      }

      $form.find('.payment-errors').html('');
      $form.block({message: null,overlayCSS: {background: "#fff url(" + woocommerce_params.ajax_loader_url + ") no-repeat center",backgroundSize: "16px 16px",opacity: .6}});

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
window.setInterval(function(){initConektaCheckout()}, 1000);
