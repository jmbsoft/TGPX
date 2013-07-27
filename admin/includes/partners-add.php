<?php
if( !defined('TGPX') ) die("Access denied");         

$icons =& $DB->FetchAll('SELECT * FROM `tx_icons` ORDER BY `identifier`');

$categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');
array_unshift($categories, array('category_id' => '__ALL__', 'name' => 'ALL CATEGORIES'));

$domains =& $DB->FetchAll('SELECT * FROM `tx_domains` ORDER BY `domain`');
array_unshift($domains, array('domain_id' => '__ALL__', 'domain' => 'ALL DOMAINS'));


$jscripts = array('includes/calendar.js');
$csses = array('includes/calendar.css');
include_once('includes/header.php');
?>

<script language="JavaScript">
$(function()
  {
      $('span').Tooltip();
  });
  
<?PHP if( $GLOBALS['added'] ): ?>
if( typeof window.parent.Search == 'object' )
    window.parent.Search.search(false);
<?PHP endif; ?>
</script>

<div style="padding: 10px;">
    <form action="index.php" method="POST" id="form">
    <div class="margin-bottom">
      <div style="float: right;">
        <a href="docs/partners.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      <?php if( $editing ): ?>
      Update this partner account by making changes to the information below
      <?php else: ?>
      Add a new partner account by filling out the information below
      <?php endif; ?>
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
          <legend>General Settings</legend>
 
            <div class="fieldgroup">
                <label for="username">Username:</label>
                <?php if( $editing ): ?>
                <div style="padding: 3px 0px 0px 0px; margin: 0;"><?php echo $_REQUEST['username']; ?></div>
                <input type="hidden" name="username" value="<?php echo $_REQUEST['username']; ?>" />
                <?php else: ?>
                <input type="text" name="username" id="username" size="20" value="<?php echo $_REQUEST['username']; ?>" />
                <?php endif; ?>
            </div>
            
            <div class="fieldgroup">
                <label for="password">Password:</label>
                <input type="text" name="password" id="password" size="20" value="<?php echo $_REQUEST['password']; ?>" />
                <?php if( $editing ): ?>
                <br /> Leave blank unless you want to change this account's password
                <?php endif; ?>
            </div>
            
            <div class="fieldgroup">
                <label for="name">Name:</label>
                <input type="text" name="name" id="name" size="30" value="<?php echo $_REQUEST['name']; ?>" />
            </div>        
            
            <div class="fieldgroup">
                <label for="email">E-mail Address:</label>
                <input type="text" name="email" id="email" size="40" value="<?php echo $_REQUEST['email']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="weight">Weight:</label>
                <input type="text" name="weight" id="weight" size="10" value="<?php echo $_REQUEST['weight']; ?>" />
            </div>
            
            <div class="fieldgroup">
                <label for="per_day">Galleries Per Day:</label>
                <input type="text" name="per_day" id="per_day" size="10" value="<?php echo $_REQUEST['per_day']; ?>" /> &nbsp; 
                -1 for no limit
            </div>
            
            <div class="fieldgroup">
                <label for="status">Status:</label>
                <select name="status" id="status">
                <?php
                $statuses = array('pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended');
                echo OptionTags($statuses, $_REQUEST['status']);
                ?>
                </select>
            </div>
            
            <div class="fieldgroup">
                <label for="categories[]">Categories:</label>
                <div id="category_selects" style="float: left;">
                <?php 
                
                if( is_array($_REQUEST['categories']) ):                        
                    foreach( $_REQUEST['categories'] as $category ):
                ?>
                
                <div style="margin-bottom: 3px;">
                <select name="categories[]">
                <?php
                echo OptionTagsAdv($categories, $category, 'category_id', 'name', 50);
                ?>
                </select>
                <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
                <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
                </div>
                
                <?php
                    endforeach;
                else: 
                ?>
                <div style="margin-bottom: 3px;">
                <select name="categories[]">
                <?php            
                echo OptionTagsAdv($categories, null, 'category_id', 'name', 50);
                ?>
                </select>
                <img src="images/add-small.png" onclick="addCategorySelect(this)" class="click-image" alt="Add Category">
                <img src="images/remove-small.png" onclick="removeCategorySelect(this)" class="click-image" alt="Remove Category">
                </div>
                <?php endif; ?>            
                </div>
            </div>
            
            <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="categories_as_exclude" class="cblabel inline">
                <?php echo CheckBox('categories_as_exclude', 'checkbox', 1, $_REQUEST['categories_as_exclude']); ?> Use the above selected categories as an exclusion list</label>
            </div>

            <div class="fieldgroup">
                <label for="domains">Domains:</label>
                <div id="domain_selects" style="float: left;">
                <?php 
                
                if( is_array($_REQUEST['domains']) ):                        
                    foreach( $_REQUEST['domains'] as $domain ):
                ?>
                
                <div style="margin-bottom: 3px;">
                <select name="domains[]" id="domains">
                <?php
                echo OptionTagsAdv($domains, $domain, 'domain_id', 'domain', 50);
                ?>
                </select>
                <img src="images/add-small.png" onclick="addCategorySelect(this, '#domain_selects')" class="click-image" alt="Add Domain">
                <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#domain_selects')" class="click-image" alt="Remove Domain">
                </div>
                
                <?php
                    endforeach;
                else: 
                ?>
                <div style="margin-bottom: 3px;">
                <select name="domains[]" id="domains">
                <?php            
                echo OptionTagsAdv($domains, null, 'domain_id', 'domain', 50);
                ?>
                </select>
                <img src="images/add-small.png" onclick="addCategorySelect(this, '#domain_selects')" class="click-image" alt="Add Domain">
                <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#domain_selects')" class="click-image" alt="Remove Domain">
                </div>
                <?php endif; ?>            
                </div>
            </div>
            
            <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="domains_as_exclude" class="cblabel inline">
                <?php echo CheckBox('domains_as_exclude', 'checkbox', 1, $_REQUEST['domains_as_exclude']); ?> Use the above selected domains as an exclusion list</label>
            </div>

            
            <div class="fieldgroup">
                <label for="date_start">Start Date:</label>
                <input type="text" name="date_start" id="date_start" size="20" value="<?php echo $_REQUEST['date_start']; ?>" class="calendarSelectDate" />
             </div>
            
            <div class="fieldgroup">
                <label for="date_end">End Date:</label>
                <input type="text" name="date_end" id="date_end" size="20" value="<?php echo $_REQUEST['date_end']; ?>"  class="calendarSelectDate" />
            </div>
            
            <?php foreach($icons as $icon): ?>
              <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="icons[<?php echo $icon['icon_id']; ?>]" class="cblabel inline">
                <?php echo CheckBox("icons[{$icon['icon_id']}]", 'checkbox', $icon['icon_id'], $_REQUEST['icons'][$icon['icon_id']]) . " " . $icon['icon_html']; ?></label>
              </div>
            <?php endforeach; ?>
            
            <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="allow_redirect" class="cblabel inline"><?php echo CheckBox('allow_redirect', 'checkbox', 1, $_REQUEST['allow_redirect']); ?> Allow URL redirection</label>
            </div>
            
            <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="allow_norecip" class="cblabel inline"><?php echo CheckBox('allow_norecip', 'checkbox', 1, $_REQUEST['allow_norecip']); ?> No reciprocal link required</label><br />
            </div>
            
            <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="allow_autoapprove" class="cblabel inline"><?php echo CheckBox('allow_autoapprove', 'checkbox', 1, $_REQUEST['allow_autoapprove']); ?> Auto-approve galleries</label><br />
            </div>
            
            <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="allow_noconfirm" class="cblabel inline"><?php echo CheckBox('allow_noconfirm', 'checkbox', 1, $_REQUEST['allow_noconfirm']); ?> No confirmation e-mail required</label>
            </div>
            
            <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="allow_blacklist" class="cblabel inline"><?php echo CheckBox('allow_blacklist', 'checkbox', 1, $_REQUEST['allow_blacklist']); ?> Allow blacklisted items</label>
            </div>
            
            <?php if( !$editing ): ?>
            <div class="fieldgroup">
                <label class="lesspad"></label>
                <label for="send_email" class="cblabel inline"><?php echo CheckBox('send_email', 'checkbox', 1, $_REQUEST['send_email']); ?> Send the email-partner-added.tpl e-mail message to this newly created account</label>
            </div>
            <?php endif; ?>
        </fieldset>
        
          <?php
          $result = $DB->Query('SELECT * FROM `tx_partner_field_defs` WHERE `request_only`=0 ORDER BY `field_id`');
          ?>
          <fieldset<?php if( $DB->NumRows($result) < 1 ) echo ' style="display: none;"'; ?>>
            <legend>User Defined Fields</legend>       
            
            <?php
            while( $field = $DB->NextRow($result) ):            
                ArrayHSC($field);
                AdminFormField($field);
            ?>
            
              <div class="fieldgroup">
                <?php if( $field['type'] != FT_CHECKBOX ): ?>
                  <label for="<?php echo $field['name']; ?>"><?php echo $field['label']; ?>:</label>
                  <?php echo FormField($field, $_REQUEST[$field['name']]); ?>
                <?php else: ?>
                  <label style="height: 1px; font-size: 1px;"></label>
                  <label for="<?php echo $field['name']; ?>" class="cblabel inline">
                  <?php echo FormField($field, $_REQUEST[$field['name']]); ?> <?php echo $field['label']; ?></label>
                <?php endif; ?>
              </div>
            
            <?php 
            endwhile;
            $DB->Free($result);
            ?>
          </fieldset>
    
    <div class="centered margin-top">
      <button type="submit"><?php echo ($editing ? 'Update' : 'Add'); ?> Account</button>
    </div>

    <input type="hidden" name="r" value="<?php echo ($editing ? 'txPartnerEdit' : 'txPartnerAdd'); ?>">
    
    <?php if( $editing ): ?>
    <input type="hidden" name="editing" value="1">
    <?PHP endif; ?>
    </form>
</div>

    

</body>
</html>
