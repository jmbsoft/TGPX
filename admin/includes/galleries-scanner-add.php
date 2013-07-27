<?php
if( !defined('TGPX') ) die("Access denied");

$sizes = unserialize(GetValue('preview_sizes'));

$categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');
array_unshift($categories, array('category_id' => '', 'name' => 'ALL CATEGORIES'));

$sponsors =& $DB->FetchAll('SELECT * FROM `tx_sponsors` ORDER BY `name`');
array_unshift($sponsors, array('sponsor_id' => '', 'name' => 'ALL SPONSORS'));

$jscripts = array('includes/calendar.js');
$csses = array('includes/calendar.css');
include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#categories').bind('focus', function()
                                     {
                                         $('#categories')
                                         .css({position: 'absolute'})
                                         .attr({size: 30})
                                         .bind('blur', function()
                                                       {
                                                           $('#categories').css({position: 'static'}).attr({size: 2}).unbind('blur');
                                                       });
                                     });

      $('#sponsors').bind('focus', function()
                                     {
                                         $('#sponsors')
                                         .css({position: 'absolute'})
                                         .attr({size: 30})
                                         .bind('blur', function()
                                                       {
                                                           $('#sponsors').css({position: 'static'}).attr({size: 2}).unbind('blur');
                                                       });
                                     });
  });

<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/galleries-scanner.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this scanner configuration by making changes to the information below
      <?php else: ?>
      Add a scanner configuration by filling out the information below
      <?php endif; ?>
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

        <fieldset>
          <legend>General Settings</legend>

        <div class="fieldgroup">
            <label for="identifier">Identifier:</label>
            <input type="text" name="identifier" id="identifier" size="60" value="<?php echo $_REQUEST['identifier']; ?>" />
        </div>

        </fieldset>

        <fieldset>
          <legend>RSS Feeds</legend>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="import_rss" class="cblabel inline">
            <?php echo CheckBox('import_rss', 'checkbox', 1, $_REQUEST['import_rss']); ?> Import galleries from your configured RSS feeds before scanning</label>
          </div>

        </fieldset>

        <fieldset>
          <legend>Galleries To Scan</legend>

          <div class="fieldgroup">
            <label>Status:</label>
            <div style="padding-top: 3px">
            <?php echo CheckBox('status[unconfirmed]', 'checkbox', 1, $_REQUEST['status']['unconfirmed']); ?> <label class="cblabel inline" for="status[unconfirmed]">Unconfirmed</label> &nbsp;
            <?php echo CheckBox('status[pending]', 'checkbox', 1, $_REQUEST['status']['pending']); ?> <label class="cblabel inline" for="status[pending]">Pending</label> &nbsp;
            <?php echo CheckBox('status[approved]', 'checkbox', 1, $_REQUEST['status']['approved']); ?> <label class="cblabel inline" for="status[approved]">Approved</label> &nbsp;
            <?php echo CheckBox('status[used]', 'checkbox', 1, $_REQUEST['status']['used']); ?> <label class="cblabel inline" for="status[used]">Used</label> &nbsp;
            <?php echo CheckBox('status[holding]', 'checkbox', 1, $_REQUEST['status']['holding']); ?> <label class="cblabel inline" for="status[holding]">Holding</label> &nbsp;
            <?php echo CheckBox('status[disabled]', 'checkbox', 1, $_REQUEST['status']['disabled']); ?> <label class="cblabel inline" for="status[disabled]">Disabled</label>
            </div>
          </div>

          <div class="fieldgroup">
            <label>Type:</label>
            <div style="padding-top: 3px">
            <?php echo CheckBox('type[submitted]', 'checkbox', 1, $_REQUEST['type']['submitted']); ?> <label class="cblabel inline" for="type[submitted]">Submitted</label> &nbsp;
            <?php echo CheckBox('type[permanent]', 'checkbox', 1, $_REQUEST['type']['permanent']); ?> <label class="cblabel inline" for="type[permanent]">Permanent</label>
            </div>
          </div>

          <div class="fieldgroup">
            <label>Format:</label>
            <div style="padding-top: 3px">
            <?php echo CheckBox('format[pictures]', 'checkbox', 1, $_REQUEST['format']['pictures']); ?> <label class="cblabel inline" for="format[pictures]">Pictures</label> &nbsp;
            <?php echo CheckBox('format[movies]', 'checkbox', 1, $_REQUEST['format']['movies']); ?> <label class="cblabel inline" for="format[movies]">Movies</label>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="id_start">ID Range:</label>
            <input type="text" name="id_start" id="id_start" value="<?php echo $_REQUEST['id_start']; ?>">
            through
            <input type="text" name="id_end" id="id_end" value="<?php echo $_REQUEST['id_end']; ?>">
          </div>

          <div class="fieldgroup">
            <label for="date_added_start">Date Added Range:</label>
            <input type="text" name="date_added_start" id="date_added_start" size="20" value="<?php echo $_REQUEST['date_added_start']; ?>" class="calendarSelectDate" /> through
            <input type="text" name="date_added_end" id="date_added_end" size="20" value="<?php echo $_REQUEST['date_added_end']; ?>" class="calendarSelectDate" />
          </div>

          <div class="fieldgroup">
            <label for="date_approved_start">Date Approved Range:</label>
            <input type="text" name="date_approved_start" id="date_approved_start" size="20" value="<?php echo $_REQUEST['date_approved_start']; ?>" class="calendarSelectDate" /> through
            <input type="text" name="date_approved_end" id="date_approved_end" size="20" value="<?php echo $_REQUEST['date_approved_end']; ?>" class="calendarSelectDate" />
          </div>

          <div class="fieldgroup">
            <label for="date_scanned_start">Date Scanned Range:</label>
            <input type="text" name="date_scanned_start" id="date_scanned_start" size="20" value="<?php echo $_REQUEST['date_scanned_start']; ?>" class="calendarSelectDate" /> through
            <input type="text" name="date_scanned_end" id="date_scanned_end" size="20" value="<?php echo $_REQUEST['date_scanned_end']; ?>" class="calendarSelectDate" />
          </div>

          <div class="fieldgroup">
            <label for="sponsors[]">Sponsors:</label>
            <div id="sponsor_selects" style="float: left;">
            <?php

            if( is_array($_REQUEST['sponsors']) ):
                foreach( $_REQUEST['sponsors'] as $sponsor ):
            ?>

            <div style="margin-bottom: 3px;">
            <select name="sponsors[]">
            <?php
            echo OptionTagsAdv($sponsors, $sponsor, 'sponsor_id', 'name', 50);
            ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#sponsor_selects')" class="click-image" alt="Add Sponsor">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#sponsor_selects')" class="click-image" alt="Remove Sponsor">
            </div>

            <?php
                endforeach;
            else:
            ?>
            <div style="margin-bottom: 3px;">
            <select name="sponsors[]">
            <?php
            echo OptionTagsAdv($sponsors, null, 'sponsor_id', 'name', 50);
            ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#sponsor_selects')" class="click-image" alt="Add Sponsor">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#sponsor_selects')" class="click-image" alt="Remove Sponsor">
            </div>
            <?php endif; ?>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="categories[]">Categories:</label>
            <div id="category_selects" style="float: left;">
            <?php

            if( is_array($_REQUEST['categories']) ):
                foreach( $_REQUEST['categories'] as $category ):
            ?>

            <div style="margin-bottom: 3px;">
            <select name="categories[]">
            <?php
            echo OptionTagsAdv($categories, $category, 'category_id', 'name', 50);
            ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
            </div>

            <?php
                endforeach;
            else:
            ?>
            <div style="margin-bottom: 3px;">
            <select name="categories[]">
            <?php
            echo OptionTagsAdv($categories, null, 'category_id', 'name', 50);
            ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
            </div>
            <?php endif; ?>
            </div>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="only_partner" class="cblabel inline">
            <?php echo CheckBox('only_partner', 'checkbox', 1, $_REQUEST['only_partner']); ?> Only galleries submitted by partners</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="only_nothumb" class="cblabel inline">
            <?php echo CheckBox('only_nothumb', 'checkbox', 1, $_REQUEST['only_nothumb']); ?> Only galleries that do not currently have a preview thumbnail</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="only_zerothumb" class="cblabel inline">
            <?php echo CheckBox('only_zerothumb', 'checkbox', 1, $_REQUEST['only_zerothumb']); ?> Only galleries that currently have a zero thumbnail count</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="only_notscanned" class="cblabel inline">
            <?php echo CheckBox('only_notscanned', 'checkbox', 1, $_REQUEST['only_notscanned']); ?> Only galleries that have not yet been scanned</label>
          </div>
        </fieldset>


        <fieldset>
          <legend>Processing Options</legend>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_createpreview" class="cblabel inline">
            <?php echo CheckBox('process_createpreview', 'checkbox', 1, $_REQUEST['process_createpreview']); ?> Create a preview thumbnail for galleries that do not have one with the following dimensions</label>
          </div>

          <div class="fieldgroup">
            <label></label>
            <span style="margin-left: 30px;">
            Pictures:
            <select name="pics_preview_size">
              <option value="">Default</option>
              <option value="custom">Custom --&gt;</option>
              <?php echo OptionTags($sizes, $_REQUEST['pics_preview_size'], TRUE); ?>
            </select>
            <input type="text" name="pics_preview_size_custom" id="pics_preview_size_custom" size="10" value="" />
            <span style="padding-left: 5px;">WxH</span>
            </span>
          </div>

          <div class="fieldgroup">
            <label></label>
            <span style="margin-left: 30px;">
            Movies:
            <select name="movies_preview_size" style="margin-left: 5px;">
              <option value="">Default</option>
              <option value="custom">Custom --&gt;</option>
              <?php echo OptionTags($sizes, $_REQUEST['movies_preview_size'], TRUE); ?>
            </select>
            <input type="text" name="movies_preview_size_custom" id="movies_preview_size_custom" size="10" value="" />
            <span style="padding-left: 5px;">WxH</span>
            </span>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_redopreview" class="cblabel inline">
            <?php echo CheckBox('process_redopreview', 'checkbox', 1, $_REQUEST['process_redopreview']); ?> Re-create existing preview thumbnails</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_resizepreview" class="cblabel inline">
            <?php echo CheckBox('process_resizepreview', 'checkbox', 1, $_REQUEST['process_resizepreview']); ?> Resize existing preview thumbnails</label>
          </div>

          <div class="fieldgroup">
            <label></label>
             <label for="process_resize_keep_orig" class="cblabel inline" style="margin-left: 30px;">
            <?php echo CheckBox('process_resize_keep_orig', 'checkbox', 1, $_REQUEST['process_resize_keep_orig']); ?> Keep original size preview thumbnail</label>
          </div>

          <div class="fieldgroup">
            <label></label>
             <label for="process_resize_overwrite" class="cblabel inline" style="margin-left: 30px;">
            <?php echo CheckBox('process_resize_overwrite', 'checkbox', 1, $_REQUEST['process_resize_overwrite']); ?> Overwrite existing preview thumbnails that have the new size(s)</label>
          </div>

          <div class="fieldgroup">
            <label></label>
            <span style="margin-left: 30px;">
            Original Size:
            <select name="original_size">
              <?php echo OptionTags($sizes, $_REQUEST['original_size'], TRUE); ?>
            </select>
            </span>
          </div>

          <div class="fieldgroup">
            <label></label>
            <span style="margin-left: 30px;">
            New Size(s):
            <input type="text" name="new_size" id="new_size" size="30" value="<?php echo $_REQUEST['new_size']; ?>" />
            <span style="padding-left: 5px;">WxH,WxH,...</span>
            </span>
            </span>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_downloadpreview" class="cblabel inline">
            <?php echo CheckBox('process_downloadpreview', 'checkbox', 1, $_REQUEST['process_downloadpreview']); ?> Download preview thumbnails located on remote servers</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_downloadresize" class="cblabel inline">
            <?php echo CheckBox('process_downloadresize', 'checkbox', 1, $_REQUEST['process_downloadresize']); ?> Resize downloaded thumbnails to dimensions selected above</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_updatethumbcount" class="cblabel inline">
            <?php echo CheckBox('process_updatethumbcount', 'checkbox', 1, $_REQUEST['process_updatethumbcount']); ?> Update the thumbnail count for scanned galleries</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_updateformat" class="cblabel inline">
            <?php echo CheckBox('process_updateformat', 'checkbox', 1, $_REQUEST['process_updateformat']); ?> Update the gallery format for scanned galleries</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="enable_disabled" class="cblabel inline">
            <?php echo CheckBox('enable_disabled', 'checkbox', 1, $_REQUEST['enable_disabled']); ?> Re-enable disabled galleries that no longer have exceptions</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_rebuild" class="cblabel inline">
            <?php echo CheckBox('process_rebuild', 'checkbox', 1, $_REQUEST['process_rebuild']); ?> Rebuild the TGP pages when the scanner is completed</label>
          </div>

          <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="process_emailadmin" class="cblabel inline">
            <?php echo CheckBox('process_emailadmin', 'checkbox', 1, $_REQUEST['process_emailadmin']); ?> Send an e-mail to administrators when the scanner is completed</label>
          </div>

        </fieldset>

        <fieldset>
          <legend>Actions</legend>

          <div class="fieldgroup">
          <label style="width: 250px;">Connection errors:</label>
          <select name="action_connect">
          <?php
          $actions = array('0x00000000' => 'Ignore',
                           '0x00000001' => 'Display in report only',
                           '0x00000002' => 'Change gallery status to disabled',
                           '0x00000004' => 'Delete gallery from database',
                           '0x00000008' => 'Delete gallery and blacklist');

          echo OptionTags($actions, $_REQUEST['action_connect']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">Broken URLs:</label>
          <select name="action_broken">
          <?php
            echo OptionTags($actions, $_REQUEST['action_broken']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">Forwarding URLs:</label>
          <select name="action_forward">
          <?php
            echo OptionTags($actions, $_REQUEST['action_forward']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">Blacklisted data:</label>
          <select name="action_blacklist">
          <?php
            echo OptionTags($actions, $_REQUEST['action_blacklist']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">No reciprocal link:</label>
          <select name="action_norecip">
          <?php
            echo OptionTags($actions, $_REQUEST['action_norecip']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">No 2257 code:</label>
          <select name="action_no2257">
          <?php
            echo OptionTags($actions, $_REQUEST['action_no2257']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">Excessive Links:</label>
          <select name="action_excessivelinks">
          <?php
            echo OptionTags($actions, $_REQUEST['action_excessivelinks']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">Thumb count change:</label>
          <select name="action_thumbchange">
          <?php
            echo OptionTags($actions, $_REQUEST['action_thumbchange']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">Page changed:</label>
          <select name="action_pagechange">
          <?php
            echo OptionTags($actions, $_REQUEST['action_pagechange']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">Content on different server:</label>
          <select name="action_content_server">
          <?php
            echo OptionTags($actions, $_REQUEST['action_content_server']);
          ?>
          </select>
          </div>

          <div class="fieldgroup">
          <label style="width: 250px;">Bad gallery format:</label>
          <select name="action_badformat">
          <?php
            echo OptionTags($actions, $_REQUEST['action_badformat']);
          ?>
          </select>
          </div>

        </fieldset>

    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Scanner Configuration</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txScannerConfigEdit' : 'txScannerConfigAdd'); ?>">

    <?php if( $editing ): ?>
    <input type="hidden" name="config_id" value="<?php echo $_REQUEST['config_id']; ?>">
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>



</body>
</html>
