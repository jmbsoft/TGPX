<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>

<xmp style="font-size: 9pt; width: 95%; padding: 10px;">Line   Import Data
----   -----------
<?PHP nl2br(htmlspecialchars(readfile($files[$_REQUEST['type']]))); ?>
</xmp>

</body>
</html>
