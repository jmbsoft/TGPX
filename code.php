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

// If you are using this script on one of the domains you have defined in the
// Manage Domains interface, uncomment the following line and set the directory
// path to your TGPX Server Edition installation
//chdir('/full/path/to/tgpxse/install');


// The minimum and maximum sizes to use for each rendered TTF font
$min_font_size = 14;
$max_font_size = 18;


// The size of the drop shadow behind each character
$shadow_size = 2;


// The characters that will be selected from for the code
$allowed_chars = array('A', 'B', 'C', 'D', 'E', 'F', 'H', 'J', 'K', 'M', 'N', 'P', 'Q', 'R', 'T', 'U', 'V', 'W', 'X', 'Y', '3', '4', '6', '7', '8', '9');


//////////////////////////////
//  DO NOT EDIT BELOW HERE  //
//////////////////////////////

header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

require_once('includes/common.php');
require_once('includes/mysql.class.php');

// See if we are running in test mode
if( stristr($_SERVER['QUERY_STRING'], '.ttf') )
{
    DisplayTest();
}


switch($_GET['c'])
{   
    default:
        $cookie = 'tgpxcaptcha';
        break;
}

$image = null;
$padding_x = 12;
$padding_y = 12;
$padding_chars = 5;
$gdinfo = gd_info();
$verification = ($gdinfo['FreeType Support'] && HaveFonts() ? new TTFVerificationCode() : new VerificationCode());
$verification->Create();


$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

$C['cookie_domain'] = preg_replace('~^www\.~i', '', strtolower($_SERVER['HTTP_HOST']));


// Insert code into database and set session cookie
$session = sha1(uniqid(rand(), true));
setcookie($cookie, $session, time() + 86400, '/', $C['cookie_domain']);

$DB->Update('DELETE FROM `tx_captcha` WHERE `time_stamp` < ?', array(time() - 3600));
$DB->Update("INSERT INTO `tx_captcha` VALUES (?, ?, ?)", array($session, $verification->code_as_string, time()));
$DB->Disconnect();


// Ouput the image
if( $_SERVER['REQUEST_METHOD'] )
{    
    if( $gdinfo['PNG Support'] )
    {
        header("Content-type: image/png");
        imagepng($verification->image);
    }
    else
    {
        header("Content-type: image/jpeg");
        imagejpeg($verification->image);
    }
}






// Class to create verification codes with TTF fonts
class TTFVerificationCode
{
    var $fonts;
    var $image_width;
    var $image_height;
    var $image;
    var $bg_dark;
    var $bg_light;
    var $code;
    var $code_as_string;
    
    function TTFVerificationCode()
    {
    }
    
    function Create()
    {
        global $C;
        
        $this->fonts =& DirRead($C['font_dir'], '\.ttf$');
        $this->code = GenerateCode();        
        
        list($this->image_width, $this->image_height) = $this->CalculateImageDimensions();

        // Create the image
        $this->image = imagecreatetruecolor($this->image_width, $this->image_height);

        // Fill the background with a gradient
        list($this->bg_dark, $this->bg_light) = FillBackground($this->image);

        // Add the code to the image
        $this->AddText();
    }
    
    function AddText()
    {
        global $padding_x, $padding_y, $shadow_size, $padding_chars;
        
        $start_x = intval($padding_x / 2);
        $current_x = $start_x;

        for($i = 0; $i < count($this->code); $i++)
        {
            $character = $this->code[$i];
            $x = $character['X'] + $current_x;
            $y = $character['Y'] + intval($padding_y / 2);

            // Draw the shadow
            for($b = 0; $b <= $shadow_size; $b++)
            {
                imagettftext($this->image, $character['FontSize'], $character['Angle'], $x++, $y++, $this->bg_dark, $character['Font'], $character['Character']);
            }
            
            ## Draw the text on top of the shadow
            imagettftext($this->image, $character['FontSize'], $character['Angle'], $x++, $y++, $this->bg_light, $character['Font'], $character['Character']);

            $current_x += $character['Width'] + $padding_chars;
        }
    }
    
    
    // Determine the image dimensions to use based on the selected fonts
    function CalculateImageDimensions()
    {
        global $padding_x, $padding_chars, $padding_y;
        
        $required_width = $padding_x;
        $required_height = null;

        foreach( $this->code as $character )
        {
            $required_width += $character['Width'] + $padding_chars;
            
            if( $character['Height'] + $padding_y > $required_height )
            {
                $required_height = $character['Height'] + $padding_y;
            }
        }

        return array($required_width, $required_height);
    }
    
    // Select the next character to use in the code
    function GetCharacter($selected = null)
    {
        global $min_font_size, $max_font_size, $fonts, $allowed_chars, $C;

        if( $selected == null )
        {
            $selected = $allowed_chars[array_rand($allowed_chars)];
        }
        
        $angle = RandomAngle();
        $font_size = rand($min_font_size, $max_font_size);
        $font = $this->fonts[array_rand($this->fonts)];
        $bounds = imagettfbbox($font_size, $angle,  "{$C['font_dir']}/$font", $selected);
        $hash = array();

        $this->code_as_string .= $selected;
        $hash['Character'] = $selected;
        $hash['Angle'] = $angle;
        $hash['Font'] = "{$C['font_dir']}/$font";
        $hash['FontSize'] = $font_size;

        if( $angle > 0 )
        {
            $hash['Height'] = abs($bounds[5]);
            $hash['Width'] = abs($bounds[6]) + $bounds[2];
            $hash['X'] = abs($bounds[6]);
            $hash['Y'] = $hash['Height'];
        }
        else
        {
            $hash['Height'] = abs($bounds[7]) + $bounds[3];
            $hash['Width'] = $bounds[4];
            $hash['X'] = 0;
            $hash['Y'] = $hash['Height'] - $bounds[3];
        }

        return $hash;
    }
}



// Class to create verification codes with built in GD font
class VerificationCode
{
    var $fonts;
    var $image_width;
    var $image_height;
    var $image;
    var $bg_dark;
    var $bg_light;
    var $code;
    var $code_as_string;
    
    function VerificationCode()
    {
    }
    
    function Create()
    {
        $this->code = GenerateCode();
        list($this->image_width, $this->image_height) = $this->CalculateImageDimensions($this->code);

        // Create the image
        $this->image = imagecreatetruecolor($this->image_width, $this->image_height);

        // Fill the background with a gradient
        list($this->bg_dark, $this->bg_light) = FillBackground($this->image);

        // Add the code to the image
        $this->AddText();
    }
    
    function AddText()
    {
        global $padding_x, $padding_y, $padding_chars;
        
        $x = intval($padding_x / 2);

        for($i = 0; $i < count($this->code); $i++)
        {
            $character = $this->code[$i];
            $y = imagefontheight(5) / 2;
            
            // Draw the text
            imagechar($this->image, 5, $x, $y, $character, $this->bg_dark);

            $x += imagefontwidth(5) + $padding_chars;
        }
    }
    
    
    // Determine the image dimensions to use based on the selected fonts
    function CalculateImageDimensions()
    {
        global $padding_x, $padding_chars, $padding_y;
        
        $required_height = imagefontheight(5) + $padding_y;
        $required_width = $padding_x + (imagefontwidth(5) + $padding_chars) * count($this->code) - $padding_chars;

        return array($required_width, $required_height);
    }
    
    
    // Select the next character to use in the code
    function GetCharacter($selected = null)
    {
        global $allowed_chars;

        if( $selected == null )
        {
            $selected = $allowed_chars[array_rand($allowed_chars)];
        }

        $this->code_as_string .= $selected;

        return $selected;
    }
}

function FillBackground(&$image)
{
    // Determine the starting color
    $red = rand(50, 150);
    $green = rand(50, 150);
    $blue = rand(50, 150);

    // Determine the dimensions of the image
    $width = imagesx($image);
    $height = imagesy($image);

    // Get the total number of different colored lines
    $lines = intval($height / 2) + 1;

    // Find the lowest starting color value from RGB
    $smallest_rgb = min($red, $green, $blue);

    // Divide that by the number of lines to figure the deviation on each line change
    $color_deviation = intval(($smallest_rgb / $lines) * 2);
    
    if( $color_deviation < 1 )
    {
        $color_deviation = 4;
    }

    // Calculate the largest RGB value allowed
    $max_color = 256 - $color_deviation;

    // Keep track of the darkest and lightest colors
    $darkest = null;
    $lightest = null;

    for($i = 0; $i < $lines; $i++)
    {
        $line_color = imagecolorallocate($image, $red, $green, $blue);

        // Draw line at the top of the image
        imageline($image, 0, $i, $width, $i, $line_color);

        // Draw line at the bottom of the image
        imageline($image, 0, $height - $i, $width, $height - $i, $line_color);

        // Modify the color values
        if( $red < $max_color && $green < $max_color && $blue < $max_color )
        {
            $red += $color_deviation;
            $green += $color_deviation;
            $blue += $color_deviation;
        }

        if( !$darkest )
        {
            $darkest = $line_color;
        }
        
        $lightest = $line_color;
    }

    return array($darkest, $lightest);
}

function GenerateCode()
{
    global $C, $verification;
       
    $code = array();

    if( $C['use_words'] )
    {
        $words = file("{$GLOBALS['BASE_DIR']}/includes/words");
        $word = strtoupper(trim($words[array_rand($words)]));

        for($i = 0; $i < strlen($word); $i++)
        {
            $code[] = $verification->GetCharacter($word{$i});
        }
    }
    else
    {        
        $length = rand($C['min_code_length'], $C['max_code_length']);    

        for($i = 1; $i <= $length; $i++ )
        {
            $code[] = $verification->GetCharacter();
        }        
    }
    
    return $code;
}

function RandomAngle()
{
    if( rand(0,1) )
    {
        return rand(0, 20);
    }
    else
    {
        return -rand(0, 20);
    }
}

function HaveFonts()
{
    global $C;
    
    if( count(DirRead($C['font_dir'], '\.ttf$')) )
    {
        return TRUE;
    }
    
    return FALSE;
}

function DisplayTest()
{
    global $allowed_chars, $C;
    
    $font_file = SafeFilename("{$C['font_dir']}/{$_SERVER['QUERY_STRING']}");
    $font_size = 24;
    $gdinfo = gd_info();
    $code = join(' ', $allowed_chars);
    $bounds = imagettfbbox($font_size, 0,  $font_file, $code);

    $image_height = $bounds[1] - $bounds[7] + 10;
    $image_width  = $bounds[2] + 10;

    // Create a new image
    $image = imagecreatetruecolor($image_width, $image_height);

    // Allocate the colors for the image
    $bg_color = imagecolorallocate($image, 240, 240, 220);
    $font_color = imagecolorallocate($image, 0, 0, 0);

    // Fill the image with the background color
    imagefill($image, 0, 0, $bg_color);

    // Write the submit code onto the image
    imagettftext($image, $font_size, 0, 5+$bounds[6], $image_height-$bounds[1]-5, $font_color, $font_file, $code);

    // Display the image
    if( $gdinfo['PNG Support'] )
    {
        header("Content-type: image/png");
        imagepng($image);
    }
    else
    {
        header("Content-type: image/jpeg");
        imagejpeg($image);
    }
}

?>