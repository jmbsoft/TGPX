<?php
if( !defined('TGPX') ) die("Access denied");

$sizes = unserialize(GetValue('preview_sizes'));
$gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));
ArrayHSC($gallery);

$format = array();
if( $image )
{
    $categories =& CategoriesFromTags($gallery['categories']);
    $format = GetCategoryFormat($gallery['format'], $categories[0]);
    $imagesize = @getimagesize($image);
}

$title = "Cropping For Gallery {$gallery['gallery_id']}";
include_once('includes/header.php');
?>

<script language="JavaScript">
var select_focus = false;
var thumb_queue = new Array();
var thumbs_found = 0;
var thumbs_downloaded = 0;
var thumb_height = null;
var cropper_created = false;
var max_width = 650;
var max_height = 525;
var resize_ratio = 1;

var BIAS_CENTER =  0;
var BIAS_TOP  = 1;
var BIAS_BOTTOM = 2;
var BIAS_LEFT = 3;
var BIAS_RIGHT = 4;


$(function()
  {
      <?php if( $image == null ): ?>
      scanGallery();
      <?php else: ?>
      insertImage({src: '<?php echo "{$C['install_url']}/cache/".basename($image); ?>', imagefile: '<?php echo basename($image); ?>', status: JSON_SUCCESS, width: <?php echo $imagesize[0]; ?>, height: <?php echo $imagesize[1]; ?>});
      $('#sizeSelect option[@value=<?php echo $format['preview_size']; ?>]').attr({selected: 'selected'});
      $('#dimensions').val('<?php echo $format['preview_size']; ?>');
      $('#thumb_selector').hide();
      <?php endif; ?>
      
      $('#sizeSelect').bind('change', thumbSizeChange);
      
      $('#mask-clear').bind('mousedown', startCropSelect);
      $('#mask').bind('mousedown', function(e) { if( e.ctrlKey == false ) { clearCropper(); startCropSelect(e); } });
      //$('#mask').bind('dblclick', function(e) { if( e.ctrlKey == true ) maximizeCropArea(); });
      
      $(document).bind('keydown', function(e)
                                  {
                                      if( cropper_created && !select_focus )
                                      {
                                          var cropper = $('#cropper');
                                          var amount = 1;
                                          
                                          if( e.shiftKey )
                                          {
                                              amount = 10;
                                          }
                                          
                                          if( e.ctrlKey )
                                          {
                                              
                                              var size = cropper.dimensions();
                                              
                                              switch(e.keyCode)
                                              {
                                                  case 37: // Left
                                                    size.w -= amount;
                                                    break;
                                                    
                                                  case 39: // Right
                                                    size.w += amount;
                                                    break;
                                                    
                                                  default:
                                                    return;
                                              }
                                              
                                              cropper.css({width: size.w + 'px'});
                                              $('#r-se').trigger('mousedown');
                                              $(document).trigger('mousemove').trigger('mouseup');
                                          }
                                          else
                                          {
                                              switch(e.keyCode)
                                              {
                                                  case 37: // Left
                                                    var left = parseInt(cropper.css('left'));
                                                    cropper.css({left: (left-amount)+'px'});
                                                    break;
                                                    
                                                  case 38: // Up
                                                    var top = parseInt(cropper.css('top'));
                                                    cropper.css({top: (top-amount)+'px'});
                                                    break;
                                                    
                                                  case 39: // Right
                                                    var left = parseInt(cropper.css('left'));
                                                    cropper.css({left: (left+amount)+'px'});
                                                    break;
                                                    
                                                  case 40: // Down
                                                    var top = parseInt(cropper.css('top'));
                                                    cropper.css({top: (top+amount)+'px'});
                                                    break;
                                                    
                                                  case 32: // Spacebar
                                                    maximizeCropArea();
                                                    break;
                                                    
                                                  default:
                                                    return;
                                              }
                                              
                                              $('#cropper').trigger('mousedown');
                                              $(document).trigger('mousemove').trigger('mouseup');
                                          }
                                          
                                          return false;
                                      }
                                  });
  });

// Scan the gallery to find thumbnails
function scanGallery()
{   
    infoBarAjax({data: 'r=txGalleryScanForCrop&'+$('#form').formSerialize(),
                 timeError: 900000,
                 message: 'Scanning gallery...',
                 success: function(json)
                          {
                              if( json.status == JSON_SUCCESS )
                              {
                                  $('#sizeSelect option[@value='+json.dimensions+']').attr({selected: 'selected'});
                                  $('#dimensions').val(json.dimensions);
                                  $('#gallery_url').val(json.end_url);
                                  thumb_queue = json.thumbs;
                                  loadThumbs();
                              }
                              else
                              {
                                  infoBarUpdate(json.message, json.status, 10000);
                              }
                          }
                });
}

// Start the process of loading the thumbnails
function loadThumbs(thumbs)
{    
    thumbs_found = thumb_queue.length;
    
    infoBarShow('Downloading '+thumbs_found+' thumbnails...');
    
    getNextThumb();
    getNextThumb();
}

// Get the next thumb in the queue and display it
function getNextThumb()
{
    if( thumb_queue.length < 1 )
        return;
        
    var thumb = thumb_queue.shift();

    $.ajax({type: 'POST',
            url: 'ajax.php',
            dataType: 'json',
            data: 'r=txDownloadThumb&thumb='+escape(thumb.preview)+'&'+$('#form').formSerialize(),
            error: function(request, status, error)
                   {
                       updateThumbCount();                         
                       getNextThumb();
                   },
            success: function(json)
                     {
                         if( json.status == JSON_SUCCESS )
                         {
                             if( !thumb_height )
                             {
                                 if( json.size[1] > 80 && json.size[1] < 150 )
                                 {
                                     thumb.height = thumb_height = json.size[1];
                                 }
                                 else
                                 {
                                     thumb.height = thumb_height = 150;
                                 }
                                
                                 $('#thumb_selector').css({height: thumb_height+'px'});
                             }
                             else
                             {
                                json.size[1] = thumb_height;
                             }
                             
                             $('#thumb_selector').append('<img src="'+json.src+'" border="0" class="preview click {full: \''+thumb.full+'\'}" height="'+thumb_height+'" onclick="loadFull(event, this)">');
                         }
                         else
                         {
                             $('#error').append(json.message + '<br />').show();
                         }
                         
                         updateThumbCount();
                         getNextThumb();
                     }
            });
}

// Update the number of thumbs that have been downloaded successfully
function updateThumbCount()
{
    thumbs_downloaded++;
    infoBarShow('Downloaded '+thumbs_downloaded+' of '+thumbs_found+' thumbnails...');
    if( thumbs_downloaded == thumbs_found )
        $('#infobar').slideUp(350);
}

// Load the full sized image for cropping
function loadFull(e,thumb)
{
    var params = $(thumb).data();

    if( e.ctrlKey == false )
    {   
        $('#cropping').hide();
        $('#crop-preview-img').hide();
        
        infoBarShow('Downloading image for cropping...');
        
        $.ajax({type: 'POST',
                url: 'ajax.php',
                dataType: 'json',
                data: 'r=txDownloadImage&image='+escape(params.full)+'&'+$('#form').formSerialize(),
                error: function(request, status, error)
                       {
                            infoBarUpdate(error, JSON_FAILURE, 10000);
                       },
                success: insertImage});
    }
    
    // Auto-crop image with bias
    else
    {
        var thumb_size = $.iUtil.getSize(thumb);
        var thumb_position = $.iUtil.getPosition(thumb);
        var click_location = $.iUtil.getPointer(e);
        var bias_land = BIAS_RIGHT;
        var bias_port = BIAS_BOTTOM;
        
        click_location.x = click_location.x - thumb_position.x;
        click_location.y = click_location.y - thumb_position.y;
        
        if( click_location.x < thumb_size.w / 3 )
            bias_land = BIAS_LEFT;
        else if( click_location.x < thumb_size.w / 3 * 2 )
            bias_land = BIAS_CENTER;
        
        if( click_location.y < thumb_size.h / 3 )
            bias_port = BIAS_TOP;
        else if( click_location.y < thumb_size.h / 3 * 2 )
            bias_port = BIAS_CENTER;
        
        $('#imagefile').val(params.full);
        $('#bias_land').val(bias_land);
        $('#bias_port').val(bias_port);
        $('#form').clone(true).append('<input type="hidden" name="r" id="r" value="txCropWithBias">').appendTo('body').submit().remove();
    }
}

// Insert the downloaded full size image onto the page
function insertImage(json)
{
    if( json.status == JSON_SUCCESS )
    {       
        $('#crop-image').attr({src: json.src, width: json.width+'px', height: json.height+'px'});
        $('#mask-image').attr({src: json.src, width: json.width+'px', height: json.height+'px'});
        
        $('#crop-preview-img').attr({src: json.src});
        
        $('#infobar').slideUp(350);
        $('#imagefile').val(json.imagefile);
    }
    else
    {
        infoBarUpdate(json.message, json.status, 10000);
    }
}

// Maximize the cropping selection area to the maximum size allowed for the image
function maximizeCropArea()
{
    var img_size = {w: parseInt($('#crop-image').css('width')), h: parseInt($('#crop-image').css('height'))};
    var thumb_size = $('#dimensions').val().split('x');
    var thumb_ratio = thumb_size[1]/thumb_size[0];
    var img_ratio = img_size.h/img_size.w;
    
    if( thumb_ratio > img_ratio )
    {
        var new_height = img_size.h
        var new_width = img_size.h / thumb_ratio;
        var new_top = 0;
        var new_left = (img_size.w - new_width) / 2;
    }
    else
    {   
        var new_height = img_size.w * thumb_ratio;
        var new_width = img_size.w;
        var new_top = (img_size.h - new_height) / 2;
        var new_left = 0;
    }
    
    $('#cropper').css({top: new_top + 'px', left: new_left + 'px', height: new_height + 'px', width: new_width + 'px'});

    $('#r-se').trigger('mousedown');
    $(document).trigger('mousemove').trigger('mouseup');
    return false;
}

// Handle changes in the thumb size drop down selection field
function thumbSizeChange()
{
    if( this.value )
    {
        clearCropper();
        
        var cropper = $('#cropper');
        var newSize = this.value.split('x');            
        var img_size = {w: parseInt($('#crop-image').attr('width')), h: parseInt($('#crop-image').attr('height'))};
        var cropper_size = fitInside((newSize[0]/resize_ratio), (newSize[1]/resize_ratio), img_size.w, img_size.h);
        
        if( cropper[0].resizeOptions )
        {
            cropper[0].resizeOptions.ratio = cropper_size.h / cropper_size.w;
        }
        
        $('#crop-preview').css({height: newSize[1] + 'px', width: newSize[0] +'px'});
        //cropper.css({height: cropper_size.h + 'px', width: cropper_size.w +'px'});
        
        $('#dimensions').val(this.value);
        $('#sizeCustomSpan:visible').hide();
    }
    else
    {
        $('#sizeCustomSpan:hidden').show();
    }
    
    $('#sizeSelect').blur();
    $('body').focus();
}

// Set a custom thumbnail size
function setCustomSize()
{
    var size = $.trim($('#sizeCustom').val());
    
    if( size.search(/^\d+x\d+$/) == -1 )
    {
        alert('Size must be entered in WxH format [Example: 150x200]');
        return;
    }
    
    $('#sizeSelect').append('<option value="'+size+'">'+size+'</option>');
    $('#sizeSelect option[@value='+size+']').attr({selected: 'selected'});
    $('#sizeSelect').trigger('change');    
}

// Mouse down, start crop selection
function startCropSelect(e)
{
    clearCropper();
        
    var img_size = {w: parseInt($('#crop-image').attr('width')), h: parseInt($('#crop-image').attr('height'))};
    var orig_height = img_size.h;
    var preview_size = $('#dimensions').val().split('x');
    
    img_size = fitInside(img_size.w, img_size.h, max_width, max_height);
    resize_ratio = orig_height / img_size.h;
    var cropper_size = fitInside((preview_size[0]/resize_ratio), (preview_size[1]/resize_ratio), img_size.w, img_size.h);
    
    var pos = $.iUtil.getPosition($$('mask'));
    var pointer = $.iUtil.getPointer(e);
    
    $('#mask').show();
    $('#mask-clear').hide();
    $('#crop-preview-img').show();
    
    $('#cropper').Resizable(
        {
            minTop: 0,
            minLeft: 0,
            maxRight: img_size.w - 1,
            maxBottom: img_size.h - 1,
            minWidth: 5,
            minHeight: 5,
            dragHandle: true,
            onDrag: function(x, y)
                    {
                        $('#mask-image').css({left: '-' + (x+1) + 'px', top: '-' + (y+1) + 'px'});
                        
                        var cropper_size = $('#cropper').dimensions();
                        var thumb_size = $('#dimensions').val().split('x');
                        var size_ratio = (thumb_size[1]/(cropper_size.h*resize_ratio));
                        
                        if( x < 0 ) x = 0;
                        if( y < 0 ) y = 0;                        
                        
                        $('#crop-preview-img').css({top: '-' + Math.floor(y * resize_ratio * size_ratio) + 'px',
                                                    left: '-' + Math.floor(x * resize_ratio * size_ratio) + 'px'});
                    },
            onResize : function(size, position) 
                       {
                           $('#mask-image').css({left: '-' + (position.left+1) + 'px', top: '-' + (position.top+1) + 'px'});
                           
                           var thumb_size = $('#dimensions').val().split('x');
                           var size_ratio = (thumb_size[1]/(size.height*resize_ratio));
                           
                           if( position.top < 0 ) position.top = 0;
                           if( position.left < 0 ) position.left = 0;
                           
                           $('#crop-preview-img').css({top: '-' + Math.floor(position.top * resize_ratio * size_ratio) + 'px',
                                                       left: '-' + Math.floor(position.left * resize_ratio * size_ratio) + 'px',
                                                       height: Math.ceil(orig_height * size_ratio) + 'px'});
                       },
            handlers: { 
                          se: '#r-se',
                          ne: '#r-ne',
                          nw: '#r-nw',
                          sw: '#r-sw'
                      },
            ratio: preview_size[1]/preview_size[0]
        }
    )
    .bind('dblclick', function() 
                      { 
                          var cropper = $$('cropper');
                          var size = $('#cropper').dimensions();
                          
                          if( size.h > 15 || size.w > 15 )
                          {                          
                              $('#x').val(cropper.offsetLeft < 1 ? 0 : cropper.offsetLeft * resize_ratio);
                              $('#y').val(cropper.offsetTop < 1 ? 0 : cropper.offsetTop * resize_ratio);
                              $('#width').val(size.w * resize_ratio);
                              $('#height').val(size.h * resize_ratio);
                              $('#form').clone(true).append('<input type="hidden" name="r" id="r" value="txCrop">').appendTo('body').submit().remove();
                          }
                      }
    )
    .css({top: pointer.y - pos.y +'px', 
          left: pointer.x - pos.x + 'px', 
          width: 5+'px', 
          height: 5+'px'})
    .show();
    
    $('#mask-image').css({left: '-' + (pointer.x - pos.x + 1) + 'px', top: '-' + (pointer.y - pos.y + 1) + 'px'});
    $('#r-se').trigger('mousedown');
    $.iResize.pointer = pointer;
    
    var cropper_size = $('#cropper').dimensions();
    var thumb_size = $('#dimensions').val().split('x');
    var size_ratio = (thumb_size[1]/(cropper_size.h*resize_ratio));
   
    $('#crop-preview-img').css({top: '-' + pointer.y * resize_ratio * size_ratio + 'px',
                                left: '-' + pointer.x * resize_ratio * size_ratio + 'px',
                                height: orig_height * size_ratio + 'px'});
    
    cropper_created = true;
}

// Setup the cropper each time a full sized image is loaded
function setupCropper()
{    
    $('#cropping').show();

    var img_size = {w: parseInt($('#crop-image').attr('width')), h: parseInt($('#crop-image').attr('height'))};
    var orig_height = img_size.h;
    var preview_size = $('#dimensions').val().split('x');
    
    img_size = fitInside(img_size.w, img_size.h, max_width, max_height);
    resize_ratio = orig_height / img_size.h;
    var cropper_size = fitInside((preview_size[0]/resize_ratio), (preview_size[1]/resize_ratio), img_size.w, img_size.h);
    
    $('#crop-preview').css({height: preview_size[1] + 'px', width: preview_size[0] +'px'});
    $('#crop-image').css({height: img_size.h, width: img_size.w});
    $('#mask-image').css({height: img_size.h, width: img_size.w, left: '-1px', top: '-1px'});
    $('#mask').css({height: img_size.h, width: img_size.w});
    
    $('#cropper').hide();
    $('#mask').hide();
    $('#mask-clear').show();
}

function clearCropper()
{
    if( cropper_created )
    {
        $('#cropper').ResizableDestroy().unbind();
        cropper_created = false;
    }
    
    $('#crop-preview-img').hide();
    $('#mask-clear').show();
    $('#mask').hide();
    $('#cropper').hide();
}

function fitInside(src_w, src_h, container_w, container_h)
{   
    if( src_h > container_h )
    {
        src_w = src_w/(src_h/container_h);
        src_h = container_h;
    }
    
    if( src_w > container_w )
    {
        src_h = src_h/(src_w/container_w);
        src_w = container_w;
    }
    
    return {w: src_w, h: src_h};
}
</script>
<style>
.preview {
  padding-left: 3px; 
}

#crop-preview {
  border: 1px solid black;
  overflow: hidden;
  position: relative;
}

#crop-preview-img {
  display: none;
  position: absolute;
}

#cropper {
  background: transparent repeat scroll 0px 0px; 
  position: absolute; 
  left: 0px; 
  top: 0px; 
  width: 120px; 
  height: 150px;
  cursor: move;
  border: 1px dotted #fff;
}

#image-cropper {
  float: left;
  position: relative;
}

#image-cropper img {
  display: block;
}

#crop-image {
  z-index: 0;
}

#mask-clear {
  position: absolute;
  top: 0; 
  left: 0;
  width: 100%; 
  height: 100%;
  background: #000;
  opacity: 0;
  filter: alpha(opacity=0);
}

#mask {
  position: absolute;
  top: 0; 
  left: 0;
  width: 100%; 
  height: 100%;
  background: #000;
  opacity: 0.6;
  filter: alpha(opacity=60);
}

#mask-image {
  position: relative;
  overflow: hidden;
}

#mask-image-div {
  overflow: hidden; 
  position: relative; 
  width: 100%; 
  height: 100%;
}

#cropper div.sizer {
  position: absolute;
  width: 6px; 
  height: 6px;
  background: #fff;
  border: 1px solid #000;
  overflow: hidden;
}

#cropper .l { left: -5px; }
#cropper .r { right: -5px; }
#cropper .t { top: -5px; }
#cropper .b { bottom: -5px; }

#cropper .l.t { cursor: nw-resize; }
#cropper .r.t { cursor: ne-resize; }
#cropper .l.b { cursor: sw-resize; }
#cropper .r.b { cursor: se-resize; }

.thumb {
  cursor: pointer;
}

.crop {
  visibility: hidden;
  position: absolute;
}
</style>

<div id="infobar" class="noticebar"><div id="info"></div></div>

<div id="main-content" style="margin-bottom: 20px;">
  <div id="centered-content" style="width: 80%">

    <div id="error" class="alert" style="display: none;"></div>
  
    <div id="thumb_selector" style="overflow: auto; height: 150px;"></div>

    <br />
    
    <div style="text-align: center;">
    Thumb Size: 
    <select id="sizeSelect" onfocus="select_focus = true" onblur="select_focus = false">
      <option value="">Custom --></option>
      <?php echo OptionTags($sizes, null, TRUE); ?>
    </select>
    
    <span id="sizeCustomSpan" style="display: none;">
    <input type="text" size="8" id="sizeCustom">
    <button type="button" onclick="setCustomSize()">Set</button>
    </span>
    </div>
    
    <div id="cropping" style="display: none; text-align: center; margin-top: 10px;">
    <table cellpadding="0" cellspacing="0" border="0" align="center">
    <tr>
    <td valign="top" style="padding-right: 10px;">
    <div id="crop-preview"><img src="" border="0" id="crop-preview-img"></div>
    </td>
    <td>
    
    <div id="image-cropper">
      <img src="" id="crop-image" onload="setupCropper()">
      <div id="mask-clear"></div>
      <div id="mask"></div>
      <div id="cropper">
        <div id="mask-image-div"><img src="" id="mask-image"></div>
        <div id="r-nw" class="t l sizer"></div>
        <div id="r-ne" class="t r sizer"></div>
        <div id="r-sw" class="b l sizer"></div>
        <div id="r-se" class="b r sizer"></div>
      </div>
    </div>
    
    </td>
    </tr>
    </table>
    
    </div>
    
    <div style="clear: both">
    </div>
    
    <br />
    <br />
    
  </div>
</div>

<form action="index.php" id="form" method="POST">
<input type="hidden" name="gallery_id" id="gallery_id" value="<?php echo $gallery['gallery_id']; ?>">
<input type="hidden" name="gallery_url" id="gallery_url" value="<?php echo $gallery['gallery_url']; ?>">
<input type="hidden" name="imagefile" id="imagefile" value="">
<input type="hidden" name="bias_land" id="bias_land" value="">
<input type="hidden" name="bias_port" id="bias_port" value="">
<input type="hidden" name="x" id="x" value="">
<input type="hidden" name="y" id="y" value="">
<input type="hidden" name="width" id="width" value="">
<input type="hidden" name="height" id="height" value="">
<input type="hidden" name="dimensions" id="dimensions" value="">
</form>

</body>
</html>