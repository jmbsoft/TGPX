<tr id="<?PHP echo $item['recip_id']; ?>">
  <td>
    <input class="checkbox autocb" name="recip_id[]" value="<?PHP echo $item['recip_id']; ?>" type="checkbox">
  </td>
  <td>
    <?PHP echo $item['identifier']; ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item['code'], 110); ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShReciprocalEdit&recip_id=<?php echo urlencode($item['recip_id']); ?>" class="window function {title: 'Edit Reciprocal Link'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['recip_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>