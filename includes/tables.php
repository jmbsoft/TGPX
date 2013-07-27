=>[tx_galleries]
`gallery_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`gallery_url` TEXT,
`description` TEXT,
`keywords` TEXT,
`thumbnails` INT,
`email` CHAR(128),
`nickname` CHAR(128),
`weight` INT NOT NULL,
`clicks` INT NOT NULL,
`submit_ip` CHAR(16),
`gallery_ip` CHAR(16),
`sponsor_id` INT,
`type` ENUM('submitted','permanent'),
`format` ENUM('pictures','movies'),
`status` ENUM('submitting','unconfirmed','pending','approved','used','holding','disabled'),
`previous_status` ENUM('submitting','unconfirmed','pending','approved','used','holding','disabled'),
`date_scanned` DATETIME,
`date_added` DATETIME NOT NULL,
`date_approved` DATETIME,
`date_scheduled` DATETIME,
`date_displayed` DATETIME,
`date_deletion` DATETIME,
`partner` CHAR(32),
`administrator` CHAR(32),
`admin_comments` TEXT,
`page_hash` CHAR(32),
`has_recip` TINYINT NOT NULL,
`has_preview` TINYINT NOT NULL,
`allow_scan` TINYINT NOT NULL,
`allow_preview` TINYINT NOT NULL,
`times_selected` INT NOT NULL,
`used_counter` INT NOT NULL,
`build_counter` INT NOT NULL,
`tags` TEXT,
`categories` TEXT,
INDEX(`gallery_url`(100)),
INDEX(`date_added`),
INDEX(`date_approved`),
INDEX(`date_displayed`),
INDEX(`date_scheduled`),
INDEX(`clicks`),
INDEX(`page_hash`),
INDEX(`email`),
INDEX(`submit_ip`),
INDEX(`sponsor_id`),
FULLTEXT(`description`,`keywords`),
FULLTEXT(`keywords`),
FULLTEXT(`tags`),
FULLTEXT(`categories`)

=>[tx_gallery_previews]
`preview_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`gallery_id` INT NOT NULL,
`preview_url` TEXT,
`dimensions` CHAR(32),
INDEX(`gallery_id`),
INDEX(`dimensions`)

=>[tx_gallery_used]
`gallery_id` INT NOT NULL,
`page_id` INT NOT NULL,
`this_build` TINYINT NOT NULL,
`new` TINYINT NOT NULL,
PRIMARY KEY(`gallery_id`,`page_id`),
INDEX(`this_build`)

=>[tx_gallery_used_page]
`gallery_id` INT NOT NULL PRIMARY KEY

=>[tx_gallery_confirms]
`gallery_id` INT NOT NULL PRIMARY KEY,
`confirm_id` CHAR(32),
`date_sent` DATETIME,
INDEX(`confirm_id`)

=>[tx_gallery_field_defs]
`field_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`name` VARCHAR(32),
`label` TEXT,
`type` VARCHAR(64),
`tag_attributes` TEXT,
`options` TEXT,
`validation` INT NOT NULL DEFAULT 0,
`validation_extras` TEXT,
`validation_message` TEXT,        
`on_submit` TINYINT NOT NULL DEFAULT 0,
`required` TINYINT NOT NULL DEFAULT 0

=>[tx_gallery_fields]
`gallery_id` INT NOT NULL PRIMARY KEY

=>[tx_gallery_icons]
`gallery_id` INT NOT NULL,
`icon_id` INT NOT NULL,
PRIMARY KEY(`gallery_id`,`icon_id`),
INDEX(`icon_id`)

=>[tx_reciprocals]
`recip_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`identifier` TEXT,
`code` TEXT,
`regex` TINYINT NOT NULL

=>[tx_2257]
`code_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`identifier` TEXT,
`code` TEXT,
`regex` TINYINT NOT NULL

=>[tx_blacklist]
`blacklist_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`type` CHAR(32) NOT NULL,
`regex` TINYINT NOT NULL,
`value` TEXT,
`reason` TEXT,
INDEX(`type`),
INDEX(`value`(50))

=>[tx_whitelist]
`whitelist_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`type` CHAR(32) NOT NULL,
`regex` TINYINT NOT NULL,
`value` TEXT,
`reason` TEXT,
`allow_redirect` TINYINT DEFAULT 0,
`allow_norecip` TINYINT DEFAULT 0,
`allow_autoapprove` TINYINT DEFAULT 0,
`allow_noconfirm` TINYINT DEFAULT 0,
`allow_blacklist` TINYINT DEFAULT 0,
INDEX(`type`),
INDEX(`value`(50))

=>[tx_rejections]
`email_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`identifier` VARCHAR(128),
`plain` TEXT,
`compiled` TEXT

=>[tx_sponsors]
`sponsor_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`name` TEXT,
`url` TEXT,
INDEX(`name`(100))

=>[tx_partners]
`username` VARCHAR(32) NOT NULL PRIMARY KEY,
`password` VARCHAR(40),
`name` VARCHAR(255),
`email` VARCHAR(255),
`ip_address` VARCHAR(16),
`date_added` DATETIME,
`date_last_submit` DATETIME,
`date_start` DATETIME,
`date_end` DATETIME,
`per_day` INT NOT NULL,
`weight` INT NOT NULL DEFAULT 0,
`categories` TEXT,
`categories_as_exclude` TINYINT NOT NULL,
`domains` TEXT,
`domains_as_exclude` TINYINT NOT NULL,
`submitted` INT,
`removed` INT,
`status` ENUM('pending','active','suspended'),
`session` CHAR(40),
`session_start` INT,
`allow_redirect` TINYINT DEFAULT 0,
`allow_norecip` TINYINT DEFAULT 0,
`allow_autoapprove` TINYINT DEFAULT 0,
`allow_noconfirm` TINYINT DEFAULT 0,
`allow_blacklist` TINYINT DEFAULT 0

=>[tx_partner_field_defs]
`field_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`name` VARCHAR(32),
`label` TEXT,
`type` VARCHAR(64),
`tag_attributes` TEXT,
`options` TEXT,
`validation` INT NOT NULL DEFAULT 0,
`validation_extras` TEXT,
`validation_message` TEXT,        
`on_request` TINYINT NOT NULL DEFAULT 0,
`request_only` TINYINT NOT NULL DEFAULT 0,
`required_request` TINYINT NOT NULL DEFAULT 0,
`on_edit` TINYINT NOT NULL DEFAULT 0,
`required_edit` TINYINT NOT NULL DEFAULT 0

=>[tx_partner_fields]
`username` VARCHAR(32) NOT NULL PRIMARY KEY,
`sample_url_1` text,
`sample_url_2` text,
`sample_url_3` text

=>[tx_partner_icons]
`username` VARCHAR(32) NOT NULL,
`icon_id` INT NOT NULL,
PRIMARY KEY(`username`,`icon_id`),
INDEX(`icon_id`)

=>[tx_partner_confirms]
`username` VARCHAR(32) NOT NULL PRIMARY KEY,
`confirm_id` CHAR(32),
`date_sent` DATETIME,
INDEX(`confirm_id`)

=>[tx_captcha]
`session` VARCHAR(40) NOT NULL,
`code` VARCHAR(64) NOT NULL,
`time_stamp` INT NOT NULL,
INDEX(`session`)

=>[tx_categories]
`category_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`name` TEXT,
`tag` TEXT,
`pics_allowed` TINYINT NOT NULL,
`pics_extensions` TEXT,
`pics_minimum` INT,
`pics_maximum` INT,
`pics_file_size` INT,
`pics_preview_size` CHAR(32),
`pics_preview_allowed` TINYINT NOT NULL,
`pics_annotation` INT,
`movies_allowed` TINYINT NOT NULL,
`movies_extensions` TEXT,
`movies_minimum` INT,
`movies_maximum` INT,
`movies_file_size` INT,
`movies_preview_size` CHAR(32),
`movies_preview_allowed` TINYINT NOT NULL,
`movies_annotation` INT,
`per_day` INT NOT NULL,
`hidden` TINYINT NOT NULL,
`date_last_submit` DATETIME,
`meta_description` TEXT,
`meta_keywords` TEXT,
FULLTEXT(`name`),
INDEX(`name`(100)),
INDEX(`tag`(100))

=>[tx_categories_build]
`category_id` INT NOT NULL PRIMARY KEY,
`name` TEXT,
`galleries` INT,
`clicks` INT,
`build_counter` INT,
`used` INT,
`page_url` TEXT,
INDEX(`name`(100))

=>[tx_annotations]
`annotation_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`identifier` TEXT,
`type` ENUM('text','image'),
`string` TEXT,
`use_category` TINYINT NOT NULL,
`font_file` TEXT,
`text_size` INT,
`text_color` VARCHAR(8),
`shadow_color` VARCHAR(8),
`image_file` TEXT,
`transparency` VARCHAR(8),
`location` ENUM('NorthWest','North','NorthEast','SouthWest','South','SouthEast')

=>[tx_administrators]
`username` CHAR(32) NOT NULL PRIMARY KEY,
`password` CHAR(40) NOT NULL,
`session` CHAR(40),
`session_start` INT,
`name` CHAR(80),
`email` CHAR(100),
`type` ENUM('administrator','editor') NOT NULL,
`date_login` DATETIME,
`date_last_login` DATETIME,
`login_ip` CHAR(18),
`last_login_ip` CHAR(18),
`approved` INT NOT NULL,
`rejected` INT NOT NULL,
`banned` INT NOT NULL,
`notifications` INT,
`rights` INT,
`reports_waiting` INT,
`requests_waiting` INT,
INDEX(`email`),
INDEX(`name`)

=>[tx_icons]
`icon_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`identifier` TEXT,
`icon_html` TEXT

=>[tx_stored_values]
`name` VARCHAR(128) NOT NULL PRIMARY KEY,
`value` TEXT

=>[tx_scanner_configs]
`config_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`identifier` VARCHAR(255),
`current_status` TEXT,
`status_updated` INT,
`pid` INT NOT NULL DEFAULT 0,
`date_last_run` DATETIME,
`configuration` TEXT

=>[tx_scanner_results]
`config_id` INT NOT NULL,
`gallery_id` INT NOT NULL,
`gallery_url` TEXT,
`http_status` VARCHAR(255),
`date_scanned` DATETIME NOT NULL,
`action` TEXT,
`message` TEXT,
INDEX(`config_id`),
INDEX(`gallery_id`),
INDEX(`gallery_url`(100)),
INDEX(`http_status`)

=>[tx_scanner_history]
`history_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`config_id` INT NOT NULL,
`date_start` DATETIME,
`date_end` DATETIME,
`selected` INT,
`scanned` INT,
`exceptions` INT,
`disabled` INT,
`deleted` INT,
`blacklisted` INT,
INDEX(`config_id`),
INDEX(`date_start`)

=>[tx_reports]
`report_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`gallery_id` INT NOT NULL,
`report_ip` CHAR(16),
`date_reported` DATETIME NOT NULL,
`reason` TEXT

=>[tx_pages]
`page_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`filename` TEXT,
`page_url` TEXT,
`category_id` INT,
`build_order` INT NOT NULL,
`locked` TINYINT NOT NULL,
`tags` TEXT,
`template` MEDIUMTEXT,
`compiled` MEDIUMTEXT,
INDEX(`build_order`),
INDEX(`category_id`),
INDEX(`filename`(200)),
FULLTEXT(`tags`)

=>[tx_email_log]
`email` CHAR(128) PRIMARY KEY

=>[tx_addresses]
`gallery_id` INT NOT NULL,
`ip_address` CHAR(16) NOT NULL,
`click_time` INT,
PRIMARY KEY(gallery_id, ip_address),
INDEX(`click_time`)

=>[tx_undos]
`preview_id` INT NOT NULL,
`undo_level` INT NOT NULL,
`image` MEDIUMBLOB,
INDEX(`preview_id`,`undo_level`)

=>[tx_saved_searches]
`search_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`identifier` CHAR(64) NOT NULL,
`fields` TEXT

=>[tx_rss_feeds]
`feed_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`feed_url` TEXT,
`date_last_import` DATETIME,
`sponsor_id` INT,
`settings` TEXT,
INDEX(`feed_url`(100))

=>[tx_build_history]
`history_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`date_start` DATETIME,
`date_end` DATETIME,
`current_page_url` TEXT,
`pages_total` INT,
`pages_built` INT,
`error_message` TEXT,
INDEX(`date_start`)

=>[tx_search_terms]
`term_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`term` TEXT,
`searches` INT,
`date_last_search` DATETIME,
INDEX(`term`(100)),
INDEX(`date_last_search`)

=>[tx_template_globals]
`name` VARCHAR(128) NOT NULL PRIMARY KEY,
`value` LONGTEXT

=>[tx_domains]
`domain_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
`domain` TEXT,
`base_url` TEXT,
`document_root` TEXT,
`categories` TEXT,
`as_exclude` TINYINT NOT NULL,
`tags` TEXT,
`template_prefix` TEXT,
INDEX(`domain`(100))


=>[tx_ads]
`ad_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
`ad_url` TEXT,
`ad_html_raw` TEXT,
`ad_html` TEXT,
`weight` INT NOT NULL,
`raw_clicks` INT NOT NULL,
`unique_clicks` INT NOT NULL,
`times_displayed` INT NOT NULL,
`categories` TEXT,
`tags` TEXT,
FULLTEXT(`tags`),
FULLTEXT(`categories`)

=>[tx_iplog_ads]
`ad_id` INT NOT NULL,
`ip_address` INT NOT NULL,
`raw_clicks` INT NOT NULL,
`last_click` INT NOT NULL,
PRIMARY KEY(`ad_id`,`ip_address`)

=>[tx_ads_used]
`ad_id` INT NOT NULL,
`page_id` INT NOT NULL,
PRIMARY KEY(`ad_id`,`page_id`)

=>[tx_ads_used_page]
`ad_id` INT NOT NULL PRIMARY KEY
