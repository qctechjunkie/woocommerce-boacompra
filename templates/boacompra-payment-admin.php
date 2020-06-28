<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<h4><?php _e('Payment Details', BOACOMPRA_DOMAIN);?></h4>
<div class="payment">
    <p>
        <strong><?php _e('Method:', BOACOMPRA_DOMAIN);?></strong>
        <?php echo '<span>'.$metodo.'</span>'; ?><br>
        <strong><?php _e('Transaction ID:', BOACOMPRA_DOMAIN);?></strong><!--<br>-->
        <?php echo '<span style="word-wrap:break-word;">'.$t_id.'</span>'; ?><br>
        <strong><?php _e('BoaCompra Status:', BOACOMPRA_DOMAIN);?></strong>
        <?php echo '<span id="status">'.$status.'</span>'; ?><br>
        <strong><?php _e('Transaction Message:', BOACOMPRA_DOMAIN);?></strong>
        <?php echo '<span id="t_msg">'.$t_msg.'</span>'; ?><br>
        <?php if ($parcelas): ?>
            <strong><?php _e('Installments:', BOACOMPRA_DOMAIN);?></strong>
            <?php echo '<span>'.$parcelas.'</span>'; ?><br>
        <?php endif;?>
        <?php if ($bandeira): ?>
            <strong><?php _e('Card Brand:', BOACOMPRA_DOMAIN);?></strong>
            <?php echo '<span>'.$bandeira.'</span>'; ?><br>
        <?php endif;?>
        <input type="button" id="consult_transaction" value="<?php _e('Consulta', BOACOMPRA_DOMAIN)?>" class="button button-primary" style="margin-top:10px;"/>
        <script type="text/javascript">
            jQuery(document).ready(function() {
                jQuery('#consult_transaction').on('click', consultTransaction);
            });
            function consultTransaction(){
                jQuery('#consult_transaction').val('<?php _e('Consultando...', BOACOMPRA_DOMAIN)?>');
                jQuery('#consult_transaction').removeClass('button-primary');
                jQuery('#consult_transaction').attr('disabled', 'disabled');
                jQuery.ajax({
                   method: 'POST',
                   url: ajaxurl,
                   data :{
                       action: 'consultboacompra',
                       order: '<?php echo $order_id; ?>',
                       transid: '<?php echo $t_id; ?>'
                   },
                   success: function(response){
                       //response = JSON.parse(response);
                       //jQuery('#t_msg').html(response.mensagem);
                       jQuery('#consult_transaction').val('<?php _e('Consultado', BOACOMPRA_DOMAIN)?>');
                       jQuery('#consult_transaction').removeClass('button-primary');
                       jQuery('#consult_transaction').attr('disabled', 'disabled');
                       location.reload();
                   }
               });
            }
        </script>
        <?php $allowedStatuses = array('PENDING'); ?>
        <?php if ($billet_url && $metodo == 'postpay' && in_array($status, $allowedStatuses)): ?>
            <a id="billet_url" target="_blank" href="<?php echo $billet_url; ?>" class="button button-primary" style="margin-top:10px;"><?php _e('Imprimir Boleto', BOACOMPRA_DOMAIN)?></a>
        <?php elseif ($billet_url && $metodo == 'e-wallet' && in_array($status, $allowedStatuses)): ?>
            <!--a id="billet_url" target="_blank" href="<?php echo $billet_url; ?>" class="button button-primary" style="margin-top:10px;"><?php _e('Transferência Bancária', BOACOMPRA_DOMAIN)?></a-->
        <?php endif;?>
    </p>
</div>