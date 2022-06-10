<?php if ( $type == 'info' ) : ?>
    <span style="color: #31708f; font-weight: bold; background-color: #d9edf7; border-color: #bce8f1; border: 1px solid; border-radius: 4px; transition: opacity .15s linear; padding: 15px; display: block;"><?php echo $message; ?></span>
<?php endif; ?>
<?php if ( $type == 'warning' ) : ?>
    <span style="color: #dba617; font-weight: bold; background-color: #fcf8e3; border-color: #dba617; border: 1px solid; border-radius: 4px; transition: opacity .15s linear; padding: 15px; display: block;"><?php echo $message; ?></span>
<?php endif;