<tr id="<?php echo $item['annotation_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="annotation_id[]" value="<?php echo $item['annotation_id']; ?>">
  </td>
  <td>
    <?php echo StringChopTooltip($item['identifier'], 50); ?>
  </td>
  <td>
    <?php echo ucfirst($item['type']); ?>
  </td>
  <td>
    <?php echo $ANN_LOCATIONS[$item['location']]; ?>
  </td>
  <td>
    <?php echo StringChopTooltip($item[$_REQUEST['order']], 20); ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="index.php?r=txShAnnotationEdit&annotation_id=<?php echo urlencode($item['annotation_id']); ?>" class="window function {title: 'Edit Annotation'}">
    <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
    <a href="" onclick="return deleteSelected('<?php echo $item['annotation_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>