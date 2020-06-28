<?php

if (!defined('ABSPATH')) {
    exit;
}

wp_enqueue_script( 'mask-script', plugin_dir_url(__DIR__).'js/masks.js' );
?>

<script>
var boacompra_submit = false;
function renderMethod(type) {
    var path = "<?php echo $static_path; ?>";
    //var name = 'visa'; //option.displayName;
    var code = 'visa'; //option.name.toLowerCase();
    var image = path + 'visa'; //option.images.MEDIUM.path;

    var cards = {
        amex: 'American Express',
        aura: 'Aura',
        diners: 'Diners',
        elo: 'Elo',
        hipercard: 'HiperCard',
        mastercard: 'MasterCard',
        personalcard: 'PersonalCard',
        visa: 'Visa',
    };

    var wallets = {
        pagseguro: 'PagSeguro',
        paypal: 'PayPal',
    }

    if(type == 'credit-card') {
        for(card in cards) {
            if(!jQuery('#bc_direct_credit_brand_'+card).length) {
                var image = path + card + '.png';
                li = document.createElement("li");
                li.id = 'bc_direct_credit_brand_'+card;
                li.setAttribute('data-image-src', image);
                jQuery('#bc_direct_card_flags').append(li);
                jQuery('.bc-direct-payment-li-card').show();
            }
        }
    }
    else if(type == 'postpay') {
        var image = path + 'boleto.png';
        jQuery('#bc_direct_billet_ico').attr('src', image);
        jQuery('#bc_direct_billet_ico').show();
        jQuery('.bc-direct-payment-li-billet').show();
    }
    else if(type == 'e-wallet') {
        for(wallet in wallets) {
            if(!jQuery('#bc_direct_debit_type_'+wallet).length) {
                var image = path + wallet + '.png';
                var name = wallets[wallet];
                li = document.createElement("li");
                img = document.createElement("img");
                span = document.createElement("span");
                radio = document.createElement("input");
                li.className = 'bc_direct_debit_li bank-option-box';
                radio.type = 'radio';
                radio.id = 'bc_direct_debit_type_'+wallet;
                radio.name = 'bc_direct_debit_type';
                radio.className = 'bc_direct_debit_type';
                radio.value = wallet;
                img.src = image;
                img.alt = name;
                img.title = name;
                span.innerText = name;
                li.append(radio);
                li.append(img);
                li.append(span);


                jQuery('#bc_direct_debit_flags').append(li);
                jQuery('.bc-direct-payment-li-debit').show();
            }
        }
    }
}

jQuery( 'body' ).on( 'click', '#bc-direct-payment-radio-card', function() {
    var radio_option = document.getElementById('bc-direct-payment-radio');
    var radio_card = document.getElementById('bc-direct-payment-radio-card');
    var radio_billet = document.getElementById('bc-direct-payment-radio-billet');
    var radio_debit = document.getElementById('bc-direct-payment-radio-debit');
    jQuery( '.bc-direct-payment-form' ).hide();
    jQuery( '.bc-direct-payment-form-card' ).show();
    radio_card.className = "active-tabButton";
    if(radio_billet) {
        radio_billet.className = "tabButton";
    }
    if(radio_debit) {
        radio_debit.className = "tabButton";
    }
    radio_option.value = "credit-card";
});
jQuery( 'body' ).on( 'click', '#bc-direct-payment-radio-billet', function() {
    var radio_option = document.getElementById('bc-direct-payment-radio');
    var radio_card = document.getElementById('bc-direct-payment-radio-card');
    var radio_billet = document.getElementById('bc-direct-payment-radio-billet');
    var radio_debit = document.getElementById('bc-direct-payment-radio-debit');
    jQuery( '.bc-direct-payment-form' ).hide();
    jQuery( '.bc-direct-payment-form-billet' ).show();
    radio_billet.className = "active-tabButton";
    if(radio_card) {
        radio_card.className = "tabButton";
    }
    if(radio_debit) {
        radio_debit.className = "tabButton";
    }
    radio_option.value = "postpay";
});
jQuery( 'body' ).on( 'click', '#bc-direct-payment-radio-debit', function() {
    var radio_option = document.getElementById('bc-direct-payment-radio');
    var radio_card = document.getElementById('bc-direct-payment-radio-card');
    var radio_billet = document.getElementById('bc-direct-payment-radio-billet');
    var radio_debit = document.getElementById('bc-direct-payment-radio-debit');
    jQuery( '.bc-direct-payment-form' ).hide();
    jQuery( '.bc-direct-payment-form-debit' ).show();
    jQuery( ".bc_direct_debit_type:first" ).click();
    radio_debit.className = "active-tabButton";
    if(radio_card) {
        radio_card.className = "tabButton";
    }
    if(radio_billet) {
        radio_billet.className = "tabButton";
    }
    radio_option.value = "e-wallet";
});

function ps_mask_cpf(v, fieldName)
{
    var masked_cpf = maskCpf(v);
    jQuery('#'+fieldName).val(masked_cpf);
}

function ps_mask_date(v, fieldName)
{
    var masked_date = maskDate(v);
    jQuery('#'+fieldName).val(masked_date);
}

function ps_mask_expiry(v, fieldName)
{
    var masked_expiry = maskExpiry(v);
    jQuery('#'+fieldName).val(masked_expiry);
}

function ps_mask_phone(v, fieldName)
{
    var masked_phone = maskPhone(v);
    jQuery('#'+fieldName).val(masked_phone);
}

function boaCompraFormValidator() {
    if ( window.boacompra_submit ) {
        window.boacompra_submit = false;

        return true;
    }

    if ( ! jQuery( '#payment_method_boacompra-payment' ).is( ':checked' ) ) {
        return true;
    }

    var form = jQuery( 'form.checkout, form#order_review' ),
        error           = false,
        wrapper         = '',
        errorHtml       = '',
        type            = jQuery( '#bc-direct-payment-radio', form).val(),
        brand           = jQuery( '#bc_direct_card_type', form).val(),
        name            = jQuery( '#bc_direct_name', form).val(),
        cardNumber      = jQuery( '#bc_direct_card_num', form ).val().replace( /[^\d]/g, '' ),
        cvv             = jQuery( '#bc_direct_card_cvv', form ).val(),
        expirationMonth = jQuery( '#bc_direct_card_expiry', form ).val().replace( /[^\d]/g, '' ).substr( 0, 2 ),
        expirationYear  = jQuery( '#bc_direct_card_expiry', form ).val().replace( /[^\d]/g, '' ).substr( 2 ),
        installments    = jQuery( '#bc_direct_installments', form ),

        today           = new Date();

    errorHtml += '<ul>';

    if(type === 'credit-card') {
        wrapper = jQuery( '#bc-direct-payment-form-card' );
        if ( typeof brand === 'undefined' || 'error' === brand ) {
            errorHtml += '<li>' + '<?php echo $errors['invalid_card'] ?>' + '</li>';
            error = true;
        }

        if ( name.trim().indexOf(' ') == -1 || name.trim().length < 1  ) {
            errorHtml += '<li>' + '<?php echo $errors['invalid_name'] ?>' + '</li>';
            error = true;
        }

        if( !BoaCompraValidateLuhn(cardNumber) || cardNumber.length < 13 ) {
            errorHtml += '<li>' + '<?php echo $errors['invalid_card'] ?>' + '</li>';
            error = true;
        }

        if ( 2 !== expirationMonth.length || 4 !== expirationYear.length ) {
            errorHtml += '<li>' + '<?php echo $errors['invalid_expiry'] ?>' + '</li>';
            error = true;
        }

        if ( ( 2 === expirationMonth.length && 4 === expirationYear.length ) && ( expirationMonth > 12 || expirationYear <= ( today.getFullYear() - 1 ) || expirationYear >= ( today.getFullYear() + 20 ) || ( expirationMonth < ( today.getMonth() + 2 ) && expirationYear.toString() === today.getFullYear().toString() ) ) ) {
            errorHtml += '<li>' + '<?php echo $errors['expired_card'] ?>' + '</li>';
            error = true;
        }

        if ( ( cvv.length != jQuery( '#bc_direct_card_cvv', form ).attr('maxlength') ) ) {
            errorHtml += '<li>' + '<?php echo $errors['invalid_cvv'] ?>' + '</li>';
            error = true;
        }

        if ( '' == installments.val() ) {
            errorHtml += '<li>' + '<?php echo $errors['invalid_installment'] ?>' + '</li>';
            error = true;
        }
    }

    errorHtml += '</ul>';

    if ( ! error ) {
        //corrigir loop
        window.boacompra_submit = true;
        //form.submit();
        if(type === 'credit-card') {
            var idToken = jQuery('#bc_direct_card_token');
            getCardToken(cardNumber,expirationMonth,expirationYear,brand,cvv,idToken,form,true);
        }
        else {
            return true;
        }
        return false;
    // Display the error messages.
    } else {
        BoaCompraError( errorHtml, wrapper );
    }

    return false;
}

function BoaCompraError( error, wrapper ) {
    jQuery( '.woocommerce-error', wrapper ).remove();
    wrapper.prepend( '<div class="woocommerce-error" style="margin-bottom: 0.5em !important;">' + error + '</div>' );
}

function BoaCompraValidateLuhn(s) {
    var w = s.replace(/\D/g, ""); //remove all non-digit characters

    j = w.length / 2;
    k = Math.floor(j);
    m = Math.ceil(j) - k;
    c = 0;
    for (i=0; i<k; i++) {
        a = w.charAt(i*2+m) * 2;
        c += a > 9 ? Math.floor(a/10 + a%10) : a;
    }
    for (i=0; i<k+m; i++) c += w.charAt(i*2+1-m) * 1;
    return (c%10 == 0);
}

function getBoaCompraPayment() {
    if(typeof boacompraPayment === "undefined") {
        var boacompraPayment;
        boacompraPayment = new Boacompra.PaymentMethod();
    }

    return boacompraPayment;
}

jQuery( document ).ready(function() {
    jQuery( ".bc-direct-payment-li" ).hide();
    jQuery( ".bc-direct-payment-radio:first" ).click();
    renderMethod('credit-card');
    renderMethod('postpay');
    renderMethod('e-wallet');
    getBoaCompraPayment();

    jQuery('#bc_direct_card_cvv').keyup(function(){validaBoaCompra()});
    jQuery('#bc_direct_card_num').keyup(function(){validaBoaCompra()});
    jQuery('#bc_direct_card_expiry').keyup(function(){validaBoaCompra()});
    //jQuery('#bc_direct_card_venc_month').change(function(){validaBoaCompra()});
    //jQuery('#bc_direct_card_venc_year').change(function(){validaBoaCompra()});
    var cc_num = document.querySelector('#bc_direct_card_num');
    if(cc_num) {
        cc_num.onkeyup = function (e) {
            if (this.value == this.lastValue) return;
            var caretPosition = this.selectionStart;
            var sanitizedValue = this.value.replace(/[^0-9]/gi, '');
            var parts = [];

            for (var i = 0, len = sanitizedValue.length; i < len; i += 4) {
                parts.push(sanitizedValue.substring(i, i + 4));
            }

            for (var i = caretPosition - 1; i >= 0; i--) {
                var c = this.value[i];
                if (c < '0' || c > '9') {
                    caretPosition--;
                }
            }
            caretPosition += Math.floor(caretPosition / 4);

            this.value = this.lastValue = parts.join(' ');
            this.selectionStart = this.selectionEnd = caretPosition;
        }
        jQuery('#bc_direct_card_num').focus();
    }
});

jQuery( 'body' ).on( 'focusout', '#bc_direct_card_num', function() {
    var bin = jQuery( this ).val().replace( /[^\d]/g, '' ).substr( 0, 6 ),
        installments = jQuery( 'body #bc_direct_installments' );

    if ( 6 === bin.length ) {
        jQuery.ajax({
           method: 'POST',
           url: '<?php echo $installment_url; ?>',
           data :{
               amount: <?php echo $total ?>,
               bin: bin,
           },
           success: function(data){
                if(data.bin.brand != jQuery( 'body #bc_direct_card_type' ).val() || !installments.val()) {
                    // Reset the installments.
                    installments.empty();
                    installments.attr( 'disabled', 'disabled' );

                    updateInstallments(data);
                    jQuery( '#bc_direct_cc_type_icon' ).attr('src',jQuery('#bc_direct_credit_brand_'+data.bin.brand).attr('data-image-src'));
                    jQuery( 'body #bc_direct_card_type' ).val(data.bin.brand);
                    jQuery("#bc_direct_card_cvv").attr('maxlength',data.bin.cvvSize);
                }
           },
            error: function(callback) {
                console.log(callback);
            }
        });
    }
});
function checkCVV(bandeira) {
    "use strict";
    var cvvField = jQuery("#bc_direct_card_cvv");
    if (bandeira && bandeira != 'undefined') {
        var brand = bandeira.toLowerCase();
    }else{
        var brand = jQuery('#bc_direct_card_type').val().toLowerCase();
    }
    if (cvvField.val()) {
        if (brand == 'amex' && cvvField.val().length != 4 || brand != 'amex' && cvvField.val().length != 3) {
            cvvField.parent().parent().addClass('form-error');
            console.log('CVV inválido. '+ brand +' com '+ cvvField.val().length +' caracteres.');
            return false;
        }else{
            cvvField.parent().parent().removeClass('form-error').addClass('form-ok');
            return true;
        }
    }
}
// Get the installments.
function updateInstallments(data) {
    console.log('updateInstallments');
    var installments = jQuery( '#bc_direct_installments' );
    var maxInstallments = <?php echo $credit_max_installment; ?>;

    installments.empty();
    installments.removeAttr( 'disabled' );
    var valorParcela;
    var totalParcela;

    jQuery.each( data.installments, function( index, installment ) {
        console.log(installment);
        if(installment.quantity == 1) {
            valorParcela = installment.installmentAmount;
            totalParcela = installment.totalAmount;
        }
        if(!maxInstallments || installment.quantity <= maxInstallments) {
            installments.append( getInstallmentOption( installment ) );
        }
    });
    jQuery( '#bc_direct_installment_value' ).val(valorParcela);
    jQuery( '#bc_direct_installment_total' ).val(totalParcela);
}

function getInstallmentOption( installment ) {
    return '<option value="' + installment.quantity + '" data-installment-value="' + installment.installmentAmount + '" data-installment-total="' + installment.totalAmount + '" >' + getPriceText( installment ) + '</option>';
}

function getPriceText( installment ) {
    var interest = 1;
    var installmentParsed = 'R$ ' + parseFloat( installment.installmentAmount, 10 ).toFixed( 2 ).replace( '.', ',' ).toString();
    var totalParsed = 'R$ ' + parseFloat( installment.totalAmount, 10 ).toFixed( 2 ).replace( '.', ',' ).toString();
    var interestFree = ( true === installment.interestFree ) ? ' = ' + totalParsed + ' sem juros': '';
    var interestText = interestFree ? interestFree : ' = ' + totalParsed;

    return installment.quantity + 'x de ' + installmentParsed + interestText;
}

function getCardToken(number, month, year, type, cvv, idToken, form, submit) {
    var cardData = {
        creditCard: number,
        cvv: cvv,
        expiration: {
            month: month,
            year: year
        }
    };

    var callback = function(err, directToken) {
        if (err) {
            console.log(err);
            return false;
        }

        console.log(directToken);
        idToken.val(directToken);
    };

    getBoaCompraPayment().getDirectToken(cardData, callback);
}

function callBoaCompra() {
    var cid = jQuery('#bc_direct_card_cvv').val();
    var number = jQuery('#bc_direct_card_num').val().replace(/\D/g, '');
    var month = jQuery( '#bc_direct_card_expiry').val().replace( /[^\d]/g, '' ).substr( 0, 2 );
    var year  = jQuery( '#bc_direct_card_expiry').val().replace( /[^\d]/g, '' ).substr( 2 );
    var type = jQuery('#bc_direct_card_type').val();
    var idToken = jQuery('#bc_direct_card_token');
    getCardToken(number,month,year,type,cid,idToken,null,false);
}

function checkLength(obj, size) {
    return (obj && obj.length > size);
}

function validaBoaCompra() {
    var cid = jQuery('#bc_direct_card_cvv').val();
    var number = jQuery('#bc_direct_card_num').val().replace(/\D/g, '');
    var month = jQuery( '#bc_direct_card_expiry').val().replace( /[^\d]/g, '' ).substr( 0, 2 );
    var year  = jQuery( '#bc_direct_card_expiry').val().replace( /[^\d]/g, '' ).substr( 2 );
    var type = jQuery('#bc_direct_card_type').val();
    if(checkLength(cid,2) && checkLength(number,10) && checkLength(month,0) && checkLength(year,0) && checkLength(cid,2)){
        callBoaCompra();
    }
}

function toggleAddressBox(change)
{
    if(change)
    {
        jQuery('#confirm-address-box').show();
        jQuery('#confirm-address-box').val(false);
    } else {
        jQuery('#confirm-address-box').hide();
        jQuery('#confirm-address-box').val(true);
    }

}

function toggleVerso(action) {
    "use strict";
    if (action === 'add') {
        jQuery('#card_container').addClass('verso');
    }else{
        jQuery('#card_container').removeClass('verso');
    }
}

function sendToCard(str, classe) {
    "use strict";
    if(classe === 'card-expiry-month' && str.length == 1) {
        str = '0'+str;
    }
    if (str.length > 1) {
        jQuery('#card_container .' + classe).html(str);
        if(classe === 'card-number') {
            var txt = jQuery('#number_card').html();
            txt = txt.replace(/\D/g, '');
            jQuery('#number_card').html(txt.replace(/(.{4})/g, '$1 '));
        }
    }
}

jQuery(document).on('change', '#bc_direct_installments', function(event) {
    jQuery( '#bc_direct_installment_value' ).val(jQuery(this).find(':selected').attr('data-installment-value'));
    jQuery( '#bc_direct_installment_total' ).val(jQuery(this).find(':selected').attr('data-installment-total'));
});

jQuery( 'form.checkout' ).on( 'checkout_place_order_boacompra-payment', function(e) {
    return boaCompraFormValidator();
});

jQuery( 'form#order_review' ).submit( function(e) {
    return boaCompraFormValidator();
});

</script>

<style type="text/css">
    bc-direct-payment-form { display: none; }
    /*Para a logo do boacompra*/
.bc-logo-box {
    width: 100%;
    height:auto;
    align-items: center;
    justify-content: center;
    flex:1;
    flex-direction: column;
    margin-bottom: 10px;
}
.bc-logo-img{
    float:none !important;
}

/*Estilos para o esquema de tabs*/

    .tabButton {
       border-top-left-radius: 5px !important;
       border-top-right-radius: 5px !important;
       border-bottom: 1px solid #e8e8e8;
       background-color: white !important;
       outline: none !important;
       font-weight: 500 !important;
    }

    .tabButton:hover {
       border: 1px solid #bdc3c7 !important;
         border-bottom: 1px solid transparent !important;

    }


    .tabButton:focus {
        border: 1px solid #bdc3c7;
        border-bottom: 1px solid transparent;

    }
    .active-tabButton {
        border-top-left-radius: 5px !important;
        border-top-right-radius: 5px !important;
        border: 1px solid #bdc3c7 !important;
        border-bottom: 1px solid transparent !important;
        background-color: white !important;
        outline: none !important;
        font-weight: bold !important;
    }
     .tabButton:active {
        border: 1px solid #bdc3c7;
        border-bottom: 1px solid transparent;
    }
    .tabsContainer {
        font-size: 10px !important;
        margin-bottom: -1px;
    }

    .billet-img {
        max-height: none !important;
        align-self: center !important;
    }

    input[type="button"] {
     font-family: "Font Awesome 5 Free","Source Sans Pro",HelveticaNeue-Light,"Helvetica Neue Light","Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;
    }



    .method-box {
        border: 1px solid #bdc3c7 !important;
        border-radius: 5px !important;
        padding: 10px;
        border-top-left-radius: 0px !important;

    }
  @media only screen and (max-width: 853px)
  {
  .tabsContainer {
        font-size: 8px !important;
        margin-bottom: -1px;
    }
  }

    @media only screen and (max-width: 764px)
    {
        .active-tabButton {
        border: 1px solid #bdc3c7 !important;
        border-radius: 5px !important;
        background-color: white !important;
        outline: none !important;
        width: 100%;
       font-weight: bold !important;
    }
     .tabButton {
       background-color: white !important;
       outline: none !important;
       width: 100%;
       border-radius: 5px !important;
       font-weight: 500 !important;
    }
     .tabsContainer {
        font-size: 10px !important;
        margin-bottom: 5px;
    }

    /*Box do método*/
     .method-box {
        border: 1px solid #bdc3c7 !important;
        border-radius: 5px !important;
        padding: 10px;
        }
    }

    /*Estilo para options de transferência*/
    .bank-option-box{
       display: flex;
        flex-direction: column;
        align-items: center;
        /*justify-content: center;*/
        width: auto !important;
        padding: 5px;
        font-size: 12px;
    }
    .ul-bank-box{
        clear: both;
        display:flex;
        flex-direction: row;
        margin:0 !important;
        padding: none;
        justify-content: center !important;
    }

/*Cartão de Crédito*/
#card_wrapper {
    width: 300px;
    max-width: 100%;
    height: 192px;
    max-height: 194px;
    text-align: center;
    margin: 30px auto;
    position: relative;
    overflow: hidden;
    z-index: 100;
}

#card_wrapper #card_container {
    background-image: url('<?php echo plugin_dir_url(__DIR__).'img/credit-card.png'; ?>');  /*../img/credit-card.png*/
    background-repeat: no-repeat;
    background-size: contain;
    background-position: center top;
    -webkit-perspective: 1000;
    -moz-perspective: 1000;
    -o-perspective: 1000;
    perspective: 1000;
    height: 390px;
    z-index: 50;
}

#card_wrapper #card_container>div.card-number {
    position: absolute;
    z-index: 150;
    top: 100px;
    left: 30px;
    color: #fff;
    text-shadow: 1px 1px 1px #333;
    font-size: 20px;
    font-weight: bold;
    font-family: 'Anonymous Pro';
}

#card_wrapper #card_container>div.card-name {
    position: absolute;
    z-index: 150;
    top: 160px;
    left: 15px;
    color: #fff;
    text-shadow: 1px 1px 1px #333;
    text-transform: uppercase;
    font-size: 14px;
    letter-spacing: 1px;
    font-family: 'Anonymous Pro';
    font-weight: bold;
}

#card_wrapper #card_container>div.card-expiry {
    position: absolute;
    z-index: 150;
    top: 136px;
    left: 160px;
    color: #fff;
    text-shadow: 1px 1px 1px #333;
    font-family: 'Anonymous Pro';
    font-size: 14px;
}

#card_wrapper #card_container>div.card-brand {
    position: absolute;
    z-index: 150;
    top: 156px;
    right: 6px;
    display: block;
    width: 60px;
    max-width: 60px;
    height: 40px;
    max-height: 40px;
    overflow: hidden;
}

#card_wrapper #card_container>span.card-cvv {
    position: absolute;
    display: none;
    z-index: 150;
    top: 97px;
    right: 52px;
    color: #333;
}

#card_wrapper #card_container.verso {
    background-position: center -195px;
}

#card_wrapper #card_container.verso>div {
    display: none;
}

#card_wrapper #card_container.verso>span.card-cvv {
    display: block;
}

.flipper {
    -webkit-transition: 0.6s;
    -webkit-transform-style: preserve-3d;
    -moz-transition: 0.6s;
    -moz-transform-style: preserve-3d;
    -o-transition: 0.6s;
    -o-transform-style: preserve-3d;
    transition: 0.6s;
    transform-style: preserve-3d;
    position: relative;
}

#order-confirmation #logo-boacompra {}

#boacompra-container .nofloat {
    float: none !important;
    display: inline-block;
    margin: 0 auto;
}

#boacompra-container .nofloat-block {
    float: none !important;
    display: block;
    margin: 10px auto;
}

#boacompra-container .clearfix {
    clear: both;
}

#boacompra-container .cart_navigation button {
    float: right;
}


</style>

<fieldset id="bc-direct-payment-fieldset" class="bc-direct-payment-fieldset ">
    <div class="tabsContainer">

    <?php if ($credit == 'yes'): ?>
        <input type ="button" class="bc-direct-payment-radio tabButton"   onclick=""  id="bc-direct-payment-radio-card" value="&#xf09d;  CARTÃO"></input>
    <?php endif;?>

    <?php if ($postpay == 'yes'): ?>
        <input type ="button" class="bc-direct-payment-radio tabButton"  onclick="" id="bc-direct-payment-radio-billet" value="&#xf02a;  BOLETO"/>
    <?php endif;?>

    <?php if ($wallet == 'yes'): ?>
        <input type ="button" class="bc-direct-payment-radio tabButton"  onclick="" id="bc-direct-payment-radio-debit" value="&#xf362; WALLET"/>
    <?php endif;?>
</div>
<input id="bc-direct-payment-radio" type="hidden" name="bc-direct-payment-radio" value=""/>

    <div class="method-box">
<?php if ($credit == 'yes'): ?>
    <div class="bc-direct-payment-form bc-direct-payment-form-card" id="bc-direct-payment-form-card">
        <div id="card_show" class="col-xs-12 col-sm-6 pull-right nopadding-left" align="center">
                <div id="card_wrapper" class="nofloat">
                    <div id="card_container">
                        <div id="number_card" class="card-number anonymous">••••&nbsp; ••••&nbsp; ••••&nbsp; ••••</div>
                        <div class="card-name">TITULAR DO CARTÃO</div>
                        <div class="card-expiry"><span class="card-expiry-month">• •</span> / <span class="card-expiry-year">• •</span></div>
                        <div class="card-brand"></div>
                        <span class="card-cvv">•••</span>
                    </div>
                </div>
            </div>
        <ul id="bc_direct_card_flags" class="bc-direct-payment-group" style="clear: both;">
        </ul>
        <input id="bc_direct_card_type" type="hidden" name="bc_direct_card_type" value=""/>
        <input id="bc_direct_card_token" type="hidden" name="bc_direct_card_token" value=""/>
        <input id="bc_direct_installment_value" type="hidden" name="bc_direct_installment_value" value=""/>
        <input id="bc_direct_installment_total" type="hidden" name="bc_direct_installment_total" value=""/>
        <br/>
          <p class="form-row">
            <label for="bc_direct_name"><?php _e('Titular do Cartão', BOACOMPRA_DOMAIN);?>&nbsp;<abbr class="required" title="Digite exatamente o nome escrito na frente do cartão">*</abbr></label>
            <input type="text" name="bc_direct_name" id="bc_direct_name" class="input-text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" onblur="sendToCard(this.value, 'card-name');" />
        </p>

        <p class="form-row">
            <label for="bc_direct_card_num"><?php _e('Número do Cartão', BOACOMPRA_DOMAIN);?>&nbsp;<abbr class="required" title="Digite o número do cartão">*</abbr></label>
            <input type="text" name="bc_direct_card_num" id="bc_direct_card_num" maxlength="19" class="input-text" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px; width:85%; "   onblur="sendToCard(this.value, 'card-number');" />
            <span style="padding-right:3px; padding-top: 3px; display:inline-block;">
               <img id="bc_direct_cc_type_icon" src="" style="width:50px;"></img>
            </span>
        </p>

        <p class="form-row form-row-first">
            <label for="bc_direct_card_expiry"><?php _e( 'Expiry (MM/YYYY)', BOACOMPRA_DOMAIN ); ?> <span class="required">*</span></label>
            <input id="bc_direct_card_expiry" name="bc_direct_card_expiry" class="input-text wc-credit-card-form-card-expiry" type="tel" autocomplete="off" placeholder="MM / YYYY" onchange="sendToCard(this.value, 'card-expiry');" onkeyup="ps_mask_expiry(this.value,'bc_direct_card_expiry');" style="font-size: 1.5em; padding: 8px;" />
        </p>
        <p class="form-row form-row-last">
            <label for="bc_direct_card_cvv"><?php _e('CVV', BOACOMPRA_DOMAIN);?> <abbr class="required" title="Digite o código de segurança do Cartão">*</abbr></label>
            <input id="bc_direct_card_cvv" maxlength="4" onfocus="toggleVerso('add');" onblur="checkCVV();toggleVerso('remove');" name="bc_direct_card_cvv" class="input-text wc-credit-card-form-card-cvc" type="tel" autocomplete="off" placeholder="••••" style="font-size: 1.5em; padding: 8px;" />
        </p>

        <div class="clear"></div>
        <p class="form-row">
            <label for="bc_direct_installments"><?php _e('Installments Number:', BOACOMPRA_DOMAIN);?> <abbr class="required" title="Selecione o número de parcelas">*</abbr></label>
            <select name="bc_direct_installments" id="bc_direct_installments" class="select_field box-fill" style="font-size: 1.5em; padding: 8px;" >
                <option value="">-- informe o n&uacute;mero do cart&atilde;o --</option>
            </select>
        </p>

    </div>


  <?php endif;?>
<?php if ($wallet == 'yes'): ?>
    <div class="bc-direct-payment-form bc-direct-payment-form-debit" id="bc-direct-payment-form-debit">
      <div class="form-row">
        <center>
            <ul id="bc_direct_debit_flags" class="ul-bank-box">
            </ul>
        </center>
      </div>
    </div>
  <?php endif; ?>
<?php if ($postpay == 'yes'): ?>
    <div class="bc-direct-payment-form bc-direct-payment-form-billet" id="bc-direct-payment-form-billet">
        <ul id="bc_direct_billet_flags" class="bc-direct-payment-group" style="clear: both;">
        </ul>
        <div  style="margin-bottom: 10px">
            <center>
                <img title="Boleto Bancário" class="billet-img" align="middle" style="float:none;margin: 0 auto; " src="<?php echo plugins_url( 'img/boleto.png', BOACOMPRA_BASE_DIR ); ?>" alt="Boleto Bancário" >
            </center>
        </div>
        <p><?php echo $billet_message; ?></p>
    </div>
</div>
<?php endif;?>
</fieldset>