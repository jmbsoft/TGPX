<tr id="<?php echo $item['history_id']; ?>">
  <td class="centered" valign="top">
    <?php echo date(DF_SHORT, strtotime($item['date_start'])); ?>
  </td>
  <td class="centered" valign="top">
    <?php echo $item['date_end'] ? date(DF_SHORT, strtotime($item['date_end'])) : '-'; ?>
  </td>
  <td class="centered" valign="top">
    <?php echo number_format($item['pages_built'], 0, $C['dec_point'], $C['thousands_sep']); ?>
    of
    <?php echo number_format($item['pages_total'], 0, $C['dec_point'], $C['thousands_sep']); ?>
  </td>
  <td class="last">
    <a href="<?php echo $item['current_page_url']; ?>" target="_blank"><?php echo StringChopTooltip($item['current_page_url'], 60); ?></a><br />
    <?php echo nl2br($item['error_message']); ?>
  </td>
</tr>