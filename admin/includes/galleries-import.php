<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<?php if( $DB->Count('SELECT COUNT(*) FROM `tx_categories`') < 1 ): ?>
<div class="alert centered">
  There must be at least one category defined before you can import galleries
</div>

</body>
</html>
<?php
return;
endif; ?>

<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="docs/galleries-import.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Import Galleries
    </div>

    <?php if( $GLOBALS['message'] ): ?>
    <div class="notice margin-top">
      <?php echo $GLOBALS['message']; ?>
    </div>
    <?php endif; ?>

    <?php if( $GLOBALS['errstr'] ): ?>
    <div class="alert margin-top">
      <?php echo $GLOBALS['errstr']; ?>
    </div>
    <?php endif; ?>

    <form action="index.php" method="POST">
    <div class="margin-top">
    To begin the importing process either upload a file containing your import data and named import.txt to the data directory of your TGPX installation or
    paste the import data into the text box below.

    <div class="centered margin-top">
    <div class="margin-bottom">
    <button type="submit" onclick="$('#type').val('input')">Analyze Input</button>
    &nbsp;&nbsp;&nbsp;
    <button type="submit" onclick="$('#type').val('file')">Analyze File</button>
    </div>

    <textarea name="input" rows="20" cols="160" wrap="off"></textarea>
    </div>
    </div>
    <input type="hidden" name="r" value="txShGalleryImportAnalyze">
    <input type="hidden" name="type" id="type" value="">
    </form>

    <div class="page-end"></div>
  </div>
</div>

</body>
</html>
