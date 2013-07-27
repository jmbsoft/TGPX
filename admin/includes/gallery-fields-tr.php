<tr id="<?PHP echo $item['field_id']; ?>">
  <td>
    <input class="checkbox autocb" name="field_id[]" value="<?PHP echo $item['field_id']; ?>" type="checkbox">
  </td>
  <td>
    <?PHP echo $item['name']; ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item['label'], 70); ?>
  </td>
  <td>
    <?PHP echo $item['type']; ?>
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['required'] ? 'check' : 'x'; ?>.png">
  </td>
  <td style="text-align: center;">
    <img src="images/<?PHP echo $item['on_submit'] ? 'check' : 'x'; ?>.png">
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShGalleryFieldEdit&field_id=<?php echo urlencode($item['field_id']); ?>" class="window function {title: 'Edit Gallery Field'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['field_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>