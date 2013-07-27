<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=ISO-8859-1">
  <title><?php if( isset($title) ): echo htmlspecialchars($title); else: ?>TGPX Administration<?php endif; ?></title>
  <link rel="stylesheet" type="text/css" href="includes/admin.css" />
  <?php
    if( isset($csses) ):
        foreach($csses as $css):
  ?>
  <link rel="stylesheet" type="text/css" href="<?php echo $css; ?>" />
  <?php
        endforeach;
    endif;
  ?>
  <script type="text/javascript" src="../includes/jquery.js"></script>
  <script type="text/javascript" src="../includes/interface.js"></script>
  <script type="text/javascript" src="../includes/form.js"></script>
  <script type="text/javascript" src="includes/admin.js"></script>
  <?php
    if( isset($jscripts) ):
        foreach($jscripts as $jscript):
  ?>
  <script type="text/javascript" src="<?php echo $jscript; ?>"></script>
  <?php
        endforeach;
    endif;
  ?>
</head>
<body>
