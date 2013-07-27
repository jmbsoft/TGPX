<?php
if( !defined('TGPX') ) die("Access denied");

$jscripts = array('includes/calendar.js');
$csses = array('includes/calendar.css');
include_once('includes/header.php');
?>

<style>
#crop-icon-big {
  position: relative;
  top: 65px;
}

#upload-icon-big {
  position: relative;
  top: 20px;
}

#no-preview {
  width: 120px;
  height: 150px;
  background-color: #fafafa;  
  border: 1px solid #777;
  position: relative;
  left: 50px;
}
</style>

<script language="JavaScript">
var gallery_win = null;
var gallery_id = null;
var input_focus = false;

$(function()
  {
      loadNext();
      
      $('#next-icon').bind('click', function() { $('#limit').val(parseInt($('#limit').val())+1); loadNext(); });      
      $('#blacklist-icon').bind('click', function()
                                         {
                                             var offset = $.iUtil.getPositionLite(this);
                                             var dimensions = $('#blacklist-options').dimensions();
                                             if( $.browser.msie6 ) $('#hideSelect').show();  
                                             $('#blacklist-options').css({left: offset.x - dimensions.w + 5}).slideDown(300);
                                         });
<?php if( !$C['review_noreject'] ): ?>
      $('#reject-icon').bind('click', function()
                                      {
                                          var offset = $.iUtil.getPositionLite(this);
                                          var dimensions = $('#reject-options').dimensions();
                                          if( $.browser.msie6 ) $('#hideSelect').show();  
                                          $('#reject-options').css({left: offset.x - dimensions.w + 5}).slideDown(300);
                                      });
<?php endif; ?>

      $('#reject-close').bind('click', function()
                                       {
                                           $('#reject-options:visible').slideUp(300);
                                           if( $.browser.msie6 ) $('#hideSelect').hide();                                      
                                       });
                                       
      $('<?php echo ($C['review_noreject'] ? '#reject-icon' : '#reject-actual'); ?>').bind('click', function()
                                        {
                                            if( <?php echo intval($C['review_noconfirm']); ?> || confirm('Are you sure you want to reject this gallery?') )
                                            {
                                                $('#r').val('txGalleryReject');
                                                $('#form').ajaxSubmit({dataType: 'json',
                                                                       error: function(request, status, error) { $('#r').val('txReviewGalleryNext'); },
                                                                       success: function(json)
                                                                                {
                                                                                    $('#r').val('txReviewGalleryNext');
                                                                                    loadNext();
                                                                                    $('#reject-close').trigger('click');
                                                                                }});
                                            }
                                        });
                                         
      $('#options-icon').bind('click', function()
                                       {
                                           var offset = $.iUtil.getPositionLite(this);
                                           var dimensions = $('#gal-review-options').dimensions();
                                           if( $.browser.msie6 ) $('#hideSelect').show();  
                                           $('#gal-review-options:hidden').css({left: offset.x - dimensions.w + 5}).slideDown(300);
                                       });
                                       
      $('#options-close').bind('click', function()
                                       {
                                           $('#gal-review-options:visible').slideUp(300);
                                           if( $.browser.msie6 ) $('#hideSelect').hide();                                      
                                       });
                                       
      $('#delete-icon').bind('click', function() 
                                      {
                                          if( <?php echo intval($C['review_noconfirm']); ?> || confirm('Are you sure you want to delete this gallery?') )
                                          {
                                              $.ajax({type: 'POST',
                                                      url: 'ajax.php',
                                                      dataType: 'json',
                                                      data: 'r=txGalleryReject&which=specific&gallery_id='+gallery_id,
                                                      error: function(request, status, error) { },
                                                      success: function(json)
                                                               {
                                                                   loadNext();
                                                               }
                                                      });
                                            }
                                      });
                                      
      $('#approve-icon').bind('click', function() 
                                       {
                                           if( <?php echo intval($C['review_noconfirm']); ?> || confirm('Are you sure you want to approve this gallery?') )
                                           {
                                               $('#r').val('txGalleryApprove');
                                               
                                               if( !$$('allow_scan').checked )
                                               {
                                                   $('#allow_scan').attr('type', 'hidden').val('0');
                                               }
                                               
                                               if( !$$('allow_preview').checked )
                                               {
                                                   $('#allow_preview').attr('type', 'hidden').val('0');
                                               }
                                                                                              
                                               $('#form').ajaxSubmit({dataType: 'json',
                                                                      error: function(request, status, error) { $('#r').val('txReviewGalleryNext'); },
                                                                      success: function(json)
                                                                               {
                                                                                   $('#r').val('txReviewGalleryNext');
                                                                                   loadNext();
                                                                               }});
                                           }
                                      });
      
      $('#sort').bind('change', function()
                                {
                                    if( $(this).val() == 'random' )
                                    {
                                        $('#seed').val(Math.round(Math.random() * 9999999));
                                    }
                                });
                                
      if( $.browser.msie6 )
      {
          $('#gal-review-options').css({position: 'absolute'});
          $('#reject-options').css({position: 'absolute'});
      }
          
      var scroll = $.iUtil.getScroll();
      $('#hideSelect').css({height: scroll.h+'px', width: scroll.w+'px'});
      
      $(document).bind('keydown', function(e)
                                  {
                                      var kc = e.keyCode;
                                      
                                      // ctrl-Q will remove focus from input fields
                                      if( kc == 81 )
                                      {
                                          if( e.ctrlKey )
                                          {
                                              $(':input').blur();
                                              $('body').focus();
                                              return false;
                                          }
                                      }
                                      
                                      else if( kc == 65 || kc == 68 || kc == 82 || kc == 39 )
                                      {
                                          if( !input_focus )
                                          {
                                              switch(e.keyCode)
                                              {
                                                  case 65: // a
                                                      $('#approve-icon').trigger('click');
                                                      break;
                                                      
                                                  case 68: // d
                                                      $('#delete-icon').trigger('click');
                                                      break;
                                                      
                                                  case 82: // r
                                                      $('<?php echo ($C['review_noreject'] ? '#reject-icon' : '#reject-actual'); ?>').trigger('click');
                                                      break;
                                                  
                                                  case 39: // right arrow
                                                      $('#next-icon').trigger('click');
                                                      break;
                                              }
                                          }
                                      }
                                  });
  });

function restartFromBegin()
{
    $('#limit').val(0);
    loadNext();
    return false;
}

function loadNext()
{
    if( gallery_win != null && !gallery_win.closed )
    {
        gallery_win.location = 'about:blank';
    }
    
    $('#gallery_form').html('<img src="images/activity.gif"> Loading next gallery...');
    $('#gal-review-options:visible').slideUp(400);
    if( $.browser.msie6 ) $('#hideSelect').hide();
    
    $('#form').ajaxSubmit({dataType: 'json',
                           error: function(request, status, error)
                                  {
                                      $('#gallery_form').html('The XMLHttpRequest failed; check your internet connection and make sure your server is online');
                                  },
                           success: function(json)
                                    {
                                        if( json.status == JSON_SUCCESS )
                                        {
                                            if( json.done )
                                            {
                                                $('#icons:visible').hide();
                                                $('#gallery_form').html(json.html);
                                            }
                                            else
                                            {
                                                openGallery(json.gallery_url);                             
                                                gallery_id = json.gallery_id;
                                        
                                                $('#icons:hidden').show();
                                                $('#gallery_form').html(json.html); 
                                                
                                                $(':input').bind('focus', function() { input_focus = true; });
                                                $(':input').bind('blur', function() { input_focus = false; });                            
                                                $('#crop-icon').bind('click', function() { displayCrop(gallery_id); });
                                                $('#upload-icon').bind('click', function() { displayUpload(gallery_id); });
                                                $('#crop-icon-big').bind('click', function() { displayCrop(gallery_id); });
                                                $('#upload-icon-big').bind('click', function() { displayUpload(gallery_id); });
                                                $('#delete-preview-icon').bind('click', function() { deletePreview($('#preview_id option[@selected]').val()); });
                                                $('#submit-info span.tt').Tooltip();
                                                $('#description').bind('keyup', function() { $('#charcount').html($(this).val().length); });
                                                $('#description').trigger('keyup');
                                             
                                                $('#blacklist-close').bind('click', function()
                                                                                    {
                                                                                        $('#blacklist-options:visible').slideUp(300);
                                                                                        if( $.browser.msie6 ) $('#hideSelect').hide(); 
                                                                                    });
                                                                                
                                                $('#blacklist-actual').bind('click', function()
                                                                                     {
                                                                                         $('#r').val('txGalleryBlacklist');
                                                                                         $('#form').ajaxSubmit({dataType: 'json',
                                                                                                                error: function(request, status, error) { $('#r').val('txReviewGalleryNext'); },
                                                                                                                success: function(json)
                                                                                                                         {
                                                                                                                             $('#r').val('txReviewGalleryNext');
                                                                                                                             loadNext();
                                                                                                                         }});
                                                                                     });
                                                
                                                $('#icons-icon').bind('click', function()
                                                                               {
                                                                                   var offset = $.iUtil.getPositionLite(this);
                                                                                   var dimensions = $('#icons-options').dimensions();
                                                                                   if( $.browser.msie6 ) $('#hideSelect').show();  
                                                                                   $('#icons-options:hidden').css({top: offset.y, left: offset.x - dimensions.w + 5}).slideDown(300);
                                                                               });
                                                                               
                                                $('#icons-close').bind('click', function()
                                                                                {
                                                                                    $('#icons-options:visible').slideUp(300);
                                                                                    if( $.browser.msie6 ) $('#hideSelect').hide(); 
                                                                                });
                                                
                                                popUpCal.init();

                                                if( $.browser.msie6 )
                                                    $('#blacklist-options').css({position: 'absolute'});
                                            }
                                        }
                                        else
                                        {
                                            $('#gallery_form').html(json.message);
                                        }
                                    }
                           });
    
    return false;
}

function openGallery(url)
{
    if( gallery_win == null || gallery_win.closed )
    {
        var win_top = $.browser.msie ? document.body.offsetHeight + 80 : window.outerHeight;
        var win_height = $.browser.msie ? screen.height - win_top - 90 : screen.height - win_top;
        
        var win_width = 1000;
        if( screen.width >= 1024 )
            win_width = Math.min(1250, screen.width - 25);
        
        gallery_win = $.browser.msie ?
                      window.open(url, '_blank', 'top='+win_top+',left=0,height='+win_height+',width='+win_width+',scrollbars=yes,resizable=yes,status=no,toolbar=yes') :
                      window.open(url, '_blank', 'top='+win_top+',left=0,outerHeight='+win_height+',width='+win_width+',scrollbars=yes,resizable=yes,status=no,toolbar=yes');
    }
    else
    {
        gallery_win.location = url;
    }
}

function addCategorySelect(img)
{
    $(img.parentNode).clone().hide().appendTo('#category_selects').slideDown(200);
}

function removeCategorySelect(img)
{
    if( $('#category_selects select').length == 1 )
    {
        alert('There must be at least one category defined');
        return false;
    }
    
    if( <?php echo intval($C['review_noconfirm']); ?> || confirm('Are you sure you want to remove this category?') )
        $(img.parentNode).slideUp(200, function() { $(this).remove(); });
}

function addPreview(gid, pid, dims, url)
{    
    $('#preview_id option')
    .contains(dims)
    .remove();
    
    $('#preview_id')
    .append('<option value="'+pid+'" class="{preview_url: \''+url+'\'}">'+dims+'</option>');
    
    loadPreview(gid);
    
    $('#no-preview').hide();
    $('#preview').show();
}

function loadPreview()
{
    var sel = $('#preview_id option[@selected]');
    var opt = sel.data();
    var size = sel.text().split('x');
    var dims = '';
    
    if( size[0] > 180 || size[1] > 180 )
    {
        if( size[0] > size[1] )
        {
            dims = ' width="180"';
        }
        else
        {
            dims = ' height="180"';
        }
    }
    
    $('#preview_image').html('<img src="'+opt.preview_url+'?'+Math.random()+'"'+dims+'>');    
}

function deletePreview(id)
{
    if( <?php echo intval($C['review_noconfirm']); ?> || confirm('Are you sure you want to delete this preview thumb?') )
    {
      $.ajax({type: 'POST',
              url: 'ajax.php',
              dataType: 'json',
              data: 'r=txPreviewDelete&preview_id='+id,
              error: function(request, status, error) { },
              success: function(json)
                       {
                           if( json.status == JSON_SUCCESS )
                           {                                 
                               $('#preview_id option[@selected]').remove();
                               
                               if( $('#preview_id option').length < 1 )
                               {
                                   $('#preview').hide();
                                   $('#no-preview').show();
                               }
                               else
                               {
                                   loadPreview();
                               }
                           }
                       }
              });
    }
}
</script>

<form id="form" action="ajax.php" method="POST">


<!-- Options to select which galleries to review -->
<div id="gal-review-options">
<img src="images/window-close.png" border="0" class="click" style="position: absolute; top: 5px; left: 500px;" id="options-close">
<table width="100%" cellpadding="2" cellspacing="2" border="0">
<tr>
<td class="bold" align="right" width="70">
Type
</td>
<td>
<select name="s_type">
  <option value="">ALL</option>
  <option value="submitted">Submitted</option>
  <option value="permanent">Permanent</option>
</select>
</td>
</tr>

<tr>
<td class="bold" align="right">
Format
</td>
<td>
<select name="s_format">
  <option value="">ALL</option>
  <option value="pictures">Pictures</option>
  <option value="movies">Movies</option>
</select>
</td>
</tr>

<tr>
<td class="bold" align="right">
Category
</td>
<td>
<select name="s_category">
<?php 
$categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');
array_unshift($categories, array('tag' => '', 'name' => 'MIXED'));
echo OptionTagsAdv($categories, '', 'tag', 'name', 50);
?>
</select>
</td>
</tr>

<tr>
<td class="bold" align="right">
Sponsor
</td>
<td>
<select name="s_sponsor_id">
<?php 
$sponsors =& $DB->FetchAll('SELECT * FROM `tx_sponsors` ORDER BY `name`');
array_unshift($sponsors, array('sponsor_id' => '', 'name' => ''));
echo OptionTagsAdv($sponsors, '', 'sponsor_id', 'name', 50);
?>
</select>
</td>
</tr>

<tr>
<td class="bold" align="right">
Sort By
</td>
<td>
<select name="sort" id="sort">
<option value="tx_galleries.gallery_id">Gallery ID</option>
<option value="gallery_url">Gallery URL</option>
<option value="date_added">Date Added</option>
<option value="random">Random</option>
</select>
<select name="direction">
  <option value="ASC">Ascending</option>
  <option value="DESC">Descending</option>
</select>
</td>
<td>
<button type="button" onclick="$('#limit').val(0); loadNext()">Save</button>
</td>
</tr>
</table>
</div>

<div id="reject-options">
<b>E-mail:</b>
<select name="multi_email">
<?php
$rejections =& $DB->FetchAll('SELECT * FROM `tx_rejections` ORDER BY `identifier`');
array_unshift($rejections, array('email_id' => '', 'identifier' => 'NONE'));

echo OptionTagsAdv($rejections, '', 'email_id', 'identifier', 40);
?>
</select>

<img src="images/x-big.png" border="0" style="position: absolute; top: 6px; left: 290px;" id="reject-actual" class="click" alt="Reject" title="Reject">
<img src="images/window-close.png" border="0" style="position: absolute; top: 5px; left: 325px;" id="reject-close" class="click" alt="Close" title="Close">
</div>

<div style="padding: 5px 10px 5px 10px; z-index: 0">

<!-- Function Icons -->
<div id="icons" style="position: absolute; top: 10px; right: 10px;">
<img src="images/check-big.png" id="approve-icon" border="0" style="padding-left: 10px;" class="click" alt="Approve" title="Approve">
<img src="images/x-big.png" id="reject-icon" border="0" style="padding-left: 10px;" class="click" alt="Reject" title="Reject">
<img src="images/trash-big.png" id="delete-icon" border="0" style="padding-left: 10px;" class="click" alt="Delete" title="Delete">
<img src="images/blacklist-big.png" id="blacklist-icon" border="0" style="padding-left: 10px;" class="click" alt="Blacklist" title="Blacklist">
<img src="images/next.png" id="next-icon" border="0" style="padding-left: 30px;" class="click" alt="Skip" title="Skip">
<img src="images/re-test.png" id="options-icon" style="padding-left: 10px;" class="click" alt="Options" title="Options">
</div>

<div id="gallery_form">
</div>

</div>

<input type="hidden" name="which" id="which" value="specific">
<input type="hidden" name="seed" id="seed" value="0">
<input type="hidden" name="r" id="r" value="txReviewGalleryNext">
<input type="hidden" name="limit" id="limit" value="0">
<input type="hidden" name="framed" id="framed" value="1">
</form>

<iframe id="hideSelect" style="display: none;"></iframe>

</body>
</html>