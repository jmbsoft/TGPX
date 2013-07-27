<tr id="<?php echo $item['config_id']; ?>">
  <td>
    <?php echo date(DF_SHORT, strtotime($item['date_start'])); ?>
  </td>
  <td>
    <?php echo $item['date_end'] ? date(DF_SHORT, strtotime($item['date_end'])) : '-'; ?>
  </td>
  <td class="centered">
    <?php echo number_format($item['scanned'], 0, $C['dec_point'], $C['thousands_sep']) . " of " . number_format($item['selected'], 0, $C['dec_point'], $C['thousands_sep']); ?>
  </td>
  <td class="centered">
    <?php echo number_format($item['exceptions'], 0, $C['dec_point'], $C['thousands_sep']); ?>
  </td>
  <td class="centered">
    <?php echo number_format($item['disabled'], 0, $C['dec_point'], $C['thousands_sep']); ?>
  </td>
  <td class="centered">
    <?php echo number_format($item['deleted'], 0, $C['dec_point'], $C['thousands_sep']); ?>
  </td>
  <td class="last centered">
    <?php echo number_format($item['blacklisted'], 0, $C['dec_point'], $C['thousands_sep']); ?>
  </td>
</tr>