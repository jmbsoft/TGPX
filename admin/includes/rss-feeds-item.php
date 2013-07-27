<?php foreach( $item as $tag => $value ): ?>
<b>&lt;<?php echo $tag; ?>&gt;</b><br />
<?php
if( is_array($value) ):
    foreach( $value as $attr => $attr_value ):
?>
<div style="margin-left: 30px;">
<b><?php echo htmlspecialchars($attr); ?></b><br />
<?php echo htmlspecialchars($attr_value); ?><br />
</div>
<br />
<?php
    endforeach;
else:
    echo htmlspecialchars($value) . '<br /><br />';
endif;
endforeach; ?>