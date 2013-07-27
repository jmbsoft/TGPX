<tr id="<?php echo $item['email_id']; ?>">
  <td>
    <input class="checkbox autocb" name="email_id[]" value="<?php echo $item['email_id']; ?>" type="checkbox">
  </td>
  <td>
    <?php echo StringChopTooltip($item['identifier'], 50); ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item['message']['subject'], 90); ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShRejectionTemplateEdit&email_id=<?php echo urlencode($item['email_id']); ?>" class="window function {title: 'Edit Rejection E-mail'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['email_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>