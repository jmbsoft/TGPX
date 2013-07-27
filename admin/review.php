<?php
// Copyright 2011 JMB Software, Inc.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//    http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

define('TGPX', TRUE);

require_once('../includes/common.php');
require_once('../includes/validator.class.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/compiler.class.php");
require_once('includes/functions.php');

SetupRequest();

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

if( ($error = ValidLogin()) === TRUE )
{
    txShGalleryReview();
}
else
{
    echo "Invalid login";
}

$DB->Disconnect();

function txShGalleryReview()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY);

    include_once('includes/review-galleries.php');
}


?>
