<tr id="<?php echo $item['username']; ?>" class="<?php echo $item['status']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="username[]" value="<?php echo $item['username']; ?>">
  </td>
  <td>
    <div style="padding-bottom: 5px;"> 
    <b style="padding-right: 4px;">Username:</b> <a href="mailto:<?php echo $item['email']; ?>"><?php echo $item['username']; ?></a>
    
    <b style="padding-left: 40px; padding-right: 4px;">Submitted:</b> <?php echo number_format($item['submitted'], 0, $C['dec_point'], $C['thousands_sep']); ?>
    <b style="padding-left: 40px; padding-right: 4px;">Removed:</b> <?php echo number_format($item['removed'], 0, $C['dec_point'], $C['thousands_sep']); ?>
    <b style="padding-left: 40px; padding-right: 4px;">Per Day:</b> <?php echo $item['per_day']; ?>
    </div>
    
    <div style="padding-bottom: 5px;"> 
    <b style="padding-right: 4px;">Date Added:</b> <?php echo $item['date_added']; ?>
    <b style="padding-left: 40px; padding-right: 4px;">Date Last Submit:</b> <?php echo $item['date_last_submit']; ?>
    </div>
    
    <?php if( $item['date_start'] != '-' ): ?>
    <div style="padding-bottom: 5px;"> 
    <b style="padding-right: 4px;">Date Range:</b> <span style="color: #666"><?php echo $item['date_start']; ?></span> to <span style="color: #666"><?php echo $item['date_end']; ?></span>
    </div>
    <?php endif; ?>

    <div style="padding-bottom: 5px; text-align: right; color: #666"> 
    <b style="padding-right: 4px;">Sorter:</b> <?php echo StringChopTooltip($item[$_REQUEST['order']], 20); ?>
    </div>
  </td>
  <td style="text-align: right;" class="last" valign="top">
    <a href="index.php?r=txShGallerySearch&sf=partner&s=<?php echo urlencode($item['username']); ?>" class="function">
    <img src="images/go.png" alt="View Galleries" title="View Galleries"></a>
    <?php if( isset($item['yahoo_login']) && !empty($item['yahoo_login']) ): ?>
    <a href="ymsgr:sendim?<?php echo urlencode(html_entity_decode($item['yahoo_login'])); ?>">
    <img src="images/yahoo.png" width="12" height="12" alt="Yahoo" class="function"></a>
    <?php
    endif;
    if( isset($item['aim_login']) && !empty($item['aim_login']) ):
    ?>
    <a href="aim:goim?screenname=<?php echo urlencode(html_entity_decode($item['aim_login'])); ?>">
    <img src="images/aim.png" width="12" height="12" alt="AIM" class="function"></a>
    <?php
    endif;
    if( isset($item['icq_login']) && !empty($item['icq_login']) ):
    ?>
    <a href="http://www.icq.com/people/cmd.php?uin=<?php echo urlencode(html_entity_decode($item['icq_login'])); ?>&action=message">
    <img src="images/icq.png" width="12" height="12" alt="ICQ" class="function"></a>
    <?php endif; ?>
    <?php if( $item['status'] != 'suspended' ): ?>
    <img src="images/disable.png" width="12" height="12" alt="Suspend" title="Suspend" class="function click-image" onclick="executeSingle('suspend', '<?php echo $item['username']; ?>')">
    <?php else: ?>
    <img src="images/enable.png" width="12" height="12" alt="Activate" title="Activate" class="function click-image" onclick="executeSingle('activate', '<?php echo $item['username']; ?>')">
    <?php endif; ?>
    <a href="index.php?r=txShPartnerEdit&username=<?php echo urlencode($item['username']); ?>" class="window function {title: 'Edit Partner'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="index.php?r=txShPartnerMail&username[]=<?php echo urlencode($item['username']); ?>" class="window function {title: 'E-mail Partner'}">
    <img src="images/mail.png" width="12" height="12" alt="E-mail" title="E-mail"></a>
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete" class="function click-image" onclick="executeSingle('delete', '<?php echo $item['username']; ?>')">    
  </td>
</tr>