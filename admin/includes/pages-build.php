<script language="JavaScript">
$(function()
  {
      $('#notice').show();
      //$('#info').hide();
      $('#activity').hide();

      if( $('#done').length > 0 )
        setTimeout("window.parent.$('#windowClose').trigger('click')", 3000);
  });
</script>

<img src="images/activity.gif" id="activity" style="padding-left: 10px; padding-top: 10px;">
<div id="notice" style="display: none; padding: 10px;">
<div class="notice">TGP pages have been built successfully</div>
<br />
<i style="font-size: 8pt; color: #aaa;">This window will close automatically in 3 seconds</i>
</div>
<div id="info" style="padding: 10px;">
