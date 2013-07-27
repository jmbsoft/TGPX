<script language="JavaScript">
$(function()
  {
      $('#notice').show();
      $('#info').remove();
      $('#activity').hide();
      
      if( typeof window.parent.Search == 'object' )
          window.parent.Search.search(false);
  });
</script>

<img src="images/activity.gif" id="activity" style="padding-left: 10px; padding-top: 10px;">
<div id="notice" style="display: none; padding: 10px;">
<div class="notice">New TGP pages have been added</div>
<br />
<div style="text-align: center; font-weight: bold; font-size: 9pt;">
<a href="index.php?r=txShPageAddBulk">Add More Pages</a>
</div>
</div>
<div id="info" style="padding: 10px;">