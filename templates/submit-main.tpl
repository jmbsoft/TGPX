{assign var=$page_title value="Submit a Gallery"}
{include filename="global-header.tpl"}

<script language="JavaScript">
var max_categories = parseInt('{$config.max_categories}');
var submitted_categories = [{$gallery.submitted_categories|htmlspecialchars}];
{literal}
$(function()
  {
     previewChange();
     partnerChange();
     
     $('#category_id').bind('change', categoryChange);
     $('#category_id').trigger('change');
     $('#description').bind('keyup', function() { $('#charcount').html($(this).val().length); });
     $('#description').trigger('keyup');
     
     var add_categories = submitted_categories.length - $('#category_selects select').length;
     
     if( submitted_categories.length > 0 )
     {
         $('#category_selects select option[@value='+submitted_categories[0]+']').attr({selected: 'selected'});
     }
     
     if( add_categories > 0 )
     {
         for( var i = 1; i <= add_categories; i++ )
         {
             var new_select = addCategorySelect($('#category_selects div:first img:first')[0]);             
             $('select option[@value='+submitted_categories[i]+']', new_select).attr({selected: 'selected'});
         }
     }
  });


function categoryChange()
{ 
    var data = $(':selected', this).data();   
    $('#p_size').html(data.p); 
    $('#m_size').html(data.m);
}

function addCategorySelect(img)
{
    if( $('#category_selects select').length == max_categories )
    {
        alert('You can only select up to ' + max_categories + ' categories');
        return;
    }
    
    return $(img.parentNode).clone().hide().appendTo('#category_selects').slideDown(200);
}

function removeCategorySelect(img)
{
    if( $('#category_selects select').length == 1 )
    {
        alert('There must be at least one category selected');
        return false;
    }
    
    $(img.parentNode).slideUp(200, function() 
                                   { 
                                       $(this).remove();
                                       $('#category_selects select:first').bind('change', categoryChange).trigger('change');
                                   });
}
{/literal}

function previewChange()
{
    switch($('#preview').val())
    {
    case 'upload':
        $('#upload_div:hidden').SlideInLeft(600);
        break;
    default:
        $('#upload_div:visible').SlideOutLeft(600);
        break;
    }
}

function partnerChange()
{
    var user = $('#username').val();
    var pass = $('#password').val();
    
    if( user || pass )
    {
        $('#nickname_tr').hide();
        $('#email_tr').hide();
           
{if !$config.gallery_captcha_partner}
        $('#verification:visible').hide();
{/if}
{if $config.allow_preview_partner}
        $('#preview_tr:hidden').show();
{/if}
    }
    else
    {
        $('#nickname_tr').show();
        $('#email_tr').show();
        
{if !$config.gallery_captcha_partner}
        $('#verification:hidden').show();
{/if}
{if $config.allow_preview_partner}
        $('#preview_tr:visible').hide();
{/if}
    }
}
</script>

<div class="header" style="text-align: center;">
Submit a Gallery
</div>


<div class="content-section">

<form method="POST" action="submit.php" enctype="multipart/form-data">

<table align="center" width="100%" cellpadding="3" cellspacing="2">

{* Display any errors encountered during the gallery submission process *}
{if $errors}
<tr>
<td colspan="2" style="padding-bottom: 5px;">
<div class="error">
Please fix the following errors:<br />
<ol style="margin: 2px; padding-left: 23px; margin-top: 5px;">
{foreach var=$error from=$errors}
<li> {$error|htmlspecialchars}<br />
{/foreach}
</ol>
</div>
</td>
</tr>
{/if}

{* Only show this if the submission form is only open to partners *}
{if $config.submit_status == 'partner'}
<tr>
<td colspan="2" style="padding-bottom: 5px;">
<div class="notice">
We are currently only accepting galleries from partners.  You will not be able to submit a gallery unless you have a partner account.
</div>
</td>
</tr>
{/if}

{* Only show this if the submission form is open to all *}
{if $config.submit_status == 'all'}
<tr>
<td>
</td>
<td>
<span class="notice small">
If you have a partner account, please fill in your username and password.
</span>
</td>
</tr>
{/if}

<tr>
<td width="150" align="right">
<b>Username</b>
</td>
<td>
<input type="text" size="20" name="username" id="username" value="{$gallery.username|htmlspecialchars}" onchange="partnerChange()" />
</td>
</tr>

<tr>
<td width="150" align="right">
<b>Password</b>
</td>
<td>
<input type="password" size="20" name="password" id="password" value="{$gallery.password|htmlspecialchars}" onchange="partnerChange()" />
<a href="{$config.install_url}/partner.php?r=reset" class="small">Forgot Your Password?</a>
</td>
</tr>

<tr>
<td colspan="2" style="font-size: 1px; height: 5px;">
&nbsp;
</td>
</tr>

{* Don't show these if submissions only open for partners *}
{if $config.submit_status != 'partner'}
<tr id="nickname_tr">
<td width="150" align="right">
<b>Name/Nickname</b>
</td>
<td>
<input type="text" size="40" name="nickname" value="{$gallery.nickname|htmlspecialchars}" />
</td>
</tr>

<tr id="email_tr">
<td width="150" align="right">
<b>E-mail Address</b>
</td>
<td>
<input type="text" size="50" name="email" value="{$gallery.email|htmlspecialchars}" />
</td>
</tr>
{/if}

<tr>
<td width="150" align="right">
<b>Gallery URL</b>
</td>
<td>
<input type="text" size="100" name="gallery_url" value="{$gallery.gallery_url|htmlspecialchars}" />
</td>
</tr>

<tr>
<td width="150" align="right" valign="top">
<b>Description</b>
</td>
<td>
<input type="text" size="100" name="description" id="description" value="{$gallery.description|htmlspecialchars}" /><br />
<span class="small">Must contain between {$config.min_desc_length} and {$config.max_desc_length} characters; <span id="charcount">0</span> characters currently entered</span>
</td>
</tr>

{* Only display if user is allowed to submit keywords *}
{if $config.allow_keywords}
<tr>
<td width="150" align="right" valign="top">
<b>Keywords</b>
</td>
<td>
<input type="text" size="80" name="keywords" value="{$gallery.keywords|htmlspecialchars}" /><br />
You may submit up to {$config.max_keywords} keywords; please separate them by spaces, not commas</span>
</td>
</tr>
{/if}

<tr>
<td width="150" align="right" valign="top">
{if $config.allow_multiple_cats}
<b>Categories</b>
{else}
<b>Category</b>
{/if}
</td>
<td>

{if $config.allow_multiple_cats}
<div id="category_selects">
<div style="margin-bottom: 3px;">
<select name="category_id[]" id="category_id">
{* Loop through and display each category in the drop-down selection box *}
{foreach var=$category from=$categories}
  <option value="{$category.category_id|htmlspecialchars}" class="{ldelim}p: '{$category.pics_preview_size}', m: '{$category.movies_preview_size}'{rdelim}"{if $category.category_id == $gallery.category_id} selected="selected"{/if}>{$category.name|htmlspecialchars}</option>
{/foreach}
</select>
<img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
<img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
</div>
</div>
{else}
<select name="category_id" id="category_id">
{* Loop through and display each category in the drop-down selection box *}
{foreach var=$category from=$categories}
  <option value="{$category.category_id|htmlspecialchars}" class="{ldelim}p: '{$category.pics_preview_size}', m: '{$category.movies_preview_size}'{rdelim}"{if $category.category_id == $gallery.category_id} selected="selected"{/if}>{$category.name|htmlspecialchars}</option>
{/foreach}
</select>
{/if}

{if $config.allow_multiple_cats}
<span class="small">Select up to {$config.max_categories|tnumber_format} categories for your gallery</span>
{/if}
</td>
</tr>

{* Only show this if users are allowed to submit a thumbnail count *}
{if $config.allow_num_thumbs}
<tr>
<td width="150" align="right">
<b>Thumbnails</b>
</td>
<td>
<input type="text" size="10" name="thumbnails" value="{$gallery.thumbnails|htmlspecialchars}" />
</td>
</tr>
{/if}


{* Only show this if users are allowed to submit a the gallery format *}
{if $config.allow_format}
<tr>
<td width="150" align="right">
<b>Format</b>
</td>
<td>
<select name="format">
  <option value="pictures"{if $gallery.format == 'pictures'} selected="selected"{/if}>Pictures</option>
  <option value="movies"{if $gallery.format == 'movies'} selected="selected"{/if}>Movies</option>
</select>
</td>
</tr>
{/if}

<tr id="preview_tr"{if $config.allow_preview_partner} style="display: none;"{/if}>
<td width="150" align="right" valign="top">
<b>Preview Thumb</b>
</td>
<td>
<div style="float: left;">
<select name="preview" id="preview" onchange="previewChange()">
  {if $config.have_imager}
  <option value="automatic"{if $gallery.preview == 'automatic'} selected="selected"{/if}>Have a preview automatically created</option>
  <option value="crop"{if $gallery.preview == 'crop'} selected="selected"{/if}>Select and crop an image from your gallery</option>
  {/if}
  <option value="upload"{if $gallery.preview == 'upload'} selected="selected"{/if}>Upload an image from your computer ---&gt;</option>
</select>
</div>
<div id="upload_div" style="{if $gallery.preview != 'upload'}display: none; {/if}float: left; padding-left: 5px;">
<input type="file" size="30" name="upload" />
</div>
<div style="clear: both;" class="small">
Picture Gallery: <span id="p_size"></span><br />
Movie Gallery: <span id="m_size"></span>
</div>
</td>
</tr>

{* Show the user defined fields *}
{foreach var=$field from=$user_fields}
  {if $field.on_submit}
    {if $field.type == FT_CHECKBOX}
<tr>
<td width="150" align="right">
&nbsp;
</td>
<td>
{field from=$field value=$field.value}
<b><label for="{$field.name|htmlspecialchars}">{$field.label|htmlspecialchars}</label></b> 
</td>
</tr>    
    {else}
<tr>
<td width="150" align="right">
<b>{$field.label|htmlspecialchars}</b> 
</td>
<td>
{field from=$field value=$field.value}
</td>
</tr>
    {/if}
  {/if}
{/foreach}

{* Display verification code if required *}
{if $config.gallery_captcha || $config.gallery_captcha_partner}
<tr id="verification">
<td width="150" align="right">
<b>Verification</b>
</td>
<td>
<div>
<img src="code.php" border="0" style="vertical-align: middle;">
<input type="text" name="captcha" size="20" style="vertical-align: middle;" />
</div>
<span class="small">Copy the characters from the image into the text box for verification</span>
</td>
</tr>
{/if}
<tr>
<td align="center" colspan="2">
<button type="submit">Submit Gallery</button>
</td>
</tr>

</table>

<input type="hidden" name="r" value="submitgallery">
</form>
</div>

{include filename="global-footer.tpl"}