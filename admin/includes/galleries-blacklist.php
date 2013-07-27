<?php
if( !defined('TGPX') ) die("Access denied");
include_once('includes/header.php');
?>

<script language="JavaScript">
</script>

<div style="padding: 10px;">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/galleries.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Enter the items to blacklist
    </div>

    <?php if( $GLOBALS['message'] ): ?>
    <div class="notice margin-bottom">
      <?php echo $GLOBALS['message']; ?>
    </div>
    <?php endif; ?>

    <?php if( $GLOBALS['errstr'] ): ?>
    <div class="alert margin-bottom">
      <?php echo $GLOBALS['errstr']; ?>
    </div>
    <?php endif; ?>

    <form action="index.php" method="POST" id="form">
      <fieldset>
        <legend>Blacklist Items</legend>

        <div class="fieldgroup">
            <label for="email">E-mail:</label>
            <input type="text" name="email" id="email" size="30" value="<?php echo $_REQUEST['email']; ?>" />
            <img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#email').val('');">
            <img src="images/preview-crop.png" border="0" title="Domain" alt="Domain" class="function click" onclick="$('#email').val(domainFromEmail($('#email').val()));">
        </div>

        <div class="fieldgroup">
            <label for="nickname">Gallery URL:</label>
            <input type="text" name="url" id="url" size="80" value="<?php echo $_REQUEST['gallery_url']; ?>" />
            <img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#url').val('');">
            <img src="images/preview-crop.png" border="0" title="Domain" alt="Domain" class="function click" onclick="$('#url').val(domainFromUrl($('#url').val()));">
        </div>

        <?php if( count($_REQUEST['dns']) ): ?>
        <div class="fieldgroup">
            <label for="nickname">DNS Server:</label>
            <input type="text" name="dns" id="dns" size="30" value="<?php echo $_REQUEST['dns'][0]; ?>" />
            <img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#dns').val('');">
        </div>
        <?php endif; ?>

        <div class="fieldgroup">
            <label for="nickname">Submitter IP:</label>
            <input type="text" name="submit_ip" id="submit_ip" size="30" value="<?php echo $_REQUEST['submit_ip']; ?>" />
            <img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#submit_ip').val('');">
        </div>

        <div class="fieldgroup">
            <label for="nickname">Gallery IP:</label>
            <input type="text" name="domain_ip" id="domain_ip" size="30" value="<?php echo $_REQUEST['gallery_ip']; ?>" />
            <img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#domain_ip').val('');">
        </div>

        <div class="fieldgroup">
            <label for="nickname">Reason:</label>
            <input type="text" name="reason" id="reason" size="70" value="<?php echo $_REQUEST['reason']; ?>" />
        </div>
      </fieldset>

      <div class="centered margin-top">
      <button type="submit">Blacklist Gallery</button>
    </div>

    <input type="hidden" name="gallery_id" value="<?php echo $_REQUEST['gallery_id']; ?>">
    <input type="hidden" name="r" value="txGalleryBlacklist">
    </form>
  </div>

</body>
</html>
