
<input type="hidden" name="gallery_id" value="<?php echo $gallery['gallery_id']; ?>">


<div id="preview-holder" style="position: absolute; top: 35px; right: 10px; height: 200px; width: 200px; text-align: center;">

<!-- No preview -->
<div id="no-preview"<?php if( $gallery['has_preview'] ) echo 'style="display: none;"'; ?>>
<img src="images/upload-big.png" border="0" alt="Upload" title="Upload" class="click" id="upload-icon-big"><br />
<img src="images/crop-big.png" border="0" alt="Crop" title="Crop" class="click" id="crop-icon-big">
</div>

<!-- Preview Thumbnail -->
<div id="preview"<?php if( !$gallery['has_preview'] ) echo ' style="display: none;"'; ?>>
<div id="preview_image">
<?php if( $gallery['has_preview'] ): ?>
<img src="<?php echo $gallery['previews'][0]['preview_url']; ?>"<?php echo html_entity_decode($gallery['previews'][0]['attrs']); ?>>
<?php endif; ?>
</div>
<div id="thumbactions" style="padding-top: 3px;">
<img src="images/preview-upload.png" border="0" alt="Upload" title="Upload" class="click" id="upload-icon">
<img src="images/preview-crop.png" border="0" alt="Crop" title="Crop" class="click" id="crop-icon">
<select id="preview_id" onchange="loadPreview()">
<?php 
if( $gallery['has_preview'] ):
foreach( $gallery['previews'] as $preview ): ?>
  <option value="<?php echo $preview['preview_id'] ?>" class="{preview_url: '<?php echo $preview['preview_url'] ?>'}"><?php echo $preview['dimensions'] ? $preview['dimensions'] : '-x-'; ?></option>
<?php 
endforeach;
endif; ?>
</select>
<img src="images/trash.png" border="0" alt="Delete" title="Delete" class="click" id="delete-preview-icon">
</div>
</div>
</div>



<!-- Gallery Data -->
<div id="main">
<table width="100%" cellpadding="0" cellspacing="2" border="0">
<tr>
<td align="right" width="80" class="bold">
URL
</td>
<td width="425">
<input type="text" name="gallery_url" value="<?php echo $gallery['gallery_url']; ?>" size="80"<?php if( $gallery['has_recip'] ) echo ' class="recip"'; ?>>
</td>
<td align="right" width="70" class="bold">
Thumbs
</td>
<td>
<input type="text" name="thumbnails" value="<?php echo $gallery['thumbnails']; ?>" size="3">

<span style="padding-left: 20px;" class="bold">
<?php 
echo number_format($remain - $_REQUEST['limit'], 0, $C['dec_point'], $C['thousands_sep']); ?>
 Left (<?php echo number_format($_REQUEST['limit'], 0, $C['dec_point'], $C['thousands_sep']); ?>)
</span>
</td>
</tr>

<tr>
<td align="right" class="bold">
Description
</td>
<td>
<input type="text" name="description" id="description" value="<?php echo $gallery['description']; ?>" size="60">
<span id="charcount" style="padding-left: 10px;">0</span>
</td>
<td align="right" width="70" class="bold">
Scheduled
</td>
<td>
<input type="text" name="date_scheduled" id="date_scheduled" value="<?php echo $gallery['date_scheduled']; ?>" size="18" class="calendarSelectDate">
</td>
</tr>

<tr>
<td align="right" class="bold">
Keywords
</td>
<td>
<input type="text" name="keywords" value="<?php echo $gallery['keywords']; ?>" size="60">
<?php if( count($icons) > 1 ): ?> 
<span style="padding-left: 50px;">
<img src="images/icons.png" border="0" class="click" alt="Icons" title="Icons" id="icons-icon">
</span>
<?php endif; ?>
</td>
<td align="right" width="70" class="bold">
Delete
</td>
<td>
<input type="text" name="date_deletion" id="date_deletion" value="<?php echo $gallery['date_deletion']; ?>" size="18" class="calendarSelectDate">
</td>
</tr>

<tr>
<td align="right" class="bold">
Tags
</td>
<td>
<input type="text" name="tags" value="<?php echo $gallery['tags']; ?>" size="60">

<span style="padding-left: 15px; font-weight: bold">
Weight
<input type="text" name="weight" value="<?php echo $gallery['weight']; ?>" size="3">
</span>
</td>
<td align="right" width="70" class="bold">
Type
</td>
<td>
<select name="type">
<?php
$types = array('submitted' => 'Submitted', 'permanent' => 'Permanent');
echo OptionTags($types, $gallery['type']);
?>
</select>
</td>
</tr>

<tr>
<td align="right" class="bold">
Sponsor
</td>
<td>
<?php
$sponsors =& $DB->FetchAll('SELECT * FROM `tx_sponsors` ORDER BY `name`');

if( count($sponsors) < 1 ):
?>
<span style="padding-right: 100px;">&nbsp;-</span>
<?php else: ?>
<select name="sponsor_id">
<?php

array_unshift($sponsors, array('sponsor_id' => '', 'name' => ''));
echo OptionTagsAdv($sponsors, $gallery['sponsor_id'], 'sponsor_id', 'name', 40);
?>
</select>
<?php endif; ?>

<span style="padding-left: 30px; font-weight: bold">
<?php echo CheckBox('allow_scan', 'checkbox', '1', $gallery['allow_scan']); ?> <label for="allow_scan" class="plain-label">Scan</label>
&nbsp;
<?php echo CheckBox('allow_preview', 'checkbox', '1', $gallery['allow_preview']); ?> <label for="allow_preview" class="plain-label">Thumb</label>
</span>
</td>
<td align="right" width="70" class="bold">
Format
</td>
<td>
<select name="format">
<?php
$formats = array('pictures' => 'Pictures', 'movies' => 'Movies');
echo OptionTags($formats, $gallery['format']);
?>
</select>
</td>
</tr>

<tr>
<td align="right" width="80" class="bold" valign="top" style="padding-top: 3px">
Categories
</td>
<td width="425">
<div id="category_selects">
<?php
$all_categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');
$categories =& CategoriesFromTags($gallery['categories']);

foreach( $categories as $category ):
?>
  <div style="margin-bottom: 3px;">
  <select name="categories[]">
  <?php
  echo OptionTagsAdv($all_categories, $category['category_id'], 'category_id', 'name', 50);
  ?>
  </select>
  <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
  <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
  </div>
<?php endforeach; ?>
</div>
</td>
<td align="right" width="70" class="bold" valign="top" style="padding-top: 3px">
Nickname
</td>
<td valign="top">
<input type="text" name="nickname" value="<?php echo $gallery['nickname']; ?>" size="20">
</td>
</tr>

<?php

$fields =& GetUserGalleryFields($gallery);
foreach( $fields as $field ):
    ArrayHSC($field);
    AdminFormField($field);
?>

<tr>
<?php if( $field['type'] != FT_CHECKBOX ): ?>

<td align="right" width="80" class="bold">
<?php echo $field['label']; ?>
</td>
<td>
<?php echo FormField($field, $field['value']); ?>
</td>

<?php else: ?>
<td>
&nbsp;
</td>
<td>
<?php echo FormField($field, $field['value']); ?> <label for="<?php echo $field['name']; ?>" class="plain-label"><?php echo $field['label']; ?></label>
</td>
<?php endif; ?>
</tr>        
<?php 
endforeach;
?>
</table>

</div>

<div id="blacklist-options">
<table width="100%" border="0">
<tr>
<td class="bold" align="right" width="90">
Domain IP
</td>
<td>
<input type="text" name="bl_domainip" id="bl_domainip" size="30" value="<?php echo $gallery['gallery_ip']; ?>"> 
<img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#bl_domainip').val('');">
</td>
<td>
<img src="images/blacklist-big.png" border="0" alt="Blacklist" title="Blacklist" class="click" id="blacklist-actual">
<img src="images/window-close.png" border="0" id="blacklist-close" class="click" style="position: relative; top: -5px; right: -16px;">
</td>
</tr>
<tr>
<td class="bold" align="right">
Submitter IP
</td>
<td colspan="2">
<input type="text" name="bl_submitip" id="bl_submitip" size="30" value="<?php echo $gallery['submit_ip']; ?>"> 
<img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#bl_submitip').val('');">
</td>
</tr>
<?php 
$nameservers = GetNameServers($gallery['gallery_url']);
if( count($nameservers) > 0 ):
?>
<tr>
<td class="bold" align="right">
DNS Server
</td>
<td colspan="2">
<input type="text" name="bl_dns" id="bl_dns" size="30" value="<?php echo $nameservers[0]; ?>"> 
<img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#bl_dns').val('');">
</td>
</tr>
<?php endif; ?>
<tr>
<td class="bold" align="right">
Gallery URL
</td>
<td colspan="2">
<input type="text" name="bl_url" id="bl_url" size="40" value="<?php echo $gallery['gallery_url']; ?>"> 
<img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#bl_url').val('');">
<img src="images/preview-crop.png" border="0" title="Domain" alt="Domain" class="function click" onclick="$('#bl_url').val(domainFromUrl($('#bl_url').val()));">
</td>
</tr>
<tr>
<td class="bold" align="right">
E-mail
</td>
<td colspan="2">
<input type="text" name="bl_email" id="bl_email" size="30" value="<?php echo $gallery['email']; ?>"> 
<img src="images/x.png" border="0" alt="Clear" title="Clear" class="function click" onclick="$('#bl_email').val('');">
<img src="images/preview-crop.png" border="0" title="Domain" alt="Domain" class="function click" onclick="$('#bl_email').val(domainFromEmail($('#bl_email').val()));">
</td>
</tr>
<tr>
<td class="bold" align="right">
Reason
</td>
<td colspan="2">
<input type="text" name="bl_reason" id="bl_reason" size="30">
</td>
</tr>
</table>
</div>

<div id="icons-options">
<img src="images/window-close.png" border="0" class="click" style="position: absolute; top: 5px; left: 230px;" id="icons-close">
<div style="height: 100px; width: 210px; overflow: auto;">
<?php foreach( $icons as $icon ): ?>
<div style="padding-bottom: 3px;">
<?php echo Checkbox('icons[]', 'checkbox', $icon['icon_id'], '');  echo " " . $icon['icon_html']; ?>
</div>
<?php endforeach; ?>
</div>
</div>

<!-- top: 150px; left: 530px -->
<div id="submit-info" style="position: absolute; top: 14em; left: 50em; font-size: 8pt; font-weight: normal">
<b>Submit Info</b><br />
<?php echo $gallery['email']; ?><br />
<?php 
$hostname = gethostbyaddr($gallery['submit_ip']);

if( $hostname != $gallery['submit_ip'] ):
?>
<span title="<?php echo htmlspecialchars($hostname); ?>" class="tt"><?php echo $gallery['submit_ip']; ?></span><br />
<?php else: ?>
<?php echo $gallery['submit_ip']; ?><br />
<?php endif; ?>
<?php echo date(DF_SHORT, strtotime($gallery['date_added'])); ?><br />
<?php echo $gallery['partner']; ?>
</div>

<input type="text" size="0" name="" value="" style="visibility: hidden;">
