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

define('ANNOTATION_PADDING', 3);
define('MAGICK_FILTERS', '-modulate 110,102,100 -sharpen 1x1 -enhance');

define('BIAS_CENTER', 0);
define('BIAS_TOP', 1);
define('BIAS_BOTTOM', 2);
define('BIAS_LEFT', 3);
define('BIAS_RIGHT', 4);

function GetImager()
{
    global $C;
    
    switch($C['imager'])
    {
        case 'gd':
            return new ImagerGD();
            
        case 'magick':
            return new ImagerMagick();
            
        default:
            return new Imager();
    }
}

class Imager
{
    var $orig_height;
    var $orig_width;
    var $new_width;
    var $new_height;

    function Imager() { }

    function PrepareImageInfo($file, $dimensions)
    {
        list($this->new_width, $this->new_height) = explode('x', $dimensions);
        
        $imagesize = @getimagesize($file);
        
        if( $imagesize === FALSE )
        {
            $this->orig_width = null;
            $this->orig_height = null;
        }
        else
        {
            $this->orig_width = $imagesize[0];
            $this->orig_height = $imagesize[1];
        }
    }

    function ResizeAuto($file, $dimensions, $annotation, $bias_land = BIAS_CENTER, $bias_port = BIAS_CENTER, $no_filters = FALSE)
    {
        // To be overriden by descendant
    }

    function ResizeCropper($file, $dimensions, $cropinfo, $annotation)
    {
        // To be overriden by descendant
    }

    function Annotate($file, $annotation)
    {
        // To be overriden by descendant
    }

    function ApplyFilter($file, $filter, $input)
    {
        // To be overriden by descendant
    }
}

class ImagerGD extends Imager
{

    function ImagerGD()
    {
    }

    function ResizeAuto($file, $dimensions, &$annotation, $bias_land = BIAS_CENTER, $bias_port = BIAS_CENTER, $no_filters = FALSE)
    {
        global $C;

        $this->PrepareImageInfo($file, $dimensions);
        
        // Check that image is not already the correct size
        if( $this->orig_width == $this->new_width && $this->orig_height == $this->new_height )
        {
            $this->Annotate($file, $annotation);
            return;
        }    
        
        $orig_ratio = $this->orig_width / $this->orig_height;
        $new_ratio = $this->new_width / $this->new_height;
        
        // Trim width
        if( $orig_ratio > $new_ratio )
        {
            $crop_width = round($this->orig_height * $new_ratio);
            $crop_height = $this->orig_height;
            $src_y = 0;
            
            switch( $bias_land )
            {
                case BIAS_LEFT:
                    $src_x = 0;
                    break;
                    
                case BIAS_RIGHT:
                    $src_x = $this->orig_width - $crop_width;
                    break;
                       
                default:
                    $src_x = round(($this->orig_width - $crop_width) / 2);
                    break;
            }
        }
        // Trim height
        else
        {
            $crop_width = $this->orig_width;
            $crop_height = round($this->orig_width * ($this->new_height / $this->new_width));
            $src_x = 0;
            
            switch( $bias_port )
            {
                case BIAS_TOP:
                    $src_y = 0;
                    break;
                    
                case BIAS_BOTTOM:
                    $src_y = $this->orig_height - $crop_height;
                    break;
                    
                default:
                    $src_y = round(($this->orig_height - $crop_height) / 2);
                    break;
            }
        }
        
        $img_src = @imagecreatefromjpeg($file);
        $img_dst = @imagecreatetruecolor($this->new_width, $this->new_height);
        
        // Resize and crop
        @imagecopyresampled($img_dst, $img_src, 0, 0, $src_x, $src_y, $this->new_width, $this->new_height, $crop_width, $crop_height);
        @imagedestroy($img_src);

        // Apply unsharp mask
        if( !$no_filters )
        {
            UnsharpMask($img_dst, 60, 0.5, 2);
        }

        // Apply the annotation        
        $this->DoAnnotate($img_dst, $annotation);
        
        // Save the image back to disk
        @imagejpeg($img_dst, $file, $C['compression']);
        @imagedestroy($img_dst);
    }

    function ResizeCropper($file, $dimensions, $cropinfo, &$annotation)
    {
        global $C;
        
        $this->PrepareImageInfo($file, $dimensions);
        
        $img_src = @imagecreatefromjpeg($file);
        $img_dst = @imagecreatetruecolor($this->new_width, $this->new_height);
        
        $img_height = imagesy($img_src);
        $img_width = imagesx($img_src);
        
        // Resize and crop
        @imagecopyresampled($img_dst, $img_src, 0, 0, round($cropinfo['x']), round($cropinfo['y']), $this->new_width, $this->new_height, min($img_width, $cropinfo['width']), min($img_height, $cropinfo['height']));
        @imagedestroy($img_src);
        
        // Apply unsharp mask
        UnsharpMask($img_dst, 60, 0.5, 2);
        
        // Apply the annotation        
        $this->DoAnnotate($img_dst, $annotation);
        
        // Save the image back to disk
        @imagejpeg($img_dst, $file, $C['compression']);
    }

    function Annotate($file, &$annotation)
    {
        global $C;
        
        $img = @imagecreatefromjpeg($file);        
        $this->DoAnnotate($img, $annotation);
        @imagejpeg($img, $file, $C['compression']);
        @imagedestroy($img);
    }

    function DoAnnotate($img, &$annotation)
    {
        if( !$annotation ) return;
        
        $img_width = imagesx($img);
        $img_height = imagesy($img);
            
        // Image annotation
        if( $annotation['type'] == 'image' )
        {
            $imagesize = @getimagesize("{$GLOBALS['BASE_DIR']}/annotations/{$annotation['image_file']}");            
            $img_ann = null;
            
            switch($imagesize[2])
            {
                case IMAGETYPE_JPEG:
                    $img_ann = @imagecreatefromjpeg("{$GLOBALS['BASE_DIR']}/annotations/{$annotation['image_file']}");
                    break;
                case IMAGETYPE_PNG:
                    $img_ann = @imagecreatefrompng("{$GLOBALS['BASE_DIR']}/annotations/{$annotation['image_file']}");
                    break;
                case IMAGETYPE_GIF:
                    $img_ann = @imagecreatefromgif("{$GLOBALS['BASE_DIR']}/annotations/{$annotation['image_file']}");
                    break;
            }
            
            if( !isset($img_ann) )
            {
                return;
            }
            
            // Select proper location
            switch($annotation['location'])
            {
                case 'NorthWest':
                    $dst_x = ANNOTATION_PADDING;
                    $dst_y = ANNOTATION_PADDING;
                    break;
                
                case 'North':
                    $dst_x = round(($img_width/2) - ($imagesize[0]/2));
                    $dst_y = ANNOTATION_PADDING;
                    break;
                
                case 'NorthEast':
                    $dst_x = $img_width - $imagesize[0] - ANNOTATION_PADDING;
                    $dst_y = ANNOTATION_PADDING;
                    break;
                
                case 'SouthWest':
                    $dst_x = ANNOTATION_PADDING;
                    $dst_y = $img_height - $imagesize[1] - ANNOTATION_PADDING;
                    break;
                
                case 'South':
                    $dst_x = round(($img_width/2) - ($imagesize[0]/2));
                    $dst_y = $img_height - $imagesize[1] - ANNOTATION_PADDING;
                    break;
                
                case 'SouthEast':
                    $dst_x = $img_width - $imagesize[0] - ANNOTATION_PADDING;
                    $dst_y = $img_height - $imagesize[1] - ANNOTATION_PADDING;
                    break;
            }
            
            @imagecopy($img, $img_ann, $dst_x, $dst_y, 0, 0, $imagesize[0], $imagesize[1]);   
        }
        
        // Text annotation
        else
        {
            if( !is_file("{$GLOBALS['BASE_DIR']}/annotations/{$annotation['font_file']}") )
            {
                return;
            }
            
            $box = @imagettfbbox($annotation['text_size'], 0, "{$GLOBALS['BASE_DIR']}/annotations/{$annotation['font_file']}", $annotation['string']);
                        
            $ttf_width = abs($box[4] - $box[0]);
            $ttf_height = abs($box[5] - $box[1]);
                                    
            // Select proper location
            switch($annotation['location'])
            {
                case 'NorthWest':
                    $dst_x = ANNOTATION_PADDING;
                    $dst_y = ANNOTATION_PADDING + $ttf_height - $box[1];
                    break;
                
                case 'North':
                    $dst_x = round(($img_width/2) - ($ttf_width/2));
                    $dst_y = ANNOTATION_PADDING + $ttf_height - $box[1];
                    break;
                
                case 'NorthEast':
                    $dst_x = $img_width - $ttf_width - ANNOTATION_PADDING;
                    $dst_y = ANNOTATION_PADDING + $ttf_height - $box[1];
                    break;
                
                case 'SouthWest':
                    $dst_x = ANNOTATION_PADDING;
                    $dst_y = $img_height - abs($box[1]) - ANNOTATION_PADDING;
                    break;
                
                case 'South':
                    $dst_x = round(($img_width/2) - ($ttf_width/2));
                    $dst_y = $img_height - abs($box[1]) - ANNOTATION_PADDING;
                    break;
                
                case 'SouthEast':
                    $dst_x = $img_width - $ttf_width - ANNOTATION_PADDING;
                    $dst_y = $img_height - abs($box[1]) - ANNOTATION_PADDING;
                    break;
            }
            
            
            // Prepare the text and shadow colors
            preg_match('~#([\dA-F]{2})([\dA-F]{2})([\dA-F]{2})~i', $annotation['text_color'], $textrgb);
            preg_match('~#([\dA-F]{2})([\dA-F]{2})([\dA-F]{2})~i', $annotation['shadow_color'], $shadowrgb);            
            $text_color = @imagecolorallocate($img, hexdec($textrgb[1]), hexdec($textrgb[2]), hexdec($textrgb[3]));
            $shadow_color = @imagecolorallocate($img, hexdec($shadowrgb[1]), hexdec($shadowrgb[2]), hexdec($shadowrgb[3]));
            
            // Draw the text and shadow on the image
            @imagettftext($img, $annotation['text_size'], 0, $dst_x+1, $dst_y+1, $shadow_color, "{$GLOBALS['BASE_DIR']}/annotations/{$annotation['font_file']}", $annotation['string']);
            @imagettftext($img, $annotation['text_size'], 0, $dst_x, $dst_y, $text_color, "{$GLOBALS['BASE_DIR']}/annotations/{$annotation['font_file']}", $annotation['string']);
        }
    }

    function ApplyFilter($file, $filter, $input)
    {
        global $DB, $C;
           
        switch($filter)
        {
            case 'annotation':
            {
                $C['compression'] = 100;
                $annotation = $DB->Row('SELECT * FROM `tx_annotations` WHERE `annotation_id`=?', array($input));
                $this->Annotate($file, $annotation);
            }
            break;
                
            case 'compress':
            {
                $img = @imagecreatefromjpeg($file);        
                @imagejpeg($img, $file, $C['compression']);
                @imagedestroy($img);
            }
            break;    
        }
    }
}

class ImagerMagick extends Imager
{

    function ImagerMagick()
    {
    }

    function ResizeAuto($file, $dimensions, $annotation, $bias_land = BIAS_CENTER, $bias_port = BIAS_CENTER, $no_filters = FALSE)
    {
        global $C;
        
        $this->PrepareImageInfo($file, $dimensions);
        $filters = !empty($C['magick_filters']) ? $C['magick_filters'] : MAGICK_FILTERS;
        
        if( $no_filters )
        {
            $filters = '';
        }
        
        if( $this->orig_height !== null && $this->orig_width !== null )
        {        
            // Check that image is not already the correct size
            if( $this->orig_width == $this->new_width && $this->orig_height == $this->new_height )
            {
                $this->Annotate($file, $annotation);
                return;
            }    

            $orig_ratio = $this->orig_width / $this->orig_height;
            $new_ratio = $this->new_width / $this->new_height;
            
            // Trim width
            if( $orig_ratio > $new_ratio )
            {
                $crop_width = round($this->orig_height * $new_ratio);
                $crop_height = $this->orig_height;            
                $src_y = 0;
                
                switch( $bias_land )
                {
                    case BIAS_LEFT:
                        $src_x = 0;
                        break;
                        
                    case BIAS_RIGHT:
                        $src_x = $this->orig_width - $crop_width;
                        break;
                           
                    default:
                        $src_x = round(($this->orig_width - $crop_width) / 2);
                        break;
                }
            }
            // Trim height
            else
            {
                $crop_width = $this->orig_width;
                $crop_height = round($this->orig_width * ($this->new_height / $this->new_width));
                $src_x = 0;
                
                switch( $bias_port )
                {
                    case BIAS_TOP:
                        $src_y = 0;
                        break;
                        
                    case BIAS_BOTTOM:
                        $src_y = $this->orig_height - $crop_height;
                        break;
                        
                    default:
                        $src_y = round(($this->orig_height - $crop_height) / 2);
                        break;
                }
                
            }
            
            shell_exec("{$C['convert']} " .
                 "$file " .
                 "-crop {$crop_width}x{$crop_height}+{$src_x}+{$src_y} " .
                 "-resize \"{$this->new_width}x{$this->new_height}!\" " .
                 ($C['magick6'] ? "-strip " : '') .
                 "-filter Blackman " .
                 "$filters " .
                 "+profile \"*\" " . 
                 "-format jpeg " .
                 "-quality {$C['compression']} " .
                 "$file");
           
            $this->DoAnnotate($file, $annotation, TRUE);
        }
    }

    function ResizeCropper($file, $dimensions, $cropinfo, $annotation)
    {
        global $C;
        
        $this->PrepareImageInfo($file, $dimensions);
        $filters = !empty($C['magick_filters']) ? $C['magick_filters'] : MAGICK_FILTERS;
        
        shell_exec("{$C['convert']} " .
                   "$file " .
                   "-crop {$cropinfo['width']}x{$cropinfo['height']}+{$cropinfo['x']}+{$cropinfo['y']} " .
                   "-resize \"{$this->new_width}x{$this->new_height}!\" " .
                   ($C['magick6'] ? "-strip " : '') .
                   "-filter Blackman " .
                   "$filters " .
                   "+profile \"*\" " . 
                   "-format jpeg " .
                   "-quality {$C['compression']} " .
                   "$file");
                           
        $this->DoAnnotate($file, $annotation, TRUE);
    }

    function Annotate($file, $annotation)
    {
        global $C;
              
        $this->DoAnnotate($file, $annotation, TRUE);
    }

    function DoAnnotate($file, &$annotation, $compress = FALSE)
    {
        global $C;
        
        if( !$annotation ) return;
                
        $quality = $compress ? $C['compression'] : 100;
        
        // Image annotation
        if( $annotation['type'] == 'image' )
        {   
            if( !is_file("{$GLOBALS['BASE_DIR']}/annotations/{$annotation['image_file']}") )
            {
                return;
            }
            
            shell_exec("{$C['composite']} " .
                 "{$GLOBALS['BASE_DIR']}/annotations/{$annotation['image_file']} " .
                 "$file " .
                 "-sampling-factor 1x1 " . 
                 "-gravity {$annotation['location']} " .
                 "-compose over " .
                 "-geometry +2+2 " .
                 "-quality $quality " .
                 "-format jpeg " .
                 "$file");
        }
        
        // Text annotation
        else
        {                             
            // Select proper location
            switch($annotation['location'])
            {
                case 'NorthWest':
                    $dst_x = $C['magick6'] ? 2 : 1;
                    $dst_y = $C['magick6'] ? -1 : -4;
                    $dst_x_shadow = $C['magick6'] ? 3 : 2;
                    $dst_y_shadow = $C['magick6'] ? 0 : -3;
                    break;
                
                case 'North':
                    $dst_x = $C['magick6'] ? 2 : 0;
                    $dst_y = $C['magick6'] ? -1 : -4;
                    $dst_x_shadow = $C['magick6'] ? 3 : 1;
                    $dst_y_shadow = $C['magick6'] ? 0 : -3;
                    break;
                
                case 'NorthEast':
                    $dst_x = $C['magick6'] ? 3 : 2;
                    $dst_y = $C['magick6'] ? -1 : -4;
                    $dst_x_shadow = $C['magick6'] ? 2 : 1;
                    $dst_y_shadow = $C['magick6'] ? 0 : -3;
                    break;
                
                case 'SouthWest':
                    $dst_x = $C['magick6'] ? 2 : 1;
                    $dst_y = $C['magick6'] ? 0 : 4;
                    $dst_x_shadow = $C['magick6'] ? 3 : 2;
                    $dst_y_shadow = $C['magick6'] ? -1 : 3;
                    break;
                
                case 'South':
                    $dst_x = $C['magick6'] ? 2 : 0;
                    $dst_y = $C['magick6'] ? 0 : 4;
                    $dst_x_shadow = $C['magick6'] ? 3 : 1;
                    $dst_y_shadow = $C['magick6'] ? -1 : 3;
                    break;
                
                case 'SouthEast':
                    $dst_x = $C['magick6'] ? 3 : 2;
                    $dst_y = $C['magick6'] ? 0 : 4;
                    $dst_x_shadow = $C['magick6'] ? 2 : 1;
                    $dst_y_shadow = $C['magick6'] ? -1 : 3;
                    break;
            }
            
            if( $MAGICK5 )
            {
                $dst_y_shadow += $annotation['text_size'];
                $dst_y += $annotation['text_size'];
            }

            if( !is_file("{$GLOBALS['BASE_DIR']}/annotations/{$annotation['font_file']}") )
            {
                return;
            }
            
            // Shadow
            shell_exec("{$C['convert']} " .
                 "$file " .
                 "-sampling-factor 1x1 " . 
                 "-font {$GLOBALS['BASE_DIR']}/annotations/{$annotation['font_file']} " .
                 "-pointsize {$annotation['text_size']} " .
                 "-fill '{$annotation['shadow_color']}' " .
                 "-draw 'gravity {$annotation['location']} text $dst_x_shadow,$dst_y_shadow \"{$annotation['string']}\"' " .
                 "-quality 100 " .
                 "-format jpeg " .
                 "$file");

            // Text
            shell_exec("{$C['convert']} " .
                 "$file " .                
                 "-sampling-factor 1x1 " . 
                 "-font {$GLOBALS['BASE_DIR']}/annotations/{$annotation['font_file']} " .
                 "-pointsize {$annotation['text_size']} " .
                 "-fill '{$annotation['text_color']}' " .
                 "-draw 'gravity {$annotation['location']} text $dst_x,$dst_y \"{$annotation['string']}\"' " .
                 "-quality $quality " .
                 "-format jpeg " .
                 "$file");
        }
    }

    function ApplyFilter($file, $filter, $input)
    {
        global $DB, $C;
                
        switch($filter)
        {
            case 'annotation':
            {
                $C['compression'] = 100;
                $annotation = $DB->Row('SELECT * FROM `tx_annotations` WHERE `annotation_id`=?', array($input));
                $this->Annotate($file, $annotation);
            }
            break;
            
            case 'sharpen':
            {
                shell_exec("{$C['convert']} " . 
                           "-compress JPEG " .
                           "-quality 100 " .
                           "-sampling-factor 1x1 " .                 
                           "-sharpen $input " .
                           ($C['magick6'] ? "-strip " : '') .
                           "$file " .
                           "$file 2>&1");
            }
            break;
            
            case 'contrast-':
            {
                shell_exec("{$C['convert']} " . 
                           "-compress JPEG " .
                           "-quality 100 " .
                           "-sampling-factor 1x1 " .                 
                           "+contrast " .
                           ($C['magick6'] ? "-strip " : '') .
                           "$file " .
                           "$file 2>&1");
            }
            break;
            
            case 'contrast':
            {
                shell_exec("{$C['convert']} " . 
                           "-compress JPEG " .
                           "-quality 100 " .
                           "-sampling-factor 1x1 " .                 
                           "-contrast " .
                           ($C['magick6'] ? "-strip " : '') .
                           "$file " .
                           "$file 2>&1");
            }
            break;
            
            case 'modulate':
            {
                shell_exec("{$C['convert']} " . 
                           "-compress JPEG " .
                           "-quality 100 " .
                           "-sampling-factor 1x1 " .                 
                           "-modulate $input " .
                           ($C['magick6'] ? "-strip " : '') .
                           "$file " .
                           "$file 2>&1");
            }
            break;
            
            case 'normalize':
            {
                shell_exec("{$C['convert']} " . 
                           "-compress JPEG " .
                           "-quality 100 " .
                           "-sampling-factor 1x1 " .                 
                           "-normalize " .
                           ($C['magick6'] ? "-strip " : '') .
                           "$file " .
                           "$file 2>&1");
            }
            break;
            
            case 'enhance':
            {
                shell_exec("{$C['convert']} " . 
                           "-compress JPEG " .
                           "-quality 100 " .
                           "-sampling-factor 1x1 " .                 
                           "-enhance " .
                           ($C['magick6'] ? "-strip " : '') .
                           "$file " .
                           "$file 2>&1");
            }
            break;
            
            case 'compress':
            {
                shell_exec("{$C['convert']} " . 
                           "-compress JPEG " .
                           "-quality {$C['compression']} " .
                           "-sampling-factor 1x1 " .                 
                           ($C['magick6'] ? "-strip " : '') .
                           "$file " .
                           "$file 2>&1");
            }
            break;
        }
    }
}





function UnsharpMask($img, $amount, $radius, $threshold)
{
    ////////////////////////////////////////////////////////////////////////////////////////////////  
    ////  
    ////                  Unsharp Mask for PHP - version 2.1  
    ////  
    ////    Unsharp mask algorithm by Torstein Hønsi 2003-06.  
    ////             thoensi_at_netcom_dot_no.  
    ////               Please leave this notice.  
    ////  
    ///////////////////////////////////////////////////////////////////////////////////////////////  

    // $img is an image that is already created within php using 
    // imgcreatetruecolor. No url! $img must be a truecolor image. 

    // Attempt to calibrate the parameters to Photoshop: 
    if ($amount > 500) $amount = 500;            
    $amount = $amount * 0.016; 
    if ($radius > 50) $radius = 50; 
    $radius = $radius * 2; 
    if ($threshold > 255) $threshold = 255; 
     
    $radius = abs(round($radius));     // Only integers make sense. 
    if( $radius == 0 )
    { 
        return $img; 
        imagedestroy($img); 
        break;
    } 
    
    $w = imagesx($img); 
    $h = imagesy($img); 
    $imgCanvas = imagecreatetruecolor($w, $h); 
    $imgBlur = imagecreatetruecolor($w, $h); 
     
    // Gaussian blur matrix: 
    //                         
    //    1    2    1         
    //    2    4    2         
    //    1    2    1         
    //                         
    ////////////////////////////////////////////////// 
         
    
    // PHP >= 5.1  
    if( function_exists('imageconvolution') )
    { 
        $matrix = array(array( 1, 2, 1 ),  
                        array( 2, 4, 2 ),  
                        array( 1, 2, 1 ));
        imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h); 
        imageconvolution($imgBlur, $matrix, 16, 0);  
    }  
    else
    {  
        // Move copies of the image around one pixel at the time and merge them with weight 
        // according to the matrix. The same matrix is simply repeated for higher radii. 
        for( $i = 0; $i < $radius; $i++ )
        { 
            imagecopy($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left 
            imagecopymerge($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right 
            imagecopymerge($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center 
            imagecopy($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h); 

            imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333 ); // up 
            imagecopymerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down 
        } 
    } 

    if( $threshold > 0 )
    { 
        // Calculate the difference between the blurred pixels and the original 
        // and set the pixels 
        for( $x = 0; $x < $w; $x++ )
        { // each row 
            for( $y = 0; $y < $h; $y++ )
            { // each pixel 
                     
                $rgbOrig = imagecolorat($img, $x, $y); 
                $rOrig = (($rgbOrig >> 16) & 0xFF); 
                $gOrig = (($rgbOrig >> 8) & 0xFF); 
                $bOrig = ($rgbOrig & 0xFF); 
                 
                $rgbBlur = imagecolorat($imgBlur, $x, $y); 
                 
                $rBlur = (($rgbBlur >> 16) & 0xFF); 
                $gBlur = (($rgbBlur >> 8) & 0xFF); 
                $bBlur = ($rgbBlur & 0xFF); 
                 
                // When the masked pixels differ less from the original 
                // than the threshold specifies, they are set to their original value. 
                $rNew = (abs($rOrig - $rBlur) >= $threshold)  
                    ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))  
                    : $rOrig; 
                $gNew = (abs($gOrig - $gBlur) >= $threshold)  
                    ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))  
                    : $gOrig; 
                $bNew = (abs($bOrig - $bBlur) >= $threshold)  
                    ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))  
                    : $bOrig; 
                 
                 
                             
                if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) { 
                        $pixCol = imagecolorallocate($img, $rNew, $gNew, $bNew); 
                        imagesetpixel($img, $x, $y, $pixCol); 
                    } 
            } 
        } 
    } 
    else
    { 
        for( $x = 0; $x < $w; $x++ )
        { // each row 
            for( $y = 0; $y < $h; $y++ )
            { // each pixel 
                $rgbOrig = imagecolorat($img, $x, $y); 
                $rOrig = (($rgbOrig >> 16) & 0xFF); 
                $gOrig = (($rgbOrig >> 8) & 0xFF); 
                $bOrig = ($rgbOrig & 0xFF); 
                 
                $rgbBlur = imagecolorat($imgBlur, $x, $y); 
                 
                $rBlur = (($rgbBlur >> 16) & 0xFF); 
                $gBlur = (($rgbBlur >> 8) & 0xFF); 
                $bBlur = ($rgbBlur & 0xFF); 
                 
                $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig; 
                    if($rNew>255){$rNew=255;} 
                    elseif($rNew<0){$rNew=0;} 
                $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig; 
                    if($gNew>255){$gNew=255;} 
                    elseif($gNew<0){$gNew=0;} 
                $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig; 
                    if($bNew>255){$bNew=255;} 
                    elseif($bNew<0){$bNew=0;} 
                $rgbNew = ($rNew << 16) + ($gNew <<8) + $bNew; 
                    imagesetpixel($img, $x, $y, $rgbNew); 
            } 
        } 
    } 
    imagedestroy($imgCanvas); 
    imagedestroy($imgBlur); 
     
    return $img;
}

?>
