<tr id="<?php echo $item['term_id']; ?>">
  <td>
    <input type="checkbox" class="checkbox autocb" name="term_id[]" value="<?php echo $item['term_id']; ?>">
  </td>
  <td>
    <?php echo StringChopTooltip($item['term'], 120); ?>
  </td>
  <td>
    <?php echo date(DF_SHORT, strtotime($item['date_last_search'])); ?>
  </td>
  <td>
    <?php echo number_format($item['searches'], 0, $C['dec_point'], $C['thousands_sep']); ?>
  </td>
  <td style="text-align: right;" class="last">
    <a href="" onclick="return deleteSelected('<?php echo $item['term_id']; ?>')" class="function">
    <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete"></a>
  </td>
</tr>