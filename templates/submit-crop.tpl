<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title>TGP - Submit a Gallery</title>
  <script type="text/javascript" src="{$config.install_url}/includes/jquery.js"></script>
  <script type="text/javascript" src="{$config.install_url}/includes/interface.js"></script>
  <link rel="stylesheet" type="text/css" href="{$config.install_url}/templates/style.css" />
  <link rel="stylesheet" type="text/css" href="{$config.install_url}/templates/crop.css" />
  <script type="text/javascript" src="{$config.install_url}/includes/form.js"></script>
</head>
<body>

<script language="JavaScript">
var JSON_SUCCESS = 'Success';
var JSON_FAILURE = 'Failure';
var INFO_TIMEOUT = null;
var thumb_queue = new Array({$thumb_queue|htmlspecialchars::ENT_NOQUOTES});
var thumbs_found = 0;
var thumbs_downloaded = 0;
var thumb_height = null;
var cropper_created = false;
var max_width = 650;
var max_height = 525;
var resize_ratio = 1;
var preview_size = new Array({$size.width|htmlspecialchars},{$size.height|htmlspecialchars});

{literal}
$(function()
  {
      if( $.browser.msie6 )
      {
          $('#infobar').css({position: 'absolute', width: '100%'});
      }
      
      loadThumbs();
  });

// Show the information bar at the top of the page
function infoBarShow(message, activity)
{   
    clearTimeout(INFO_TIMEOUT);
    $('#info').html('<img src="images/activity-small.gif"> ' + message);
    $('#infobar').removeClass('errorbar');
    $('#infobar:hidden').slideDown(350);
}

// Update the information bar and have it disappear at a set amount of time
function infoBarUpdate(message, type, timeout)
{
    $('#info').fadeOut(350, function() 
                            {
                                if( type == JSON_FAILURE )
                                {
                                    $('#infobar').addClass('errorbar');
                                }
                                $(this).html(message);
                                $(this).fadeIn(350);
                                INFO_TIMEOUT = setTimeout("$('#infobar').slideUp(350)", timeout == undefined ? 3500 : timeout); 
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
            url: 'cropper.php',
            dataType: 'json',
            data: 'r=preview&thumb='+escape(thumb.preview)+'&'+$('#form').formSerialize(),
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
                             
                             $('#thumb_selector').append('<img src="'+json.src+'" border="0" class="preview click {full: \''+thumb.full+'\'}" height="'+thumb_height+'" onclick="loadFull(this)">');
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
function loadFull(thumb)
{
    var params = $(thumb).data();
    
    $('#cropping').hide();
    $('#crop_info').hide();
    
    infoBarShow('Downloading image for cropping...');
    
    $.ajax({type: 'POST',
            url: 'cropper.php',
            dataType: 'json',
            data: 'r=full&image='+escape(params.full)+'&'+$('#form').formSerialize(),
            error: function(request, status, error)
                   {
                        infoBarUpdate(error, JSON_FAILURE, 10000);
                   },
            success: function(json)
                     {
                         if( json.status == JSON_SUCCESS )
                         {                             
                             $('#crop-image').attr({src: json.src, width: json.width+'px', height: json.height+'px'});
                             $('#mask-image').attr({src: json.src, width: json.width+'px', height: json.height+'px'});
                             
                             $('#infobar').slideUp(350);                             
                             $('#imagefile').val(json.imagefile);
                         }
                         else
                         {
                             infoBarUpdate(json.message, json.status, 10000);
                         }
                     }
            });
}

// Setup the cropper each time a full sized image is loaded
function setupCropper()
{
    $('#crop_info').show();
    $('#cropping').show();
    
    if( cropper_created )
        $('#cropper').ResizableDestroy().unbind();

    var img_size = {w: parseInt($('#crop-image').attr('width')), h: parseInt($('#crop-image').attr('height'))};
    var orig_height = img_size.h;
    //var preview_size = $('#dimensions').val().split('x');
    
    img_size = fitInside(img_size.w, img_size.h, max_width, max_height);
    resize_ratio = orig_height / img_size.h;
    var cropper_size = fitInside((preview_size[0]/resize_ratio), (preview_size[1]/resize_ratio), img_size.w, img_size.h);
    
    $('#crop-image').css({height: img_size.h, width: img_size.w});
    $('#mask-image').css({height: img_size.h, width: img_size.w, left: '-1px', top: '-1px'});
    $('#mask').css({height: img_size.h, width: img_size.w});
    
    $('#cropper').Resizable(
        {
            minTop: -1,
            minLeft: -1,
            maxRight: img_size.w,
            maxBottom: img_size.h,
            minWidth: 30,
            minHeight: 30,
            dragHandle: true,
            onDrag: function(x, y)
                    {
                        $('#mask-image').css({left: '-' + (x+1) + 'px', top: '-' + (y+1) + 'px'});
                    },
            onResize : function(size, position) 
                       {
                           $('#mask-image').css({left: '-' + (position.left+1) + 'px', top: '-' + (position.top+1) + 'px'});
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
                          
                          $('#x').val(cropper.offsetLeft < 1 ? 0 : cropper.offsetLeft * resize_ratio);
                          $('#y').val(cropper.offsetTop < 1 ? 0 : cropper.offsetTop * resize_ratio);
                          $('#width').val(size.w * resize_ratio);
                          $('#height').val(size.h * resize_ratio);
                          $('#form').clone(true).append('<input type="hidden" name="r" id="r" value="crop">').appendTo('body').submit().remove();
                      }
    )
    .css({top: 0, 
          left: 0, 
          width: cropper_size.w+'px', 
          height: cropper_size.h+'px'})
    .show();   
    
    cropper_created = true;
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
{/literal}
</script>

<div id="infobar" class="noticebar"><div id="info"></div></div>

<div id="main-content">
<div id="centered-content" style="width: 80%">

    <div style="text-align: center; font-weight: bold; margin-bottom: 5px;">Select one of the thumbnails below to crop your preview image from</div>

    <div id="thumb_selector" style="overflow: auto; height: 150px;"></div>
    
    <div id="crop_info" style="text-align: center; font-weight: bold; margin-bottom: 5px; margin-top: 5px; display: none;">
    To crop your image, move the selection box by clicking your mouse inside the box and dragging it to the location you want to crop.<br />
    You can resize the selection box by clicking and dragging the corners.<br />
    Once you have the area selected that you want to crop, double click your mouse inside the selection box.    
    </div>

    <div id="cropping" style="display: none; text-align: center; margin-top: 10px;">
    
      <table cellpadding="0" cellspacing="0" border="0" align="center">
      <tr>
      <td>
        
        <div id="image-cropper">
          <img src="" id="crop-image" onload="setupCropper()">
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
    

</div>
</div>

<form action="submit.php" id="form" method="POST">
<input type="hidden" name="gallery_id" id="gallery_id" value="{$gallery.gallery_id|htmlspecialchars}">
<input type="hidden" name="gallery_url" id="gallery_url" value="{$gallery.gallery_url|htmlspecialchars}">
<input type="hidden" name="imagefile" id="imagefile" value="">
<input type="hidden" name="x" id="x" value="">
<input type="hidden" name="y" id="y" value="">
<input type="hidden" name="width" id="width" value="">
<input type="hidden" name="height" id="height" value="">
</form>



</body>
</html>