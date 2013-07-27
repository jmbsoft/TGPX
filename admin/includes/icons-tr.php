<tr id="<?php echo $item['icon_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="icon_id[]" value="<?php echo $item['icon_id']; ?>">
  </td>
  <td>
    <?php echo StringChopTooltip($item['identifier'], 28); ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item['icon_html'], 130); ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShIconEdit&icon_id=<?php echo urlencode($item['icon_id']); ?>" class="window function {title: 'Edit Icon'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['icon_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>