<?php if( $error_message == false ) {?>
  <div class="buttons">
  <div class="pull-right">
    <a class="btn btn-primary" href="<?php echo $url_redirect ?>"><?php echo $button_confirm ?> <?php echo $and_pay ?> <?php echo $btc ?> BTC</a>
  </div>
</div>
<?php } else {?>
    <div class="buttons"><div class="alert alert-danger" role="alert"><?php echo $error_message ?></div></div>
<?php } ?>