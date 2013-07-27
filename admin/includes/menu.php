<div style="background-image: url(images/logo-bg.png); clear: left;">
  <div style="float: left"><a href="index.php"><img src="images/logo.png" border="0" alt="TGPX"></a></div>
  <div id="logout">
  <a href="index.php?r=txLogOut" onclick="return confirm('Are you sure you want to log out?')"><img src="images/logout.png" border="0" alg="Log Out"></a>
  </div>
  <div style="clear: both;"></div>
</div>

<div id="menu">
  <span class="topMenu">
    <a class="topMenuItem">Galleries</a>
    <div class="subMenu">
      <a href="index.php?r=txShGallerySearch">Search Galleries</a>
      <a href="" onclick="return openGalleryReview()">Review New Galleries</a>
      <a href="index.php?r=txShGalleryScanner">Gallery Scanner</a>
      <a href="index.php?r=txShGalleryAdd" class="window {title: 'Add Gallery'}">Add Gallery</a>
      <a href="index.php?r=txShGalleryImport">Import From File</a>
      <a href="index.php?r=txShRssFeeds">Import From RSS</a>
      <a href="index.php?r=txShGalleryBreakdown">Gallery Breakdown</a>
      <a href="index.php?r=txShGalleryStats">Gallery Statistics</a>
      <a href="index.php?r=txFetchEmails">Download E-mail Log</a>
    </div>
  </span>

  <span class="topMenu">
    <a class="topMenuItem">TGP Pages</a>
    <div class="subMenu">
      <a href="index.php?r=txShPages">Manage Pages</a>
      <a href="index.php?r=txShPageTemplates">Edit Templates</a>
      <a href="index.php?r=txShPagesRecompile" class="window {title: 'Recompile TGP Templates', height: 300}">Recompile TGP Templates</a>
      <a href="index.php?r=txShPagesTest" class="window {title: 'Test Page Permissions'}">Test Page Permissions</a>
      <a href="index.php?r=txShBuildAllNew" class="window {title: 'Build All With New'}">Build All With New</a>
      <a href="index.php?r=txShBuildAll" class="window {title: 'Build All'}">Build All</a>
      <a href="index.php?r=txShBuildHistory">Build History</a>
    </div>
  </span>

  <span class="topMenu">
    <a class="topMenuItem">Templates</a>
    <div class="subMenu">
      <a href="index.php?r=txShScriptTemplates">Script Pages</a>
      <a href="index.php?r=txShEmailTemplates">E-mail Messages</a>
      <a href="index.php?r=txShRejectionTemplates">Rejection E-mails</a>
      <a href="index.php?r=txShLanguageFile">Language File</a>
    </div>
  </span>

  <span class="topMenu">
    <a class="topMenuItem">To Do</a>
    <div class="subMenu">
      <a href="" onclick="return openGalleryReview()">Review New Galleries</a>
      <a href="index.php?r=txShCheatReports">Review Cheat Reports</a>
      <a href="index.php?r=txShPartnerReview">Review Partner Account Requests</a>
    </div>
  </span>

  <span class="topMenu">
    <a class="topMenuItem">Database</a>
    <div class="subMenu">
      <a href="index.php?r=txShDatabaseTools">Tools</a>
      <a href="index.php?r=txShGalleryFields">User Defined Gallery Fields</a>
      <a href="index.php?r=txShPartnerFields">User Defined Partner Fields</a>
    </div>
  </span>

  <span class="topMenu">
    <a class="topMenuItem">Partners</a>
    <div class="subMenu">
      <a href="index.php?r=txShPartnerSearch">Manage Partners</a>
      <a href="index.php?r=txShPartnerReview">Review Partner Requests</a>
      <a href="index.php?r=txShPartnerAdd" class="window {title: 'Add Partner Account'}">Add Partner</a>
    </div>
  </span>

  <span class="topMenu">
    <a class="topMenuItem">Settings</a>
    <div class="subMenu">
      <a href="index.php?r=txShGeneralSettings" class="window {title: 'General Settings'}" id="_menu_gs">General Settings</a>
      <a href="index.php?r=txShSearchTerms">Search Terms</a>

      <a href="index.php?r=txShDomains">Manage Domains</a>

      <a href="index.php?r=txShAds">Manage Advertisements</a>
      <a href="index.php?r=txShBlacklist">Manage Blacklist</a>
      <a href="index.php?r=txShWhitelist">Manage Whitelist</a>
      <a href="index.php?r=txShReciprocals">Manage Recip Links</a>
      <a href="index.php?r=txSh2257">Manage 2257 Code</a>
      <a href="index.php?r=txShSponsors">Manage Sponsors</a>
      <a href="index.php?r=txShCategories">Manage Categories</a>
      <a href="index.php?r=txShAnnotations">Manage Annotations</a>
      <a href="index.php?r=txShIcons">Manage Icons</a>
      <a href="index.php?r=txShAdministrators">Manage Administrators</a>
      <a href="index.php?r=txShPhpinfo">phpinfo() Function</a>
    </div>
  </span>
</div>

<?php if( empty($C['from_email']) ): ?>
<script language="JavaScript">
$(function() { $('#_menu_gs').trigger('click'); });
</script>
<?php endif; ?>

<?php if( file_exists("{$GLOBALS['BASE_DIR']}/admin/reset-access.php") ): ?>
<div class="alert centered">
  SECURITY RISK: Please remove the reset-access.php file from the admin directory of your TGPX installation immediately
</div>
<?php endif; ?>

<?php if( file_exists("{$GLOBALS['BASE_DIR']}/admin/install.php") ): ?>
<div class="alert centered">
  SECURITY RISK: Please remove the install.php file from the admin directory of your TGPX installation immediately
</div>
<?php endif; ?>

<?php if( file_exists("{$GLOBALS['BASE_DIR']}/admin/mysql-change.php") ): ?>
<div class="alert centered">
  SECURITY RISK: Please remove the mysql-change.php file from the admin directory of your TGPX installation immediately
</div>
<?php endif; ?>

