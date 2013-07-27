<?php
if( !defined('TGPX') ) die("Access denied");

$pages =& $DB->FetchAll('SELECT `page_id`,`page_url` FROM `tx_pages` ORDER BY `page_url`');

include_once('includes/header.php');
include_once('includes/menu.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#load').bind('click', function()
                               {
                                   var position = $.iUtil.getPositionLite(this);
                                   $('#save_select:visible').BlindUp(300);
                                   $('#load_select:visible').BlindUp(300);
                                   $('#load_select:hidden').css({top: position.y + 25, left: position.x}).BlindDown(300);
                               });

      $('#load_select_cancel').bind('click', function()
                                             {
                                                 $('#load_select:visible').BlindUp(300);
                                             });

      $('#load_select_ok').bind('click', function()
                                         {
                                             var selected = $('#load_template :selected').val();

                                             if( !selected )
                                             {
                                                 alert('Please select the page you want to load');
                                                 return false;
                                             }

                                             $('#code').val('');
                                             $('#page_id').val($('#load_template :selected').val());
                                             $('#r').val('txPageTemplateLoad');
                                             $('#form').submit();
                                         });

      $('#refresh').bind('click', function()
                                  {
                                     $('#code').val('');
                                     $('#r').val('txPageTemplateLoad');
                                     $('#form').submit();
                                 });

      $('#save').bind('click', function()
                               {
                                   if( confirm('Are you sure you want to save your changes?') )
                                   {
                                       $('#r').val('txPageTemplateSave');
                                       $('#build').val(0);
                                       $('#form').submit();
                                   }
                               });

      $('#save_build').bind('click', function()
                                     {
                                         if( confirm('Are you sure you want to save your changes and build this page?') )
                                         {
                                             $('#r').val('txPageTemplateSave');
                                             $('#build').val(1);
                                             $('#form').submit();
                                         }
                                     });

      $('#save_options').bind('click', function()
                                       {
                                           var position = $.iUtil.getPositionLite(this);
                                           $('#load_select:visible').BlindUp(300);
                                           $('#save_select:visible').BlindUp(300);
                                           $('#save_select:hidden').css({top: position.y + 25, left: position.x}).BlindDown(300);
                                       });

      $('#save_select_cancel').bind('click', function()
                                             {
                                                 $('#save_select:visible').BlindUp(300);
                                             });

      $('#save_select_ok').bind('click', function()
                                         {
                                             var selected = $('#save_template :selected');

                                             if( selected.length < 1 )
                                             {
                                                 alert('Please at least one page to save this template for');
                                                 return false;
                                             }

                                             if( confirm('Are you sure you want to save your changes?') )
                                             {
                                                 var page_ids = new Array();
                                                 selected.each(function() { page_ids.push(this.value); });

                                                 if( $$('build_on_save').checked )
                                                 {
                                                     $('#build').val(1);
                                                 }

                                                 $('#page_id').val(page_ids.join(','));
                                                 $('#r').val('txPageTemplateSave');
                                                 $('#form').submit();
                                             }
                                         });
      // Min-width for IE
      if( $.browser.msie )
      {
          var dimensions = $('#load_template').dimensions();
          if( dimensions.w < 300 )
          {
              $('#load_template').css({width: '300px'});
          }

          dimensions = $('#save_template').dimensions();
          if( dimensions.w < 300 )
          {
              $('#save_template').css({width: '300px'});
          }
      }

<?PHP if( !$_REQUEST['page_id'] ): ?>
      var position = $.iUtil.getPositionLite($$('load'));
      $('#load_select').css({top: position.y + 25, left: position.x}).show();
<?php endif; ?>
  });
</script>


<?php if( count($pages) < 1 ): ?>
<div class="alert centered">
  You must setup at least one TGP page through the <a href="index.php?r=txShPages">Manage Pages</a> interface before you can edit the TGP page templates
</div>

</body>
</html>
<?php return; endif; ?>


<?php if( isset($GLOBALS['no_access_list']) ): ?>
<div class="warn centered">
  ENHANCED SECURITY: You have not yet setup an access list, which will add increased security to your control panel.
  <a href="docs/access-list.html" target="_blank"><img src="images/help-small.png" border="0" width="12" height="12" style="position: relative; top: 1px; left: 10px;"></a>
</div>
<?php endif; ?>


<div id="main-content">
  <div id="centered-content" class="max-width">
    <div class="heading">
      <div class="heading-icon">
        <a href="index.php?r=txShPageTemplateWizard" onclick="return openWizard();"><img src="images/wizard.png" border="0" alt="Template Wizard" title="Template Wizard"></a>
        &nbsp;
        <a href="docs/templates-tgp.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      TGP Page Templates
    </div>

    <div class="margin-top" style="padding-left: 5px;">
    <img id="load" src="images/template-load.png" border="0" width="22" height="22" alt="Load Template" title="Load Template" class="click-image" style="margin-right: 30px;">
    <?php if( $_REQUEST['page_id'] ): ?>
    <img id="refresh" src="images/refresh.png" border="0" width="22" height="22" alt="Refresh" title="Refresh" class="click-image" style="margin-right: 30px;">
    <img id="save" src="images/template-save.png" border="0" width="22" height="22" alt="Save Template" title="Save Template" class="click-image" style="margin-right: 30px;">
    <img id="save_build" src="images/template-save-build.png" border="0" width="22" height="22" alt="Save Template and Build" title="Save Template and Build" class="click-image" style="margin-right: 30px;">
    <img id="save_options" src="images/template-save-options.png" border="0" width="22" height="22" alt="Save Template With Options" title="Save Template With Options" class="click-image" style="margin-right: 30px;">
    <?php endif; ?>
    <a href="index.php?r=txShPageTemplateReplace" class="window {title: 'Template Search and Replace'}">
    <img src="images/template-replace.png" border="0" width="22" height="22" alt="Search and Replace" title="Search and Replace"></a>
    </div>

    <form action="index.php" method="POST" id="form">

    <?php if( $_REQUEST['page_id'] ): ?>
    <br />

    <?php if( !empty($GLOBALS['warnstr']) ): ?>
    <div class="warn margin-bottom">
      <?php echo $GLOBALS['warnstr']; ?>
    </div>
    <?php endif; ?>

    <?php if( isset($GLOBALS['errstr']) ): ?>
    <div class="alert margin-bottom">
      <?php echo $GLOBALS['errstr']; ?>
    </div>
    <?php endif; ?>

    <?php if( $GLOBALS['message'] ): ?>
    <div class="notice margin-bottom">
      <?php echo $GLOBALS['message']; ?>
    </div>
    <?php endif; ?>


    <div class="heading">
      Editing template for <a href="<?php echo $_REQUEST['page']['page_url']; ?>" target="_blank" style="text-decoration: none;"><?php echo $_REQUEST['page']['page_url']; ?></a>
    </div>

    <div class="centered margin-top" style="font-weight: bold">
    <textarea name="code" id="code" rows="50" style="width: 930px;" wrap="off"><?PHP echo $_REQUEST['code']; ?></textarea>
    </div>
    <?php endif; ?>

    <input type="hidden" id="r" name="r" value="">
    <input type="hidden" id="build" name="build" value="0">
    <input type="hidden" name="page_id" id="page_id" value="<?PHP echo $_REQUEST['page_id']; ?>" />
    </form>

    <?php if( $_REQUEST['page_id'] ): ?>
    <div class="page-end"></div>
    <?php endif; ?>
  </div>
</div>

<!-- TEMPLATE LOAD SELECTOR -->
<div style="display: none; position: absolute; background-color: #ececec; border: 1px solid #afafaf; padding: 10px;" id="load_select">
  <div style="float: left; font-weight: bold;">
    Select template to load:
  </div>
  <div style="padding-bottom: 5px; text-align: right">
    <img src="images/check.png" alt="OK" title="OK" style="margin-right: 10px;" id="load_select_ok" class="click-image">
    <img src="images/x.png" alt="Cancel" title="Cancel" id="load_select_cancel" class="click-image">
  </div>
  <select id="load_template" size="30" style="min-width: 300px;">
    <?php
    echo OptionTagsAdv($pages, $_REQUEST['page_id'], 'page_id', 'page_url');
    ?>
  </select>
</div>


<!-- TEMPLATE SAVE WITH OPTIONS SELECTOR -->
<div style="display: none; position: absolute; background-color: #ececec; border: 1px solid #afafaf; padding: 10px;" id="save_select">
  <div style="float: left;">
    <input type="checkbox" class="checkbox" id="build_on_save" value="1"> <label for="build_on_save" style="font-weight: normal">Build these pages after save</label>
  </div>
  <div style="padding-bottom: 5px; text-align: right">
    <img src="images/check.png" alt="OK" title="OK" style="margin-right: 10px;" id="save_select_ok" class="click-image">
    <img src="images/x.png" alt="Cancel" title="Cancel" id="save_select_cancel" class="click-image">
  </div>
  <div style="font-weight: bold;">
  Select templates to save:
  </div>
  <select name="save_template[]" id="save_template" size="30" style="min-width: 300px;" multiple="multiple">
    <?php
    $pages =& $DB->FetchAll('SELECT `page_id`,`page_url` FROM `tx_pages` ORDER BY `page_url`');
    echo OptionTagsAdv($pages, $_REQUEST['page_id'], 'page_id', 'page_url');
    ?>
  </select>
</div>


</body>
</html>
