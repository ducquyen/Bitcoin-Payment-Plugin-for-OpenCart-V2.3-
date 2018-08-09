<!DOCTYPE html>
<html>
<head>
    <title>Pay with Bitcoin</title>
    <link rel="stylesheet" type="text/css" href="<?php echo $style ?>">
    <script src="catalog/view/javascript/apirone/jquery-2.1.1.min.js" type="text/javascript"></script>
<script type="text/javascript">
    if (window.jQuery) {
    apirone_query();
    function apirone_query(){
    jQuery(".abf-refresh").addClass('rotating');
    var key = '<?php echo $key ?>';
    var order = <?php echo $order ?>;
    if (key != undefined && order != undefined) {
    abf_get_query='/index.php?route=extension/payment/apirone/check_payment&key='+key+'&order='+order;
    jQuery.ajax({
    url: abf_get_query,
    dataType : "text",
    success: function (data, textStatus) {
       data = JSON.parse(data);
        if (data.status == "complete") {
            complete = 1; 
            jQuery(".with-uncomfirmed, .uncomfirmed").empty();
            statusText = "<?php echo $payment_complete ?>";
        }
        if (data.status == "innetwork") {
            innetwork = 1;
            complete = 0;
            jQuery(".with-uncomfirmed").text("(<?php echo $with_uncomfirmed ?>)");
            statusText = "<?php echo $tx_in_network ?>: "+ data.innetwork_amount +" BTC)";
        }
        if (data.status == "waiting") {
            complete = 0;
            jQuery(".with-uncomfirmed, .uncomfirmed").empty();
            statusText = "<?php echo $waiting_payment ?>";
        }

        jQuery(".abf-tx").empty();
        if(!data.transactions) {
            jQuery(".abf-tx").prepend('<?php echo $no_tx_yet ?>');
        } else{
            data.transactions.forEach(showTransactions);
        }

        input_address = jQuery('.abf-input-address').html(); 
        encoded_msg = encodeURIComponent("bitcoin:" + input_address + "?amount=" + data.remains_to_pay + "&label=Apirone");
        src = 'https://apirone.com/api/v1/qr?message=' + encoded_msg;
        jQuery('.abf-img-height').hide();
        jQuery('.abf-img-height').attr('src', src);
        jQuery('.abf-img-height').show();

        function showTransactions(value, index, ar) {
            if(value.confirmations >= data.count_confirmations) {
                color='abf-green';
            } else if(value.confirmations > 0 && value.confirmations < data.count_confirmations) {
                color='abf-yellow';
            } else{
                color='abf-red';
            };
            tx = '<div><a href="https://apirone.com/btc/tx/' + value.input_thash + '" target="_blank">'+ value.input_thash.substr(0,8)+ '...' + value.input_thash.substr(-8) + '</a><div class="abf-confirmations ' + color + '" title="<?php echo $confirmations_count ?>">' + value.confirmations + '</div></div>';
            jQuery(".abf-tx").prepend(tx);
        }
        jQuery( ".abf-totalbtc" ).text(data.total_btc);
        jQuery( ".abf-arrived" ).text(data.arrived_amount);

        remains = parseFloat(data.remains_to_pay);
        remains = remains.toFixed(8);
        if( remains < 0 ) remains = 0;
        jQuery( ".abf-remains" ).text(remains);
        jQuery( ".abf-status" ).text(statusText);
        complete_block = '<div class="abf-complete"><p><?php echo $thank_you ?></p></div>';
 
        if (!jQuery("div").is(".abf-complete") && complete){ jQuery( ".abf-data" ).after(complete_block); }
        jQuery(".abf-refresh").removeClass('rotating');
        
    } ,
    error: function(xhr, ajaxOptions, thrownError){
      jQuery( ".apirone_result" ).html( '<h4><?php echo $waiting_payment ?>...</h4>' );
    }
    });
    }
    }
    setInterval(apirone_query, 7000);
    jQuery( document ).ready(function() {
    jQuery(".abf-refresh").click(function(event) {
        jQuery(".abf-refresh").addClass('rotating');
        apirone_query();
    });
});
}
</script>
</head>
<body>
<div class="abf-frame">
        <div class="abf-header">
            <div>
                <div class="abf-ash1"><img src="/catalog/view/theme/default/image/apirone_bitcoin_logo.svg" alt=""></div>
            </div>
            <div style="text-align: center; background-color:#fff;"><span class="abf-qr">
               <img class="abf-img-height" src="https://apirone.com/api/v1/qr?message=<?php echo $message ?>">
               </span>
            </div> 
        </div>
        <div class="abf-form">
            <div class="abf-ash1">
                <?php echo $please_send ?> <strong><span class="abf-totalbtc"><?php echo $response_btc ?></span></strong> BTC
                <?php echo $to_address ?>:
            </div>
            <div class="abf-address abf-topline abf-ash2 abf-input-address"><?php echo $input_address ?></div>
            <div class="abf-data abf-topline">
                <div class="abf-list">
                    <div class="abf-list-item">
                        <div class="abf-label"><?php echo $merchant ?>:</div>
                        <div class="abf-value"><?php echo $merchantname ?></div>
                    </div>
                    <div class="abf-list-item">
                        <div class="abf-label"><?php echo $amount_to_pay ?>:</div>
                        <div class="abf-value"><span class="abf-totalbtc"><?php echo $response_btc ?></span> BTC</div>
                    </div>
                    <div class="abf-list-item">
                        <div class="abf-label"><?php echo $arrived_amount ?>:</div>
                        <div class="abf-value"><span class="abf-arrived">0.00000000</span> BTC</div>
                    </div>
                    <div class="abf-list-item">
                        <div class="abf-label"><?php echo $remains_to_pay ?>:</div>
                        <div class="abf-value"><b><span class="abf-remains"><?php echo $response_btc ?></span> BTC</b></div>
                    </div>                                                           
                    <div class="abf-list-item">
                        <div class="abf-label"><?php echo $date ?>:</div>
                        <div class="abf-value"><?php echo $current_date ?></div>
                    </div>
                    <div class="abf-list-item abf-tx-block">
                        <div class="abf-label"><?php echo $transactions ?>:</div>
                        <div class="abf-value abf-tx">
                            <?php echo $no_tx_yet ?>
                        </div>
                    </div>
                    <div class="abf-list-item">
                        <div class="abf-label"><?php echo $status ?>:</div>
                        <div class="abf-value"><b><span class="abf-status"><?php echo $loading_data ?></span></b><div class="abf-refresh"></div></div>
                    </div>
                </div>
            </div>
            <div class="abf-info">
                <p><?php echo $if_you_unable_complete ?><br><?php echo $you_can_pay_partially ?>
                </p>
                <p class="abf-left"><a href="<?php $back_to_cart ?>"><?php echo $go_to_cart ?></a></p>
                <p class="abf-right"><a href="https://apirone.com/" target="_blank"><img src="/catalog/view/theme/default/image/apirone_logo.svg"  alt=""></a></p>
                <div class="abf-clear"></div>
            </div>
        </div>
    </div>
    <div class="abf-clear"></div>
</body>
</html>