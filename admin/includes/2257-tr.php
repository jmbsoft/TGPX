<tr id="<?PHP echo $item['code_id']; ?>">
  <td>
    <input class="checkbox autocb" name="code_id[]" value="<?PHP echo $item['code_id']; ?>" type="checkbox">
  </td>
  <td>
    <?PHP echo $item['identifier']; ?>
  </td>
  <td>
    <?PHP echo $item['code']; ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txSh2257Edit&code_id=<?php echo urlencode($item['code_id']); ?>" class="window function {title: 'Edit 2257 Code'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['code_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>