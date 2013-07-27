<tr id="<?php echo $item['username']; ?>">
  <td valign="top">
    <input type="checkbox" class="checkbox autocb" name="username[]" value="<?php echo $item['username']; ?>">
  </td>
  <td class="last" valign="top">
      <div class="fieldgroup">
        <label class="lesspad">Username:</label>
        <?php echo $item['username']; ?>
      </div>
      
      <div class="fieldgroup">
        <label class="lesspad">E-mail:</label>
        <a href="mailto:<?php echo $item['email']; ?>"><?php echo $item['email']; ?></a>
      </div>
      
      <div class="fieldgroup">
        <label class="lesspad">IP Address:</label>
        <?php 
        $hostname = gethostbyaddr($item['ip_address']);
        echo $item['ip_address'];
        
        if( $hostname != $item['ip_address'] )
        {
            echo " &nbsp; [$hostname]";
        }
        ?>
      </div>
      
      <div class="fieldgroup">
        <label>Name:</label>
        <input type="text" size="30" name="partner[<?php echo $item['username']; ?>][name]" value="<?php echo $item['name']; ?>">
      </div>
      
      <div class="fieldgroup">
        <label>Weight:</label>
        <input type="text" size="10" name="partner[<?php echo $item['username']; ?>][weight]" value="<?php echo $C['gallery_weight']; ?>">
      </div>
      
      <div class="fieldgroup">
        <label>Per Day:</label>
        <input type="text" size="10" name="partner[<?php echo $item['username']; ?>][per_day]" value="<?php echo $C['submissions_per_person']; ?>">
      </div>
      
      <div class="fieldgroup">
        <label>Categories:</label>
        <div id="category_selects_<?php echo $item['username']; ?>" style="float: left;">
        <div style="margin-bottom: 3px;">
        <select name="partner[<?php echo $item['username']; ?>][categories][]">
        <?php            
        echo OptionTagsAdv($GLOBALS['categories'], null, 'category_id', 'name', 50);
        ?>
        </select>
        <img src="images/add-small.png" onclick="addCategorySelect(this, '#category_selects_<?php echo $item['username']; ?>')" class="click-image" alt="Add Category">
        <img src="images/remove-small.png" onclick="removeCategorySelect(this, '#category_selects_<?php echo $item['username']; ?>')" class="click-image" alt="Remove Category">
        </div>
        </div>
      </div>
      
      <div class="fieldgroup">
        <label>Start Date:</label>
        <input type="text" size="20" name="partner[<?php echo $item['username']; ?>][date_start]" value="" class="calendarSelectDate">
      </div>
      
      <div class="fieldgroup">
        <label>End Date:</label>
        <input type="text" size="20" name="partner[<?php echo $item['username']; ?>][date_end]" value="" class="calendarSelectDate">
      </div>
      
      <?php
      $fields =& GetUserPartnerFields($item);
      foreach( $fields as $field ):
          ArrayHSC($field);
          AdminFormField($field);
          
          if( $field['request_only'] && IsEmptyString($field['value']) )
              continue;

      ?>        
          <div class="fieldgroup">
            <?php if( $field['request_only'] ): ?>
              <label for="<?php echo $field['name']; ?>" class="lesspad"><?php echo $field['label']; ?>:</label>
            <?php
               if( preg_match('~^http://~i', $field['value']) ):
            ?>
            <a href="<?php echo $field['value']; ?>" target="_blank"><?php echo $field['value']; ?></a>
            <?php
               else:
               echo $field['value'];
               endif;
            ?>
            <?php elseif( $field['type'] != FT_CHECKBOX ): ?>
              <label for="<?php echo $field['name']; ?>"><?php echo $field['label']; ?>:</label>
              <?php echo FormField($field, $field['value']); ?>
            <?php else: ?>
              <label style="height: 1px; font-size: 1px;"></label>
              <label for="<?php echo $field['name']; ?>" class="cblabel inline">
              <?php echo FormField($field, $field['value']); ?> <?php echo $field['label']; ?></label>
            <?php endif; ?>
          </div>        
      <?php 
      endforeach;
      ?>
  </td>
  <td class="last" valign="top" style="width: 350px">
      <?php if( count($GLOBALS['REJECTION_CACHE']) > 0 ): ?>
        <div class="fieldgroup">
        <label>Rejection:</label>
        <select name="partner[<?php echo $item['username']; ?>][rejection]">
          <option value="">NONE</option>
          <?php echo OptionTagsAdv($GLOBALS['REJECTION_CACHE'], '', 'email_id', 'identifier', 30); ?>
        </select>
        </div>
      <?php else: ?>
        <input type="hidden" name="partner[<?php echo $item['username']; ?>][rejection]" value="">
      <?php endif; ?>
    
  
      <div class="fieldgroup">
        <label class="lesspad">Options:</label>
        <div style="float: left">
        <?php echo CheckBox("partner[{$item['username']}][allow_redirect]", 'checkbox', 1, $item['allow_redirect']); ?> 
        <label for="<?php echo "partner[{$item['username']}][allow_redirect]"; ?>" class="plain-label lite">Allow URL redirection</label><br />
        <?php echo CheckBox("partner[{$item['username']}][allow_norecip]", 'checkbox', 1, $item['allow_norecip']); ?> 
        <label for="<?php echo "partner[{$item['username']}][allow_norecip]"; ?>" class="plain-label lite">No reciprocal link required</label><br />
        <?php echo CheckBox("partner[{$item['username']}][allow_autoapprove]", 'checkbox', 1, $item['allow_autoapprove']); ?> 
        <label for="<?php echo "partner[{$item['username']}][allow_autoapprove]"; ?>" class="plain-label lite">Auto-approve galleries</label><br />
        <?php echo CheckBox("partner[{$item['username']}][allow_noconfirm]", 'checkbox', 1, $item['allow_noconfirm']); ?> 
        <label for="<?php echo "partner[{$item['username']}][allow_noconfirm]"; ?>" class="plain-label lite">No confirmation e-mail required</label><br />
        <?php echo CheckBox("partner[{$item['username']}][allow_blacklist]", 'checkbox', 1, $item['allow_blacklist']); ?> 
        <label for="<?php echo "partner[{$item['username']}][allow_blacklist]"; ?>" class="plain-label lite">Allow blacklisted items</label><br />
        </div>
        <div style="clear: both; padding-bottom: 4px;"></div>
      </div>
      
      <?php if( count($GLOBALS['ICON_CACHE']) > 0 ): ?>
      <div class="fieldgroup">
        <label class="lesspad">Icons:</label>
        <div style="float: left">
          <?php
          foreach( $GLOBALS['ICON_CACHE'] as $icon ):
              echo CheckBox("partner[{$item['username']}][icons][]", 'checkbox', $icon['icon_id'], '') . " " . $icon['icon_html'] . "<br />";
          endforeach;
          ?>
        </div>
        <div style="clear: both; padding-bottom: 4px;"></div>
      </div>
      <?php endif; ?>
  </td>
</tr>
