<tr id="<?php echo $item['ad_id']; ?>">
  <td valign="top">
    <input type="checkbox" class="checkbox autocb" name="ad_id[]" value="<?php echo $item['ad_id']; ?>">
  </td>
  <td>
    <div style="float: right; color: #666">#<?php echo $item['ad_id']; ?></div>
    <b style="padding-right: 4px;">Raw:</b> <?php echo number_format($item['raw_clicks'], 0, $C['dec_point'], $C['thousands_sep']); ?>
    
    <b style="padding-left: 40px; padding-right: 4px;">Unique:</b> <?php echo number_format($item['unique_clicks'], 0, $C['dec_point'], $C['thousands_sep']); ?>
    <b style="padding-left: 40px; padding-right: 4px;">Displayed:</b> <?php echo number_format($item['times_displayed'], 0, $C['dec_point'], $C['thousands_sep']); ?>
    
    <div style="margin-top: 5px;">
    <?php echo html_entity_decode($item['ad_html']) ?>
    </div>
  </td>
  <td style="text-align: right;" class="last" valign="top">
    <a href="index.php?r=txShAdEdit&ad_id=<?php echo urlencode($item['ad_id']); ?>" class="window function {title: 'Edit Advertisement'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['ad_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>