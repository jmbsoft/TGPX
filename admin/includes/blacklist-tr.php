<tr id="<?PHP echo $item['blacklist_id']; ?>">
  <td>
    <input class="checkbox autocb" name="blacklist_id[]" value="<?PHP echo $item['blacklist_id']; ?>" type="checkbox">
  </td>
  <td>
    <?php echo StringChopTooltip($item['value'], 35); ?>
  </td>
  <td>
    <?PHP echo $BLIST_TYPES[$item['type']]; ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item['reason'], 90); ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShBlacklistEdit&blacklist_id=<?php echo urlencode($item['blacklist_id']); ?>" class="window function {title: 'Edit Blacklist Item'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['blacklist_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>
