<?php
if( !defined('TGPX') ) die("Access denied");
include_once('includes/header.php');
?>

<style>
#results td {
    height: 20px;
    padding-left: 5px;
}

.changed {
  color: red;
  padding-left: 20px;
}
</style>

<script language="JavaScript">

</script>

<div style="padding: 0px 10px 10px 10px;">

  <form>
  <fieldset>
    <legend>Scan Results</legend>
  
      <?php if( $scan['success'] ): ?>
      <table id="results" width="100%">
        <tr>
          <td width="235" align="right">
            <b>HTTP Status</b>
          </td>
          <td>
            <?php echo $scan['status']; ?>
          </td>
        </tr>
        <tr>
          <td width="235" align="right">
            <b>IP Address</b>
          </td>
          <td>
            <?php
            echo $scan['gallery_ip'];
            
            if( !empty($gallery['gallery_ip']) && $scan['gallery_ip'] != $gallery['gallery_ip'] )
            {
                echo "<span class=\"changed\">Changed from {$gallery['gallery_ip']}</span>";
            }
            ?>
          </td>
        </tr>
        <tr>
          <td width="235" align="right">
            <b>Format</b>
          </td>
          <td>
            <?php 
            echo ucfirst($scan['format']);
            
            if( $scan['bad_format'] )
            {
                 echo "<span class=\"changed\">[Not Allowed]</span>";
            }
            
            if( $scan['format'] != $gallery['format'] )
            {
                echo "<span class=\"changed\">[Has Changed]</span>";
            }
            ?>
          </td>
        </tr>
        <tr>
          <td width="235" align="right">
            <b>Thumbnails</b>
          </td>
          <td>
            <?php 
            echo $scan['thumbnails'];
            
            if( $scan['thumbnails'] != $gallery['thumbnails'] )
            {
                echo "<span class=\"changed\">[Changed from {$gallery['thumbnails']}]</span>";
            }
            ?>
          </td>
        </tr>        
        <tr>
          <td width="235" align="right">
            <b>Links</b>
          </td>
          <td>
            <?php echo $scan['links']; ?>
          </td>
        </tr>
        <tr>
          <td width="235" align="right">
            <b>Page Content</b>
          </td>
          <td>
            <?php
            echo number_format($scan['bytes'], 0, $C['dec_point'], $C['thousands_sep']) . " bytes";
            
            if( !empty($gallery['page_hash']) && $scan['page_hash'] != $gallery['page_hash'] )
            {
                echo "<span class=\"changed\">[Has Changed]</span>";
            }
            ?>
          </td>
        </tr>
        <tr>
          <td width="235" align="right">
            <img src="images/<?php echo ($scan['has_recip'] ? 'check' : 'x'); ?>.png">
          </td>
          <td>
            Reciprocal link found
          </td>
        </tr>        
        <tr>
          <td width="235" align="right">
            <img src="images/<?php echo ($scan['has_2257'] ? 'check' : 'x'); ?>.png">
          </td>
          <td>
            2257 code found
          </td>
        </tr>
        <tr>
          <td width="235" align="right">
            <img src="images/<?php echo ($blacklisted !== FALSE ? 'x' : 'check'); ?>.png">
          </td>
          <td>
            No blacklisted data found
          </td>
        </tr>
        <tr>
          <td width="235" align="right">
            <img src="images/<?php echo ($scan['server_match'] ? 'check' : 'x'); ?>.png">
          </td>
          <td>
            Content hosted on same server as gallery
          </td>
        </tr>
      </table>
      
      <?php else: // if( $scan['success'] ) ?>
      
      <div class="alert">
        <?php echo $scan['errstr']; ?>
      </div>
      
      <?php endif; // if( $scan['success'] ) ?>
  
  </fieldset>
  
  <?php if( $scan['success'] ): ?>
  <fieldset>
    <legend>HTTP Headers</legend>
    
    <div style="font-size: 9pt; font-family: monospace, fixed; width: 95%; overflow: auto; background-color: #ececec; padding: 10px;"><?php echo nl2br($scan['headers']); ?></div>
    
  </fieldset>
  
  <fieldset>
    <legend>Gallery HTML</legend>
    
    <div style="font-size: 9pt; font-family: monospace, fixed; width: 95%; height: 300px; overflow: auto; background-color: #ececec; padding: 10px;"><?php echo nl2br($scan['html']); ?></div>
  </fieldset>
  <?php endif; // if( $scan['success'] ) ?>
  
  </form>

</div>  

</body>
</html>
