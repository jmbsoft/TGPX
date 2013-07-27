<tr id="<?PHP echo $item['whitelist_id']; ?>">
  <td>
    <input class="checkbox autocb" name="whitelist_id[]" value="<?PHP echo $item['whitelist_id']; ?>" type="checkbox">
  </td>
  <td>
    <?php echo StringChopTooltip($item['value'], 35); ?>
  </td>
  <td>
    <?PHP echo $WLIST_TYPES[$item['type']]; ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item['reason'], 25); ?>
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['allow_redirect'] ? 'check' : 'x'; ?>.png">
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['allow_norecip'] ? 'check' : 'x'; ?>.png">
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['allow_autoapprove'] ? 'check' : 'x'; ?>.png">
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['allow_noconfirm'] ? 'check' : 'x'; ?>.png">
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['allow_blacklist'] ? 'check' : 'x'; ?>.png">
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShWhitelistEdit&whitelist_id=<?php echo urlencode($item['whitelist_id']); ?>" class="window function {title: 'Edit Whitelist Item', height: 470}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['whitelist_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>
