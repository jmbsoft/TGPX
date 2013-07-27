<tr id="<?php echo $item['gallery_id']; ?>" class="<?php echo strtolower($item['status']); ?>">
  <td valign="top">
    <input type="checkbox" class="checkbox autocb" name="gallery_id[]" value="<?php echo $item['gallery_id']; ?>">
  </td>
  <td valign="top" class="last">
    <div style="position: relative;">
    
      <table cellpadding="0" cellspacing="0" width="100%">
      <tr class="inner">
      <td valign="top">
      <div class="fieldgroup">
        <label class="lesspad">Gallery URL:</label>
        <span class="ipedit {f: 'gallery_url', s: 90}"><?php echo StringChopTooltip($item['gallery_url'], 90, true); ?></span>
        <a href="<?php echo $item['gallery_url']; ?>" target="_blank" onmouseover="updateGalleryUrl(this)"><img src="images/go.png" border="0" class="function"></a>
      </div>
      
      <div class="fieldgroup">
        <label class="lesspad">E-mail:</label>
        <span class="ipedit {f: 'email', s: 40}">
        <?php echo StringChopTooltip($item['email'], 40); ?>
        </span>
        
        <?php if( !empty($item['partner']) ): ?>
        <b style="padding: 0px 3px 0px 35px;">Partner:</b> <?php echo $item['partner']; ?>
        <?php endif; ?>
        <b style="padding: 0px 3px 0px 35px;">IP:</b> <span class="ipedit {f: 'submit_ip', s: 18}"><?php echo $item['submit_ip']; ?></span>
      </div>
      
      <div class="fieldgroup">
        <label class="lesspad">Categories:</label>
        <span class="ipedit {f: 'categories', t: 'ct', ids: [<?php echo $item['category_ids']; ?>]}">
        <?php echo StringChopTooltip($item['categories'], 90); ?>
        </span>
      </div>
      
      <div class="fieldgroup">
        <label class="lesspad">Description:</label>
        <span class="ipedit {f: 'description', s: 100}">
        <?php
        if( !empty($item['description']) )
           echo StringChopTooltip($item['description'], 90);
        else
           echo '<span class="ipedit-spacer">&nbsp;</span>'; 
        ?>
        </span>
      </div>      

      <div class="fieldgroup">
        <label class="lesspad">Keywords:</label>
        <span class="ipedit {f: 'keywords', s: 100}">
        <?php
        if( !empty($item['keywords']) )
           echo StringChopTooltip($item['keywords'], 90);
        else
           echo '<span class="ipedit-spacer">&nbsp;</span>'; 
        ?>
        </span>
      </div>
      
      <div class="fieldgroup">
        <label class="lesspad">Tags:</label>
        <span class="ipedit {f: 'tags', s: 100}">
        <?php
        if( !empty($item['tags']) )
           echo StringChopTooltip($item['tags'], 90);
        else
           echo '<span class="ipedit-spacer">&nbsp;</span>'; 
        ?>
        </span>
      </div>
      
      <div class="fieldgroup">
        <label class="lesspad">Sponsor:</label>
        <span class="ipedit {f: 'sponsor_id', t: 'sp', id: <?php echo $item['sponsor_id'] ? $item['sponsor_id'] : "''"; ?>}">
        <?php
        if( isset($item['sponsor']) )
           echo StringChopTooltip($item['sponsor'], 90);
        else
           echo '<span class="ipedit-spacer">&nbsp;</span>';
        ?>
        </span>
      </div>
      
      <div class="fieldgroup">
        <label class="lesspad">Added:</label>
        <?php echo $item['date_added']; ?>
        
        <b style="padding: 0px 3px 0px 35px;">Scheduled:</b> <span class="ipedit {f: 'date_scheduled', s: 25, t: 'cal'}"><?php echo $item['date_scheduled']; ?></span>
      </div>
      
      <div class="fieldgroup">
        <label class="lesspad">Displayed:</label>
        <span class="ipedit {f: 'date_displayed', s: 25, t: 'cal'}"><?php echo $item['date_displayed']; ?></span>

        <b style="padding: 0px 3px 0px 35px;">Delete:</b> <span class="ipedit {f: 'date_deletion', s: 25, t: 'cal'}"><?php echo $item['date_deletion']; ?></span>
      </div>
      
      <div class="fieldgroup">       
        <label class="lesspad">Status:</label>
        <span class="ipedit {f: 'status', t: 'st'}"><?php echo $item['status']; ?></span>
        
        <b style="padding: 0px 3px 0px 35px;">Format:</b> <span class="ipedit {f: 'format', t: 'fo'}"><?php echo $item['format']; ?></span>
        <b style="padding: 0px 3px 0px 35px;">Type:</b> <span class="ipedit {f: 'type', t: 'ty'}"><?php echo $item['type']; ?></span>
        <b style="padding: 0px 3px 0px 35px;">Thumbnails:</b> <span class="ipedit {f: 'thumbnails', s: 3}"><?php echo $item['thumbnails']; ?></span>
      </div>

      <div class="fieldgroup">
        <label class="lesspad">Weight:</label>
        <span class="ipedit {f: 'weight', s: 4}">
        <?php echo $item['weight']; ?>
        </span>
        
        <b style="padding: 0px 3px 0px 65px;">Clicks:</b> 
        <span class="ipedit {f: 'clicks', s: 5}">
        <?php echo $item['clicks']; ?>
        </span>
        
        <b style="padding: 0px 3px 0px 65px;">Name:</b> 
        <span class="ipedit {f: 'nickname', s: 40}">
        <?php 
        if( !empty($item['nickname']) )
            echo StringChopTooltip($item['nickname'], 40);
        else
            echo '<span class="ipedit-spacer">&nbsp;</span>';
        ?>
        </span>
      </div>
      
      </td>
      <td style="width: 230px;">
      <div class="preview-holder">
        <div id="no-preview_<?php echo $item['gallery_id']; ?>"<?php if( $item['has_preview'] ) echo ' style="display: none;"'; ?> class="crop-upload">   
        <img src="images/upload-big.png" border="0" alt="Upload" title="Upload" class="click upload-icon-big" onclick="displayUpload(<?php echo $item['gallery_id']; ?>)"><br />
        <img src="images/crop-big.png" border="0" alt="Crop" title="Crop" class="click crop-icon-big" onclick="displayCrop(<?php echo $item['gallery_id']; ?>)">
        </div>
        
        <div id="preview_<?php echo $item['gallery_id']; ?>"<?php if( !$item['has_preview'] ) echo ' style="display: none;"'; ?>>
        <div id="preview_image_<?php echo $item['gallery_id']; ?>">
        <?php if( $item['has_preview'] ): ?>
        <img src="<?php echo $item['previews'][0]['preview_url']; ?>?<?php echo rand(); ?>" onclick="displayFull(this, '<?php echo $item['gallery_id']; ?>')"<?php echo $item['previews'][0]['attrs']; ?>>
        <?php endif; ?>
        </div>
        <div id="thumbactions" style="padding-top: 3px;">
        <img src="images/preview-upload.png" border="0" alt="Upload" title="Upload" class="click" onclick="displayUpload(<?php echo $item['gallery_id']; ?>)">
        <img src="images/preview-crop.png" border="0" alt="Crop" title="Crop" class="click function" onclick="displayCrop(<?php echo $item['gallery_id']; ?>)">
        <select id="preview_id_<?php echo $item['gallery_id']; ?>" onchange="loadPreview(<?php echo $item['gallery_id']; ?>)">
        <?php 
        if( $item['has_preview'] ):
          foreach( $item['previews'] as $preview ): ?>
          <option value="<?php echo $preview['preview_id'] ?>" class="{preview_url: '<?php echo $preview['preview_url'] ?>'}"><?php echo $preview['dimensions'] ? $preview['dimensions'] : '-x-'; ?></option>
        <?php 
          endforeach;
        endif; ?>
        </select>
        <img src="images/trash.png" border="0" alt="Delete" title="Delete" class="click" onclick="deletePreview(<?php echo $item['gallery_id']; ?>);">
        <img src="images/filters.png" border="0" alt="Filters" title="Filters" class="click" onclick="displayFilters(this, <?php echo $item['gallery_id']; ?>);">
        </div>
        </div>
      </div>
      </td>
      </tr>
      </table>
      

      <div style="position: absolute; top: 0px; left: 690px; width: 250px; text-align: right;">
        <span style="color: #999">#<?php echo $item['gallery_id']; ?></span>
        &nbsp;
        <span class="ipedit {f: 'icons', t: 'ic', icons: [<?php echo $item['icons']; ?>]}"><img src="images/icons.png" alt="Icons" title="Icons" class="click-image function"></span>
        <img src="images/search.png" width="12" height="12" alt="Scan" title="Scan" class="click-image function" onclick="openScan('<?php echo $item['gallery_id']; ?>')">
        <a href="index.php?r=txShGalleryBlacklist&gallery_id=<?php echo urlencode($item['gallery_id']); ?>" class="window function {title: 'Blacklist Gallery', height: 400}">
        <img src="images/blacklist.png" width="10" height="12" alt="Blacklist" title="Blacklist"></a>
        <a href="index.php?r=txShWhitelistAdd&gallery_id=<?php echo urlencode($item['gallery_id']); ?>" class="window function {title: 'Whitelist Gallery', height: 475}">
        <img src="images/report.png" width="10" height="12" alt="Whitelist" title="Whitelist"></a>
        <?php if( $item['status'] != 'Disabled' ): ?>
        <img src="images/disable.png" width="12" height="12" alt="Disable" title="Disable" class="click-image function" onclick="executeSingle('disable', '<?php echo $item['gallery_id']; ?>')">
        <?php else: ?>
        <img src="images/enable.png" width="12" height="12" alt="Enable" title="Enable" class="click-image function" onclick="executeSingle('enable', '<?php echo $item['gallery_id']; ?>')">
        <?php endif; ?>
        <a href="index.php?r=txShGalleryEdit&gallery_id=<?php echo urlencode($item['gallery_id']); ?>" class="window function {title: 'Edit Gallery'}">
        <img src="images/edit.png" width="12" height="12" alt="Edit" title="Edit"></a>
        <a href="index.php?r=txShSubmitterMail&gallery_id[]=<?php echo urlencode($item['gallery_id']); ?>" class="window function {title: 'E-mail Submitter'}">
        <img src="images/mail.png" width="12" height="12" alt="E-mail" title="E-mail"></a>
        <?php if( $item['status'] == 'Pending' || $item['status'] == 'Unconfirmed' ): ?>
        <img src="images/check.png" width="12" height="12" alt="Approve" title="Approve" class="click-image function" onclick="processPending('a', '<?php echo $item['gallery_id']; ?>')">
        <img src="images/x.png" width="12" height="12" alt="Reject" title="Reject" class="click-image function" onclick="processPending('r', '<?php echo $item['gallery_id']; ?>')">
        <?php endif; ?>
        <img src="images/trash.png" width="12" height="12" alt="Delete" title="Delete" class="click-image function" onclick="deleteSingle('<?php echo $item['gallery_id']; ?>')">
      </div>
      
      <div style="position: absolute; top: 20px; left: 790px; width: 150px; text-align: right;">
        <?php echo StringChopTooltip($item[$_REQUEST['order']], 20); ?>
      </div>
    
    </div>
  </td>
</tr>