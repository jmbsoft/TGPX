<tr id="<?php echo $item['gallery_id']; ?>">
  <td>
    <?php if( $item['action'] != 'Deleted' && $item['action'] != 'Blacklisted' ): ?>
    <input type="checkbox" class="checkbox autocb" name="gallery_id[]" value="<?php echo $item['gallery_id']; ?>">
    <?php endif; ?>
  </td>
  <td valign="top">
    <a href="<?php echo $item['gallery_url']; ?>" target="_blank"><?php echo StringChopTooltip($item['gallery_url'], 90, TRUE); ?></a><br />
    <?php echo $item['message']; ?>
  </td>
  <td valign="top" class="r-<?php echo strtolower($item['action']); ?>">
    <?php echo $item['action']; ?>
  </td>
  <td valign="top">
    <?php echo date(DF_SHORT, strtotime($item['date_scanned'])); ?>
  </td>
  <td style="text-align: right;" class="last" valign="top">
    <?php if( $item['action'] != 'Deleted' && $item['action'] != 'Blacklisted' ): ?>
    <img src="images/search.png" width="12" height="12" alt="Scan" title="Scan" class="click-image function" onclick="openScan('<?php echo $item['gallery_id']; ?>')">
    <a href="index.php?r=txShGalleryBlacklist&gallery_id=<?php echo urlencode($item['gallery_id']); ?>" class="window function {title: 'Blacklist Gallery', height: 400}">
    <img src="images/blacklist.png" width="10" height="12" alt="Blacklist" title="Blacklist"></a>
    <?php if( $item['status'] != 'disabled' ): ?>
    <img src="images/disable.png" width="12" height="12" alt="Disable" title="Disable" class="click-image function" onclick="executeSingle('disable', '<?php echo $item['gallery_id']; ?>')">
    <?php else: ?>
    <img src="images/enable.png" width="12" height="12" alt="Enable" title="Enable" class="click-image function" onclick="executeSingle('enable', '<?php echo $item['gallery_id']; ?>')">
    <?php endif; ?>
    <a href="index.php?r=txShGalleryEdit&gallery_id=<?php echo urlencode($item['gallery_id']); ?>" class="window function {title: 'Edit Gallery'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="index.php?r=txShSubmitterMail&gallery_id[]=<?php echo urlencode($item['gallery_id']); ?>" class="window function {title: 'E-mail Submitter'}">
    <img src="images/mail.png" width="12" height="12" alt="E-mail" title="E-mail"></a>
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete" class="click-image function" onclick="executeSingle('delete', '<?php echo $item['gallery_id']; ?>')">
    <?php endif; ?>
  </td>
</tr>