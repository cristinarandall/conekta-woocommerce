  jQuery(function($) {
    Conekta.setPublishableKey('<?= $this->publishable_key ?>');
    var $form = $('.checkout');


var conektaErrorResponseHandler = function(response) {
      $form.find('.payment-errors').text(response.message);
      $form.unblock();
 };
    var conektaResponseHandler = function(response) {
      $form.append($('<input type="hidden" name="conektaToken" />').val(response.id));
      $form.submit();
  };
    $('body').on('click', '.checkout input:submit', function(){
      // Make sure there's not an old token on the form
      $('.checkout').find('[name=conektaToken]').remove()
    })

    // Bind to the checkout_place_order event to add the token
    $('.checkout').bind('checkout_place_order', function(e){
      $form.find('.payment-errors').html('');
      $form.block({message: null,overlayCSS: {background: "#fff url(" + woocommerce_params.ajax_loader_url + ") no-repeat center",backgroundSize: "16px 16px",opacity: .6}});

      // Pass if we have a token
      if( $form.find('[name=conektaToken]').length)
        return true;

      Conekta.token.create($form,conektaSuccessResponseHandler, conektaErrorResponseHandler)
      return false;
    });
  });
