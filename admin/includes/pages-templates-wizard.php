<?php
if( !defined('TGPX') ) die("Access denied");

$categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');
$sizes = unserialize(GetValue('preview_sizes'));
$sponsors =& $DB->FetchAll('SELECT * FROM `tx_sponsors` ORDER BY `name`');

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#mix_method').bind('change', function()
                                      {
                                          if( $(this).val() == 'random' )
                                              $('#mix_value').hide();
                                          else
                                              $('#mix_value').show();
                                      });

      $('#inrows').bind('change', function()
                                  {
                                      if( $(this).val() == 'yes' )
                                          $('#perrow_span').show();
                                      else
                                          $('#perrow_span').hide();
                                  });

      $('#agetype').bind('change', function()
                                  {
                                      if( $(this).val() == 'between' )
                                          $('#betweenage').show();
                                      else
                                          $('#betweenage').hide();

                                      if( $(this).val() == '' )
                                          $('#age').hide();
                                      else
                                          $('#age').show();
                                  });

      $('#perm_agetype').bind('change', function()
                                  {
                                      if( $(this).val() == 'between' )
                                          $('#perm_betweenage').show();
                                      else
                                          $('#perm_betweenage').hide();

                                      if( $(this).val() == '' )
                                          $('#perm_age').hide();
                                      else
                                          $('#perm_age').show();
                                  });

      $('#previewsize').bind('change', function()
                                       {
                                           if( $(this).val() == '' )
                                               $('#customsizespan').show();
                                           else
                                               $('#customsizespan').hide();
                                       });

      $('#amount').bind('keyup', function () { updatePercent('#submitted_percent', '#permanent_percent'); });

      $('#agetype').trigger('change');
      $('#previewsize').trigger('change');
      $('#inrows').trigger('change');
      updatePercent('#submitted_percent', '#permanent_percent');
  });

function updatePercent(src, dest)
{
    var srcPercent = parseInt($(src).val());
    var destPercent = 100 - srcPercent;

    $(dest+' option[@value='+destPercent+']').attr({selected: 'selected'});

    if( srcPercent > 0 && destPercent > 0 )
        $('#mix_div').show();
    else
        $('#mix_div').hide();

    if( $('#submitted_percent').val() > 0 )
        $('#sub_fields').show();
    else
        $('#sub_fields').hide();

    if( $('#permanent_percent').val() > 0 )
        $('#perm_fields').show();
    else
        $('#perm_fields').hide();

    var amount = parseInt($('#amount').val());
    var perm_percent = $('#permanent_percent').val();
    var sub_percent = $('#submitted_percent').val();
    var num_submitted = Math.round(amount * (sub_percent / 100));

    $('#num_submitted').html(num_submitted);
    $('#num_permanent').html(amount - num_submitted);
}
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/templates-wizard.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Use this interface to generate {galleries} template code for your TGP pages
    </div>

        <?php if( $GLOBALS['message'] ): ?>
        <div class="notice margin-bottom">
          <?php echo $GLOBALS['message']; ?>
        </div>
        <?php endif; ?>

        <fieldset>
          <legend>General Options</legend>

          <div class="fieldgroup">
            <label for="amount">Number of Galleries:</label>
            <input type="text" name="amount" id="amount" value="50" size="5">
          </div>

          <div class="fieldgroup">
            <label for="display">Display As:</label>
            <select name="display" id="display">
              <option value="thumbs">Thumbnails</option>
              <option value="text">Text Links</option>
              <option value="xml">RSS Feed</option>
              <option value="xmlthumbs">RSS Feed With Thumbs</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="inrows">Display In Rows:</label>
            <select name="inrows" id="inrows">
              <option value="no">No</option>
              <option value="yes">Yes</option>
            </select>
            <span id="perrow_span" style="display: none;">
            <input type="text" name="perrow" id="perrow" size="5" value="5"> galleries per row
            </span>
          </div>

          <div class="fieldgroup">
            <label for="type">Type of Galleries:</label>
            <div style="float: left">
            <select name="submitted_percent" id="submitted_percent" onchange="updatePercent(this, '#permanent_percent')">
              <?php foreach( range(100,0) as $percent ): ?>
              <option value="<?php echo $percent; ?>"><?php echo "$percent%"; ?></option>
              <?php endforeach; ?>
            </select> Submitted (<span id="num_submitted">50</span>)<br />
            <select name="permanent_percent" id="permanent_percent" onchange="updatePercent(this, '#submitted_percent')">
              <?php foreach( range(100,0) as $percent ): ?>
              <option value="<?php echo $percent; ?>"<?php if( $percent == 0 ): ?> selected='selected'<?php endif; ?>><?php echo "$percent%"; ?></option>
              <?php endforeach; ?>
            </select> Permanent (<span id="num_permanent">0</span>)<br />
            </div>
          </div>

          <div class="fieldgroup" id="mix_div">
            <label>Mix Method:</label>
            <select name="mix_method" id="mix_method">
              <option value="random">Random</option>
              <option value="interval">Specific interval --&gt;</option>
              <option value="locations">Specific locations --&gt;</option>
            </select>
            <input type="text" name="mix_value" id="mix_value" size="10" style="display: none">
          </div>

        </fieldset>

        <fieldset id="sub_fields">
          <legend>Submitted Gallery Options</legend>

          <div class="fieldgroup">
            <label for="getnew">Add New Galleries:</label>
            <select name="s[getnew]" id="getnew">
              <option value="true">Yes, add new galleries in this section</option>
              <option value="allowused">Yes, add new galleries in this section but allow used galleries if there are not enough new</option>
              <option value="false">No, only display previously used galleries in this section</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="preview">Preview Thumbnail:</label>
            <select name="s[preview]" id="preview">
              <option value="true">Gallery must have a preview thumbnail</option>
              <option value="false">Gallery must NOT have a preview thumbnail</option>
              <option value="any">Don't care</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="previewsize">Preview Size:</label>
            <select name="s[previewsize]" id="previewsize">
              <option value="">Custom --&gt;</option>
              <?php echo OptionTags($sizes, null, TRUE); ?>
            </select>
            <span id="customsizespan">
            <input type="text" name="s[customsize]" size="8"> &nbsp; WxH
            </span>
          </div>

          <div class="fieldgroup">
            <label for="description">Require Description:</label>
            <select name="s[description]" id="description">
              <option value="false">No, description is not required for galleries in this section</option>
              <option value="true">Yes, description is required for galleries in this section</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="format">Format of Galleries:</label>
            <select name="s[format]" id="format">
              <option value="pictures">Picture galleries only</option>
              <option value="movies">Movie galleries only</option>
              <option value="any">Don't care</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="categories">Categories:</label>
            <div id="category_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="s[categories][]">
              <option value="MIXED">ANY CATEGORY</option>
              <?php
              echo OptionTagsAdv($categories, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="categories">Exclude Categories:</label>
            <div id="exclude_category_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="s[exclude_categories][]">
              <option value="">NONE</option>
              <?php
              echo OptionTagsAdv($categories, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#exclude_category_selects')" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#exclude_category_selects')" class="click-image" alt="Remove Category">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="sponsors">Sponsors:</label>
            <div id="sponsor_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="s[sponsors][]">
              <option value="">ANY SPONSOR</option>
              <?php
              echo OptionTagsAdv($sponsors, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#sponsor_selects')" class="click-image" alt="Add Sponsor">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#sponsor_selects')" class="click-image" alt="Remove Sponsor">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="exclude_sponsors">Exclude Sponsors:</label>
            <div id="exclude_sponsor_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="s[exclude_sponsors][]">
              <option value="">NONE</option>
              <?php
              echo OptionTagsAdv($sponsors, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#exclude_sponsor_selects')" class="click-image" alt="Add Sponsor">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#exclude_sponsor_selects')" class="click-image" alt="Remove Sponsor">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="weight">Gallery Weight:</label>
            <select name="s[weight]" id="weight">
              <option value="">Don't care</option>
              <option value="=">Equals</option>
              <option value="!=">Does not equal</option>
              <option value="<">Less than</option>
              <option value=">">Greater than</option>
              <option value="<=">Less than or equal to</option>
              <option value=">=">Greater than or equal to</option>
            </select>
            <input type="text" name="s[weight_value]" id="weight_value" value="" size="5">
          </div>

          <div class="fieldgroup">
            <label for="agetype">Gallery Age:</label>
            <select name="s[agetype]" id="agetype">
              <option value="">Don't care</option>
              <option value="exact">Exactly</option>
              <option value="atleast">At least</option>
              <option value="atmost">At most</option>
              <option value="between">Between</option>
            </select>
            <select name="s[age]" id="age" style="display: none;">
              <?php foreach( range(0, 30) as $day ): ?>
              <option value="<?php echo $day; ?>"><?php echo $day; ?> day<?php if($day != 1): ?>s<?php endif; ?> old <?php if($day == 0): ?>(today)<?php endif;?></option>
              <?php endforeach; ?>
            </select>
            <span id="betweenage" style="display: none;">
            and
            <select name="s[endage]" id="endage">
              <?php foreach( range(0, 30) as $day ): ?>
              <option value="<?php echo $day; ?>"><?php echo $day; ?> day<?php if($day != 1): ?>s<?php endif; ?> old <?php if($day == 0): ?>(today)<?php endif;?></option>
              <?php endforeach; ?>
            </select>
            </span>
          </div>

          <div class="fieldgroup">
            <label for="order">Sort new galleries by:</label>
            <div id="sortnew_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="s[order][]" id="order">
              <option value="date_approved">Date approved (Least recently to most recently)</option>
              <option value="date_approved DESC">Date approved (Most recently to least recently)</option>
              <option value="date_displayed">Date gallery was selected for display (least recent to most recent)</option>
              <option value="date_displayed DESC">Date gallery was selected for display (most recent to least recent)</option>
              <option value="weight">Weight (Smallest to largest)</option>
              <option value="weight DESC">Weight (Largest to smallest)</option>
              <option value="times_selected">Times gallery has been selected for display (least to most)</option>
              <option value="times_selected DESC">Times gallery has been selected for display (most to least)</option>
              <option value="used_counter">Used counter (least to most)</option>
              <option value="used_counter DESC">Used counter (most to least)</option>
              <option value="build_counter">Build counter (least to most)</option>
              <option value="build_counter DESC">Build counter (most to least)</option>
              <option value="clicks">Number of clicks (least to most)</option>
              <option value="clicks DESC">Number of clicks (most to least)</option>
              <option value="(clicks/build_counter)">Productivity - build counter (lowest to highest)</option>
              <option value="(clicks/build_counter) DESC">Productivity - build counter (highest to lowest)</option>
              <option value="(clicks/used_counter)">Productivity - used counter (lowest to highest)</option>
              <option value="(clicks/used_counter) DESC">Productivity - used counter (highest to lowest)</option>
              <option value="RAND()">Randomly</option>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#sortnew_selects')" class="click-image" alt="Add Sort">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#sortnew_selects')" class="click-image" alt="Remove Sort">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="reorder">Sort used galleries by:</label>
            <div id="sortused_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="s[reorder][]" id="reorder">
              <option value="date_approved">Date approved (Least recently to most recently)</option>
              <option value="date_approved DESC">Date approved (Most recently to least recently)</option>
              <option value="date_displayed">Date gallery was selected for display (least recent to most recent)</option>
              <option value="date_displayed DESC">Date gallery was selected for display (most recent to least recent)</option>
              <option value="weight">Weight (Smallest to largest)</option>
              <option value="weight DESC">Weight (Largest to smallest)</option>
              <option value="times_selected">Times gallery has been selected for display (least to most)</option>
              <option value="times_selected DESC">Times gallery has been selected for display (most to least)</option>
              <option value="used_counter">Used counter (least to most)</option>
              <option value="used_counter DESC">Used counter (most to least)</option>
              <option value="build_counter">Build counter (least to most)</option>
              <option value="build_counter DESC">Build counter (most to least)</option>
              <option value="clicks">Number of clicks (least to most)</option>
              <option value="clicks DESC">Number of clicks (most to least)</option>
              <option value="(clicks/build_counter)">Productivity - build counter (lowest to highest)</option>
              <option value="(clicks/build_counter) DESC">Productivity - build counter (highest to lowest)</option>
              <option value="(clicks/used_counter)">Productivity - used counter (lowest to highest)</option>
              <option value="(clicks/used_counter) DESC">Productivity - used counter (highest to lowest)</option>
              <option value="RAND()">Randomly</option>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#sortused_selects')" class="click-image" alt="Add Sort">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#sortused_selects')" class="click-image" alt="Remove Sort">
            </div>
            </div>
          </div>
        </fieldset>

        <fieldset id="perm_fields" style="display: none";>
          <legend>Permanent Gallery Options</legend>

          <div class="fieldgroup">
            <label for="getnew">Add New Galleries:</label>
            <select name="p[getnew]" id="getnew">
              <option value="true">Yes, add new galleries in this section</option>
              <option value="allowused">Yes, add new galleries in this section but allow used galleries if there are not enough new</option>
              <option value="false">No, only display previously used galleries in this section</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="preview">Preview Thumbnail:</label>
            <select name="p[preview]" id="preview">
              <option value="true">Gallery must have a preview thumbnail</option>
              <option value="false">Gallery must NOT have a preview thumbnail</option>
              <option value="any">Don't care</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="previewsize">Preview Size:</label>
            <select name="p[previewsize]" id="previewsize">
              <option value="">Custom --&gt;</option>
              <?php echo OptionTags($sizes, null, TRUE); ?>
            </select>
            <span id="customsizespan">
            <input type="text" name="p[customsize]" size="8"> &nbsp; WxH
            </span>
          </div>

          <div class="fieldgroup">
            <label for="description">Require Description:</label>
            <select name="p[description]" id="description">
              <option value="false">No, description is not required for galleries in this section</option>
              <option value="true">Yes, description is required for galleries in this section</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="format">Format of Galleries:</label>
            <select name="p[format]" id="format">
              <option value="pictures">Picture galleries only</option>
              <option value="movies">Movie galleries only</option>
              <option value="any">Don't care</option>
            </select>
          </div>

          <div class="fieldgroup">
            <label for="categories">Categories:</label>
            <div id="perm_category_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="p[categories][]">
              <option value="MIXED">ANY CATEGORY</option>
              <?php
              echo OptionTagsAdv($categories, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#perm_category_selects')" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#perm_category_selects')" class="click-image" alt="Remove Category">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="categories">Exclude Categories:</label>
            <div id="perm_exclude_category_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="p[exclude_categories][]">
              <option value="">NONE</option>
              <?php
              echo OptionTagsAdv($categories, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#perm_exclude_category_selects')" class="click-image" alt="Add Category">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#perm_exclude_category_selects')" class="click-image" alt="Remove Category">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="sponsors">Sponsors:</label>
            <div id="perm_sponsor_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="p[sponsors][]">
              <option value="">ANY SPONSOR</option>
              <?php
              echo OptionTagsAdv($sponsors, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#perm_sponsor_selects')" class="click-image" alt="Add Sponsor">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#perm_sponsor_selects')" class="click-image" alt="Remove Sponsor">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="exclude_sponsors">Exclude Sponsors:</label>
            <div id="perm_exclude_sponsor_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="p[exclude_sponsors][]">
              <option value="">NONE</option>
              <?php
              echo OptionTagsAdv($sponsors, null, 'name', 'name', 50);
              ?>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#perm_exclude_sponsor_selects')" class="click-image" alt="Add Sponsor">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#perm_exclude_sponsor_selects')" class="click-image" alt="Remove Sponsor">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="weight">Gallery Weight:</label>
            <select name="p[weight]" id="weight">
              <option value="">Don't care</option>
              <option value="=">Equals</option>
              <option value="!=">Does not equal</option>
              <option value="<">Less than</option>
              <option value=">">Greater than</option>
              <option value="<=">Less than or equal to</option>
              <option value=">=">Greater than or equal to</option>
            </select>
            <input type="text" name="p[weight_value]" id="weight_value" value="" size="5">
          </div>

          <div class="fieldgroup">
            <label for="perm_agetype">Gallery Age:</label>
            <select name="p[agetype]" id="perm_agetype">
              <option value="">Don't care</option>
              <option value="exact">Exactly</option>
              <option value="atleast">At least</option>
              <option value="atmost">At most</option>
              <option value="between">Between</option>
            </select>
            <select name="p[age]" id="perm_age" style="display: none;">
              <?php foreach( range(0, 30) as $day ): ?>
              <option value="<?php echo $day; ?>"><?php echo $day; ?> day<?php if($day != 1): ?>s<?php endif; ?> old <?php if($day == 0): ?>(today)<?php endif;?></option>
              <?php endforeach; ?>
            </select>
            <span id="perm_betweenage" style="display: none;">
            and
            <select name="p[endage]" id="perm_endage">
              <?php foreach( range(0, 30) as $day ): ?>
              <option value="<?php echo $day; ?>"><?php echo $day; ?> day<?php if($day != 1): ?>s<?php endif; ?> old <?php if($day == 0): ?>(today)<?php endif;?></option>
              <?php endforeach; ?>
            </select>
            </span>
          </div>

          <div class="fieldgroup">
            <label for="order">Sort new galleries by:</label>
            <div id="perm_sortnew_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="p[order][]" id="order">
              <option value="date_approved">Date approved (Least recently to most recently)</option>
              <option value="date_approved DESC">Date approved (Most recently to least recently)</option>
              <option value="date_displayed">Date gallery was selected for display (least recent to most recent)</option>
              <option value="date_displayed DESC">Date gallery was selected for display (most recent to least recent)</option>
              <option value="weight">Weight (Smallest to largest)</option>
              <option value="weight DESC">Weight (Largest to smallest)</option>
              <option value="times_selected">Times gallery has been selected for display (least to most)</option>
              <option value="times_selected DESC">Times gallery has been selected for display (most to least)</option>
              <option value="used_counter">Used counter (least to most)</option>
              <option value="used_counter DESC">Used counter (most to least)</option>
              <option value="build_counter">Build counter (least to most)</option>
              <option value="build_counter DESC">Build counter (most to least)</option>
              <option value="clicks">Number of clicks (least to most)</option>
              <option value="clicks DESC">Number of clicks (most to least)</option>
              <option value="(clicks/build_counter)">Productivity - build counter (lowest to highest)</option>
              <option value="(clicks/build_counter) DESC">Productivity - build counter (highest to lowest)</option>
              <option value="(clicks/used_counter)">Productivity - used counter (lowest to highest)</option>
              <option value="(clicks/used_counter) DESC">Productivity - used counter (highest to lowest)</option>
              <option value="RAND()">Randomly</option>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#perm_sortnew_selects')" class="click-image" alt="Add Sort">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#perm_sortnew_selects')" class="click-image" alt="Remove Sort">
            </div>
            </div>
          </div>

          <div class="fieldgroup">
            <label for="reorder">Sort used galleries by:</label>
            <div id="perm_sortused_selects" style="float: left;">
            <div style="margin-bottom: 3px;">
            <select name="p[reorder][]" id="reorder">
              <option value="date_approved">Date approved (Least recently to most recently)</option>
              <option value="date_approved DESC">Date approved (Most recently to least recently)</option>
              <option value="date_displayed">Date gallery was selected for display (least recent to most recent)</option>
              <option value="date_displayed DESC">Date gallery was selected for display (most recent to least recent)</option>
              <option value="weight">Weight (Smallest to largest)</option>
              <option value="weight DESC">Weight (Largest to smallest)</option>
              <option value="times_selected">Times gallery has been selected for display (least to most)</option>
              <option value="times_selected DESC">Times gallery has been selected for display (most to least)</option>
              <option value="used_counter">Used counter (least to most)</option>
              <option value="used_counter DESC">Used counter (most to least)</option>
              <option value="build_counter">Build counter (least to most)</option>
              <option value="build_counter DESC">Build counter (most to least)</option>
              <option value="clicks">Number of clicks (least to most)</option>
              <option value="clicks DESC">Number of clicks (most to least)</option>
              <option value="(clicks/build_counter)">Productivity - build counter (lowest to highest)</option>
              <option value="(clicks/build_counter) DESC">Productivity - build counter (highest to lowest)</option>
              <option value="(clicks/used_counter)">Productivity - used counter (lowest to highest)</option>
              <option value="(clicks/used_counter) DESC">Productivity - used counter (highest to lowest)</option>
              <option value="RAND()">Randomly</option>
            </select>
            <img src="images/add-small.png" onclick="addCategorySelect(this, '#perm_sortused_selects')" class="click-image" alt="Add Sort">
            <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#perm_sortused_selects')" class="click-image" alt="Remove Sort">
            </div>
            </div>
          </div>
        </fieldset>

    <div class="centered margin-top">
      <button type="submit">Generate Template</button>
    </div>

    <input type="hidden" name="s[type]" value="submitted">
    <input type="hidden" name="p[type]" value="permanent">
    <input type="hidden" name="r" value="txPageTemplateWizard">
    </form>
</div>



</body>
</html>
