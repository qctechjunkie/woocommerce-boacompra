(function ( $ ) {
    'use strict';

    $( function () {

        /**
         * Switch transparent checkout options display basead in payment type.
         *
         * @param {String} method
         */
        function BoaCompraChangeIntegration( method ) {
            console.log(method);
            var fields_direct  = $( '#woocommerce_boacompra-payment_credit' ).closest( '.form-table' ),
                heading_direct = fields_direct.prev( 'h3' ),
                fields_hosted  = $( '#woocommerce_boacompra-payment_card' ).closest( '.form-table' ),
                heading_hosted = fields_hosted.prev( 'h3' ),
                redirect_message = $( '#woocommerce_boacompra-payment_redirect_message' ).closest( 'tr' );

            if(method == 'direct') {
                fields_direct.show();
                heading_direct.show();
                fields_hosted.hide();
                heading_hosted.hide();
                redirect_message.hide();
                BoaCompraShowHideBilletOptions( $( '#woocommerce_boacompra-payment_billet' ).is( ':checked' ) );
                BoaCompraShowHideCreditcardOptions( $( '#woocommerce_boacompra-payment_credit' ).is( ':checked' ) );
            }
            else {
                fields_direct.hide();
                heading_direct.hide();
                fields_hosted.show();
                heading_hosted.show();
                redirect_message.show();
            }
        }

        /**
         * Switch banking ticket message display.
         *
         * @param {String} checked
         */
        function BoaCompraShowHideBilletOptions( checked, suffix = '' ) {
            var fields  = $( '#woocommerce_boacompra-payment'+suffix+'_billet_message' ).closest( '.form-table' ),
                heading = fields.prev( 'h3' );

            if ( checked ) {
                fields.show();
                heading.show();
            } else {
                fields.hide();
                heading.hide();
            }
        }


        /**
         * Switch banking ticket message display.
         *
         * @param {String} checked
         */
        function BoaCompraShowHideCreditcardOptions( checked, suffix = '' ) {
            var fields  = $( '#woocommerce_boacompra-payment'+suffix+'_maximum_installment' ).closest( '.form-table' ),
                heading = fields.prev( 'h3' );

            if ( checked ) {
                fields.show();
                heading.show();
            } else {
                fields.hide();
                heading.hide();
            }
        }

        /**
         * Awitch user data for sandbox and production.
         *
         * @param {String} checked
         */
        function BoaCompraShowHideCredentials( value, suffix = '' ) {
            var merchantid = $( '#woocommerce_boacompra-payment'+suffix+'_merchantid' ).closest( 'tr' ),
                secretkey = $( '#woocommerce_boacompra-payment'+suffix+'_secretkey' ).closest( 'tr' ),
                sandboxMerchantid = $( '#woocommerce_boacompra-payment'+suffix+'_sandbox_merchantid' ).closest( 'tr' ),
                sandboxSecretkey = $( '#woocommerce_boacompra-payment'+suffix+'_sandbox_secretkey' ).closest( 'tr' );

            if ( value == 'sandbox' ) {
                merchantid.hide();
                secretkey.hide();
                sandboxMerchantid.show();
                sandboxSecretkey.show();
            } else {
                merchantid.show();
                secretkey.show();
                sandboxMerchantid.hide();
                sandboxSecretkey.hide();
            }
        }

        BoaCompraChangeIntegration( $( '#woocommerce_boacompra-payment_boacompra_mode' ).val() );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment_boacompra_mode', function () {
            BoaCompraChangeIntegration( $( this ).val() );
        }).change();

        BoaCompraShowHideBilletOptions( $( '#woocommerce_boacompra-payment_billet' ).is( ':checked' ) );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment_billet', function () {
            BoaCompraShowHideBilletOptions( $( this ).is( ':checked' ) );
        });

        BoaCompraShowHideCreditcardOptions( $( '#woocommerce_boacompra-payment_credit' ).is( ':checked' ) );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment_credit', function () {
            BoaCompraShowHideCreditcardOptions( $( this ).is( ':checked' ) );
        });

        BoaCompraShowHideCredentials( $('#woocommerce_boacompra-payment_boacompra_environment option:selected').val() );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment_boacompra_environment', function () {
            BoaCompraShowHideCredentials( $( this ).val() );
        });

        /*BoaCompraShowHideBilletOptions( $( '#woocommerce_boacompra-payment-redirect_billet' ).is( ':checked' ), '-redirect' );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment-redirect_billet', function () {
            BoaCompraShowHideBilletOptions( $( this ).is( ':checked' ), '-redirect' );
        });

        BoaCompraShowHideCreditcardOptions( $( '#woocommerce_boacompra-payment-redirect_credit' ).is( ':checked' ), '-redirect' );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment-redirect_credit', function () {
            BoaCompraShowHideCreditcardOptions( $( this ).is( ':checked' ), '-redirect' );
        });*/

        BoaCompraShowHideCredentials( $('#woocommerce_boacompra-payment-redirect_boacompra_environment option:selected').val(), '-redirect' );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment-redirect_boacompra_environment', function () {
            BoaCompraShowHideCredentials( $( this ).val(), '-redirect' );
        });

        BoaCompraShowHideCredentials( $('#woocommerce_boacompra-payment-billet_boacompra_environment option:selected').val(), '-billet' );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment-billet_boacompra_environment', function () {
            BoaCompraShowHideCredentials( $( this ).val(), '-billet' );
        });

        BoaCompraShowHideCredentials( $('#woocommerce_boacompra-payment-credit_boacompra_environment option:selected').val(), '-credit' );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment-credit_boacompra_environment', function () {
            BoaCompraShowHideCredentials( $( this ).val(), '-credit' );
        });

        BoaCompraShowHideCredentials( $('#woocommerce_boacompra-payment-debit_boacompra_environment option:selected').val(), '-debit' );
        $( 'body' ).on( 'change', '#woocommerce_boacompra-payment-debit_boacompra_environment', function () {
            BoaCompraShowHideCredentials( $( this ).val(), '-debit' );
        });
    });

}( jQuery ));
