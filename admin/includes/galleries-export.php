<?php
if( !defined('TGPX') ) die("Access denied");

include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#to').html(window.parent.$('#which [@selected]').text());
      $('#search_form').val(window.parent.$('#search').formSerialize());
      $('#results').val(window.parent.$('#results').formSerialize());

      $('#delimiter').bind('change', function()
                                        {
                                            switch($('#delimiter').val())
                                            {
                                            case '':
                                                $('#custom_select:hidden').SlideInLeft(250);
                                                break;
                                            default:
                                                $('#custom_select:visible').SlideOutLeft(250);
                                                break;
                                            }
                                        });


      $('#file_format').bind('change', function()
                                        {
                                            switch($('#file_format').val())
                                            {
                                            case 'delimited':
                                                $('#delimited_fields:hidden').slideDown(250);
                                                break;
                                            default:
                                                $('#delimited_fields:visible').slideUp(250);
                                                break;
                                            }
                                        });

      $('#file_format').trigger('change');
      $('#delimiter').trigger('change');

  });
</script>

<div style="padding: 10px;">

    <div style="float: right;">
      <a href="docs/galleries-export.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
    </div>

    <?php if( $GLOBALS['errstr'] ): ?>
    <div class="alert margin-bottom">
      <?php echo $GLOBALS['errstr']; ?>
    </div>
    <?php endif; ?>

  <form action="index.php" method="POST" id="form">
    <fieldset>
      <legend>Export Settings</legend>

      <?php if( !$C['shell_exec'] || empty($C['php_cli']) ): ?>
        <div class="warn margin-top">
          The gallery export function will have to be run through your browser because your server either has the shell_exec() PHP function disabled or it is missing the
          CLI version of PHP.  Depending on the number of galleries being exported and the PHP restrictions set on your server, it may not be possible to complete export
          procedure in the amount of time that the script is given to run.
        </div>
      <?php endif; ?>

      <div class="fieldgroup">
        <label>Galleries:</label>
        <div style="padding: 3px 0px 0px 0px; margin: 0;" id="to"></div>
      </div>

      <div class="fieldgroup">
        <label for="filename">Galleries File:</label>
        <input type="text" name="galleries-file" id="galleries-file" size="30" value="galleries-export.txt">
      </div>

      <div class="fieldgroup">
        <label for="thumb-file">Thumbs File:</label>
        <input type="text" name="thumbs-file" id="thumbs-file" size="30" value="thumbs-export.txt">
        <img src="images/x.png" border="0" class="click function" onclick="$('#thumbs-file').val('');">
      </div>

      <div class="fieldgroup">
        <label for="file_format">File Format:</label>
        <select name="file_format" id="file_format">
          <option value="sql">Raw SQL Text File</option>
          <option value="delimited">Delimited Text File</option>
        </select>
      </div>

      <div id="delimited_fields" style="display: none; clear: left;">
      <div class="fieldgroup">
        <label for="delimiter">Delimiter:</label>
        <div style="float: left;">
        <select name="delimiter" id="delimiter">
          <option value="pipe">Pipe (|)</option>
          <option value="tab">Tab</option>
          <option value="">Custom --&gt;</option>
        </select>
        </div>
        <div id="custom_select" style="display: none; float: left;">
          <input type="text" name="custom_delimiter" id="custom_delimiter" size="3">
        </div>
      </div>

      <div class="fieldgroup">
        <label for="fields">Fields:</label>
        <select name="fields[]" id="fields" multiple="multiple" size="10">
            <option value="gallery_url">Gallery URL</option>
            <option value="description">Description</option>
            <option value="keywords">Keywords</option>
            <option value="thumbnails">Thumbnails</option>
            <option value="email">E-mail Address</option>
            <option value="nickname">Nickname</option>
            <option value="weight">Weight</option>
            <option value="clicks">Clicks</option>
            <option value="submit_ip">Submit IP</option>
            <option value="gallery_ip">Gallery IP</option>
            <option value="sponsor_id">Sponsor</option>
            <option value="type">Type</option>
            <option value="format">Format</option>
            <option value="status">Status</option>
            <option value="date_added">Date Added</option>
            <option value="date_approved">Date Approved</option>
            <option value="tags">Tags</option>
            <option value="categories">Categories</option>
            <option value="preview_url">Thumbnail URL</option>
            <option value="dimensions">Thumbnail Dimensions</option>
            <option value="preview_file">Thumbnail Filename</option>
        </select>
      </div>
      </div>

    </fieldset>

    <div class="centered margin-top"><button type="submit">Export Galleries</button></div>
    <input type="hidden" name="r" value="txGalleryExport">
    <input type="hidden" name="search_form" id="search_form" value="">
    <input type="hidden" name="results" id="results" value="">
  </form>
</div>


</body>
</html>