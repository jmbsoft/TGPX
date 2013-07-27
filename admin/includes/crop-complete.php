<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>
<script language="JavaScript">
if( window.opener.addPreview != undefined )
{
    window.opener.addPreview(<?php echo $gallery['gallery_id']; ?>, <?php echo $preview['id']; ?>, '<?php echo $_REQUEST['dimensions']; ?>', '<?php echo $preview['url']; ?>');
}
    
setTimeout("window.close()", 5000);
</script>

<div class="centered" style="padding: 10px;">
  <img src="<?php echo "{$preview['url']}?" . md5(uniqid(rand(), true)); ?>" border="0">
    
  <br />
  <br />
    
  <a href="" onclick="window.close(); return false;">Close Window</a>
  
  <br />
  <br />
  
  <i style="font-size: 8pt; color: #aaa;">This window will close automatically in 5 seconds</i>
</div>

</html>
</body>