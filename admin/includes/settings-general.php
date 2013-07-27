<?php
if( !defined('TGPX') ) die("Access denied");

$defaults = array('document_root' => $_SERVER['DOCUMENT_ROOT'],
                  'install_url' => "http://{$_SERVER['HTTP_HOST']}" . preg_replace('~/admin/index\.php.*~', '', $_SERVER['REQUEST_URI']),
                  'cookie_domain' => preg_replace('~www\.~', '', $_SERVER['HTTP_HOST']),
                  'date_format' => 'm-d-Y',
                  'time_format' => 'h:i:s',
                  'dec_point' => '.',
                  'thousands_sep' => ',',
                  'compression' => 85,
                  'max_submissions' => -1,
                  'submissions_per_person' => 3,
                  'max_links' => 10,
                  'min_desc_length' => 10,
                  'max_desc_length' => 500,
                  'min_thumb_width' => 70,
                  'min_thumb_height' => 70,
                  'max_thumb_width' => 400,
                  'max_thumb_height' => 400,
                  'max_keywords' => 8,
                  'gallery_weight' => 1,
                  'font_dir' => "{$GLOBALS['BASE_DIR']}/fonts",
                  'min_code_length' => 4,
                  'max_code_length' => 6,
                  'permanent_hold' => 7,
                  'submitted_hold' => 14,
                  'magick_filters' => '-modulate 110,102,100 -sharpen 1x1 -enhance',
                  'page_permissions' => '666');
                  
$defaults['preview_url'] = $defaults['install_url'] . '/thumbs';

if( !isset($C['min_thumb_width']) || !isset($C['min_thumb_height']) )
{
    $C['min_thumb_width'] = 70;
    $C['min_thumb_height'] = 70;
}

if( !isset($C['max_thumb_width']) || !isset($C['max_thumb_height']) )
{
    $C['max_thumb_width'] = 400;
    $C['max_thumb_height'] = 400;
}
   
if( !isset($C['from_email']) )
{
    $C = array_merge($C, $defaults);
}

include_once('../includes/imager.class.php');
include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('#form').bind('submit', function()
                                {
                                    $('input[@type=checkbox]').each(function() 
                                                       {
                                                           if( !this.checked )
                                                           {
                                                               $('#form').append('<input type="hidden" name="'+this.name+'" value="0">');
                                                           }
                                                       });
                                });
                                
      $('#imager').bind('change', function()
                                  {
                                      if( $(this).val() == 'magick' )
                                          $('#filters_field:hidden').slideDown(300);
                                      else
                                          $('#filters_field:visible').slideUp(300);
                                  });
                                  
      $('#imager').trigger('change');
  });
</script>
<style>
.fieldgroup label {
  width: 190px;
}
</style>

<?php if( isset($GLOBALS['no_access_list']) ): ?>
<div class="warn centered">
  ENHANCED SECURITY: You have not yet setup an access list, which will add increased security to your control panel.
  <a href="docs/access-list.html" target="_blank"><img src="images/help-small.png" border="0" width="12" height="12" style="position: relative; top: 1px; left: 10px;"></a>
</div>
<?php endif; ?>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/settings.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Use this page to adjust the software's general settings
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
        <legend>Basic Settings</legend>
        <div class="fieldgroup">
            <label for="document_root">Document Root:</label>
            <input type="text" name="document_root" id="document_root" size="70" value="<?PHP echo $C['document_root']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="install_url">TGPX URL:</label>
            <input type="text" name="install_url" id="install_url" size="70" value="<?PHP echo $C['install_url']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="preview_url">Thumbnail URL:</label>
            <input type="text" name="preview_url" id="preview_url" size="70" value="<?PHP echo $C['preview_url']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="cookie_domain">Cookie Domain:</label>
            <input type="text" name="cookie_domain" id="cookie_domain" size="30" value="<?PHP echo $C['cookie_domain']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="page_permissions">Page Permissions:</label>
            <input type="text" name="page_permissions" id="page_permissions" size="5" value="<?PHP echo $C['page_permissions']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="from_email">E-mail Address:</label>
            <input type="text" name="from_email" id="from_email" size="40" value="<?PHP echo $C['from_email']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="from_email_name">E-mail Name:</label>
            <input type="text" name="from_email_name" id="from_email_name" size="40" value="<?PHP echo $C['from_email_name']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="email_type">E-mail Sender:</label>
            <select name="email_type">
              <?php
              $email_types = array(MT_PHP => 'PHP mail() function',
                                   MT_SENDMAIL => 'Sendmail',
                                   MT_SMTP => 'SMTP Server');
              echo OptionTags($email_types, $C['email_type']);
              ?>
            </select>
            <input type="text" name="mailer" id="mailer" size="40" value="<?PHP echo $C['mailer']; ?>" />
        </div>
               
        <div class="fieldgroup">
            <label for="date_format">Date Format:</label>
            <input type="text" name="date_format" id="date_format" size="20" value="<?PHP echo $C['date_format']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="time_format">Time Format:</label>
            <input type="text" name="time_format" id="time_format" size="20" value="<?PHP echo $C['time_format']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="timezone">Timezone:</label>
            <select name="timezone" id="timezone">
            <?PHP
            $zones = array('-12' => '(GMT -12:00) Eniwetok, Kwajalein',
                           '-11' => '(GMT -11:00) Midway Island, Samoa',
                           '-10' => '(GMT -10:00) Hawaii',
                           '-9' => '(GMT -9:00) Alaska',
                           '-8' => '(GMT -8:00) Pacific Time (US & Canada)',
                           '-7' => '(GMT -7:00) Mountain Time (US & Canada)',
                           '-6' => '(GMT -6:00) Central Time (US & Canada), Mexico City',
                           '-5' => '(GMT -5:00) Eastern Time (US & Canada), Bogota, Lima',
                           '-4' => '(GMT -4:00) Atlantic Time (Canada), La Paz, Santiago',
                           '-3.5' => '(GMT -3:30) Newfoundland',
                           '-3' => '(GMT -3:00) Brazil, Buenos Aires, Georgetown',
                           '-2' => '(GMT -2:00) Mid-Atlantic',
                           '-1' => '(GMT -1:00 hour) Azores, Cape Verde Islands',
                           '0' => '(GMT) Western Europe Time, London, Lisbon, Casablanca',
                           '1' => '(GMT +1:00 hour) Brussels, Copenhagen, Madrid, Paris',
                           '2' => '(GMT +2:00) Kaliningrad, South Africa',
                           '3' => '(GMT +3:00) Baghdad, Riyadh, Moscow, St. Petersburg',
                           '3.5' => '(GMT +3:30) Tehran',
                           '4' => '(GMT +4:00) Abu Dhabi, Muscat, Baku, Tbilisi',
                           '4.5' => '(GMT +4:30) Kabul',
                           '5' => '(GMT +5:00) Ekaterinburg, Islamabad, Karachi, Tashkent',
                           '5.5' => '(GMT +5:30) Bombay, Calcutta, Madras, New Delhi',
                           '6' => '(GMT +6:00) Almaty, Dhaka, Colombo',
                           '6.5' => '(GMT +6:30) Yangon, Cocos Islands',
                           '7' => '(GMT +7:00) Bangkok, Hanoi, Jakarta',
                           '8' => '(GMT +8:00) Beijing, Perth, Singapore, Hong Kong',
                           '9' => '(GMT +9:00) Tokyo, Seoul, Osaka, Sapporo, Yakutsk',
                           '9.5' => '(GMT +9:30) Adelaide, Darwin',
                           '10' => '(GMT +10:00) Eastern Australia, Guam, Vladivostok',
                           '11' => '(GMT +11:00) Magadan, Solomon Islands, New Caledonia',
                           '12' => '(GMT +12:00) Auckland, Wellington, Fiji, Kamchatka');

            echo OptionTags($zones, $C['timezone']);
            ?>
            </select>
        </div>        
        
        <div class="fieldgroup">
            <label for="dec_point">Decimal Point:</label>
            <input type="text" name="dec_point" id="dec_point" size="10" value="<?PHP echo $C['dec_point']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="thousands_sep">Thousands Separator:</label>
            <input type="text" name="thousands_sep" id="thousands_sep" size="10" value="<?PHP echo $C['thousands_sep']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="reset_on_rotate" class="cblabel inline"><?php echo CheckBox('reset_on_rotate', 'checkbox', 1, $C['reset_on_rotate']); ?> 
            Reset click, build, and used counters when permanent gallery is rotated from holding to approved</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="log_searches" class="cblabel inline"><?php echo CheckBox('log_searches', 'checkbox', 1, $C['log_searches']); ?> 
            Keep statistics on the search terms entered by surfers</label>
        </div>
      </fieldset>
      
      <fieldset>
        <legend>Image Manipulation Settings</legend>        
        <?php if( !$C['have_magick'] && !$C['have_gd'] ): ?>
        
        <div class="warn">
          You will be unable to use the thumbnail cropping and resizing features of TGPX because:
          
          <ol>
          <li> PHP on your server does not have the GD extension installed
          <li> The ImageMagick command line tools 
          
          <?php if( $C['safe_mode'] ): ?>
          cannot be used because PHP is running in safe_mode
          <?php elseif( !$C['shell_exec'] ): ?>
          cannot be used because the PHP shell_exec() function is disabled
          <?php else: ?>
          cannot be located
          <?php endif; ?>
          
          </ol>
          
          If you would like to use these features, contact your server administrator and ask them to install the PHP GD extension or have
          them install ImageMagick on your server and ensure that PHP is not running in safe_mode so that the command line tools can be utilized.
        </div>
        
        <?php else: ?>
          <div class="fieldgroup">
            <label for="imager">Image Library:</label>
            <select name="imager" id="imager">
              <?php
              $imager_types = array();
              if( $C['have_magick'] ) $imager_types['magick'] = 'ImageMagick';
              if( $C['have_gd'] ) $imager_types['gd'] = 'GD';
              
              echo OptionTags($imager_types, $C['imager']);
              ?>
            </select>
          </div>
          
          <div class="fieldgroup">
            <label for="handle_bad_size">Bad Sized Uploaded Thumb:</label>
            <select name="handle_bad_size" id="handle_bad_size">
            <?PHP
            $bad_size_options = array('reject' => 'Reject',
                                      'crop' => 'Automatically crop');

            echo OptionTags($bad_size_options, $C['handle_bad_size']);
            ?>
            </select>
          </div>
        
          <div class="fieldgroup" id="filters_field"<?php if( $C['imager'] != 'magick' ): ?> style="display: none"<?php endif; ?>>
            <label for="magick_filters">ImageMagick Arguments:</label>
            <input type="text" name="magick_filters" id="magick_filters" size="60" value="<?PHP echo $C['magick_filters']; ?>" />
          </div>
          
          <div class="fieldgroup">
            <label for="compression">JPEG Quality:</label>
            <input type="text" name="compression" id="compression" size="10" value="<?PHP echo $C['compression']; ?>" />
          </div>
          
          <div class="fieldgroup">
            <label for="portrait_bias">Portrait Bias:</label>
            <select name="portrait_bias">
              <?PHP
              $portrait_bias_options = array(BIAS_CENTER => 'Center',
                                             BIAS_TOP => 'Top',
                                             BIAS_BOTTOM => 'Bottom');

              echo OptionTags($portrait_bias_options, $C['portrait_bias']);
            ?>
            </select>
          </div>
          
          <div class="fieldgroup">
            <label for="landscape_bias">Landscape Bias:</label>
            <select name="landscape_bias">
              <?PHP
              $landscape_bias_options = array(BIAS_CENTER => 'Center',
                                             BIAS_LEFT => 'Left',
                                             BIAS_RIGHT => 'Right');

              echo OptionTags($landscape_bias_options, $C['landscape_bias']);
            ?>
            </select>
          </div>
        
        <?php endif; ?>
      </fieldset>
      
      <fieldset>
        <legend>Gallery Submission Settings</legend>
        <div class="fieldgroup">
            <label for="max_submissions">Global Submissions Per Day:</label>
            <input type="text" name="max_submissions" id="max_submissions" size="5" value="<?PHP echo $C['max_submissions']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="submissions_per_person">Submissions Per Person:</label>
            <input type="text" name="submissions_per_person" id="submissions_per_person" size="5" value="<?PHP echo $C['submissions_per_person']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="min_desc_length">Description Length:</label>
            <input type="text" name="min_desc_length" id="min_desc_length" size="5" value="<?PHP echo $C['min_desc_length']; ?>" /> to
            <input type="text" name="max_desc_length" id="max_desc_length" size="5" value="<?PHP echo $C['max_desc_length']; ?>" /> characters
        </div>
        
        <div class="fieldgroup">
            <label for="max_keywords">Keywords Allowed:</label>
            <input type="text" name="max_keywords" id="max_keywords" size="5" value="<?PHP echo $C['max_keywords']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="gallery_weight">Default Gallery Weight:</label>
            <input type="text" name="gallery_weight" id="gallery_weight" size="10" value="<?PHP echo $C['gallery_weight']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="min_thumb_size">Minimum Gallery Thumb Size:</label>
            <input type="text" name="min_thumb_size" id="min_thumb_size" size="10" value="<?PHP echo $C['min_thumb_width']; ?>x<?PHP echo $C['min_thumb_height']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="max_thumb_size">Maximum Gallery Thumb Size:</label>
            <input type="text" name="max_thumb_size" id="max_thumb_size" size="10" value="<?PHP echo $C['max_thumb_width']; ?>x<?PHP echo $C['max_thumb_height']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="max_links">Maximum Links Allowed:</label>
            <input type="text" name="max_links" id="max_links" size="10" value="<?PHP echo $C['max_links']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="permanent_hold">Permanent Holding Period:</label>
            <input type="text" name="permanent_hold" id="permanent_hold" size="10" value="<?PHP echo $C['permanent_hold']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="submitted_hold">Submitted Holding Period:</label>
            <input type="text" name="submitted_hold" id="submitted_hold" size="10" value="<?PHP echo $C['submitted_hold']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="submit_status">Submission Status:</label>
            <select name="submit_status" id="submit_status">
            <?PHP
            $submit_statuses = array('all' => 'Open to all submitters',
                                     'partner' => 'Open only to partners',
                                     'closed' => 'Closed for all');

            echo OptionTags($submit_statuses, $C['submit_status']);
            ?>
            </select>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_num_thumbs" class="cblabel inline"><?php echo CheckBox('allow_num_thumbs', 'checkbox', 1, $C['allow_num_thumbs']); ?> 
            Allow user to submit the number of thumbs on their gallery</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_format" class="cblabel inline"><?php echo CheckBox('allow_format', 'checkbox', 1, $C['allow_format']); ?> 
            Allow user to submit the format (pictures or movies) of their gallery</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_multiple_cats" class="cblabel inline"><?php echo CheckBox('allow_multiple_cats', 'checkbox', 1, $C['allow_multiple_cats']); ?> 
            Allow user to select up to </label> <input type="text" name="max_categories" size="3" value="<?PHP echo $C['max_categories']; ?>"> categories for their gallery
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="use_transparent_blacklist" class="cblabel inline"><?php echo CheckBox('use_transparent_blacklist', 'checkbox', 1, $C['use_transparent_blacklist']); ?> 
            Make the blacklist be transparent to the gallery submitter</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="require_description" class="cblabel inline"><?php echo CheckBox('require_description', 'checkbox', 1, $C['require_description']); ?> 
            Require a gallery description for submission</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="require_nickname" class="cblabel inline"><?php echo CheckBox('require_nickname', 'checkbox', 1, $C['require_nickname']); ?> 
            Require a name/nickname for submission</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="require_recip" class="cblabel inline"><?php echo CheckBox('require_recip', 'checkbox', 1, $C['require_recip']); ?> 
            Require a reciprocal link for submission</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="give_recip_boost" class="cblabel inline"><?php echo CheckBox('give_recip_boost', 'checkbox', 1, $C['give_recip_boost']); ?> 
            Give galleries with a reciprocal link a +1 weight boost</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_duplicates" class="cblabel inline"><?php echo CheckBox('allow_duplicates', 'checkbox', 1, $C['allow_duplicates']); ?> 
            Allow duplicate gallery URLs to be submitted</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_same_hash" class="cblabel inline"><?php echo CheckBox('allow_same_hash', 'checkbox', 1, $C['allow_same_hash']); ?> 
            Allow galleries with the same MD5 hash as an existing gallery to be submitted</label>
        </div>       
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="require_2257" class="cblabel inline"><?php echo CheckBox('require_2257', 'checkbox', 1, $C['require_2257']); ?> 
            Require 2257 code to appear on the gallery for submission</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_keywords" class="cblabel inline"><?php echo CheckBox('allow_keywords', 'checkbox', 1, $C['allow_keywords']); ?> 
            Allow keywords to be submitted with gallery data</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="require_keywords" class="cblabel inline"><?php echo CheckBox('require_keywords', 'checkbox', 1, $C['require_keywords']); ?> 
            Require keywords to be submitted with gallery data</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_preview_partner" class="cblabel inline"><?php echo CheckBox('allow_preview_partner', 'checkbox', 1, $C['allow_preview_partner']); ?> 
            Allow only partners to submit preview thumbnails with their galleries</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="require_confirm" class="cblabel inline"><?php echo CheckBox('require_confirm', 'checkbox', 1, $C['require_confirm']); ?> 
            Require that gallery submissions must be confirmed through e-mail</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="require_content_on_server" class="cblabel inline"><?php echo CheckBox('require_content_on_server', 'checkbox', 1, $C['require_content_on_server']); ?> 
            Require that gallery content be hosted on same server as the gallery</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="allow_autoapprove" class="cblabel inline"><?php echo CheckBox('allow_autoapprove', 'checkbox', 1, $C['allow_autoapprove']); ?> 
            Automatically approve galleries after being scanned and confirmed</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="check_content_size" class="cblabel inline"><?php echo CheckBox('check_content_size', 'checkbox', 1, $C['check_content_size']); ?> 
            Check size of gallery content during submission</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="check_download_speed" class="cblabel inline"><?php echo CheckBox('check_download_speed', 'checkbox', 1, $C['check_download_speed']); ?> 
            Gallery download speed must be</label> <input type="text" name="min_download_speed" size="3" value="<?PHP echo $C['min_download_speed']; ?>"> KB/s or faster
        </div>       
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="log_emails" class="cblabel inline"><?php echo CheckBox('log_emails', 'checkbox', 1, $C['log_emails']); ?> 
            Keep a log of all e-mail addresses used for gallery submissions</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="email_on_approval" class="cblabel inline"><?php echo CheckBox('email_on_approval', 'checkbox', 1, $C['email_on_approval']); ?> 
            Send e-mail message to gallery submitter when gallery is approved
        </div>
        
      </fieldset>
      
      <fieldset>
        <legend>Review Galleries Settings</legend>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="review_noreject" class="cblabel inline"><?php echo CheckBox('review_noreject', 'checkbox', 1, $C['review_noreject']); ?> 
            Do not display rejection e-mail selection field when rejecting a gallery
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="review_noconfirm" class="cblabel inline"><?php echo CheckBox('review_noconfirm', 'checkbox', 1, $C['review_noconfirm']); ?> 
            Do not display confirmation messages when approving, rejecting, or deleting an gallery
        </div>
      </fieldset>
      
      <fieldset>
        <legend>Verification Code Settings</legend>
        
        <div class="fieldgroup">
            <label for="font_dir">Font Directory:</label>
            <input type="text" name="font_dir" id="font_dir" size="60" value="<?PHP echo $C['font_dir']; ?>" />
        </div>
        
        <div class="fieldgroup">
            <label for="min_code_length">Code Length:</label>
            <input type="text" name="min_code_length" id="min_code_length" size="5" value="<?PHP echo $C['min_code_length']; ?>" /> to
            <input type="text" name="max_code_length" id="max_code_length" size="5" value="<?PHP echo $C['max_code_length']; ?>" /> characters
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="use_words" class="cblabel inline"><?php echo CheckBox('use_words', 'checkbox', 1, $C['use_words']); ?> 
            Use words file for verification codes</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="gallery_captcha" class="cblabel inline"><?php echo CheckBox('gallery_captcha', 'checkbox', 1, $C['gallery_captcha']); ?> 
            Require verification code on gallery submission form for general submitters</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="gallery_captcha_partner" class="cblabel inline"><?php echo CheckBox('gallery_captcha_partner', 'checkbox', 1, $C['gallery_captcha_partner']); ?> 
            Require verification code on gallery submission form for partner submitters</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="request_captcha" class="cblabel inline"><?php echo CheckBox('request_captcha', 'checkbox', 1, $C['request_captcha']); ?> 
            Require verification code on partner account request form</label>
        </div>
        
        <div class="fieldgroup">
            <label class="lesspad"></label>
            <label for="report_captcha" class="cblabel inline"><?php echo CheckBox('report_captcha', 'checkbox', 1, $C['report_captcha']); ?> 
            Require verification code on bad gallery reporting form</label>
        </div>
      </fieldset>
      
    <div class="centered margin-top">
      <button type="submit">Save Settings</button>
    </div>
    
    <input type="hidden" name="r" value="txGeneralSettingsSave">
    </form>
</div>


</body>
</html>
