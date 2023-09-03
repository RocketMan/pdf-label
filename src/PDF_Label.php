<?php
//////////////////////////////////////////////////////////////////////////////
// PDF_Label 
//
// tFPDF-based label maker
//
// Create labels in Avery or custom formats with support for unicode and ttf
//
// Copyright (C) 2021-2023 Jim Mason
// Copyright (C) 2003 Laurent PASSEBECQ (LPA)
// Based on code by Steve Dillon
//
// Forked from:
// http://www.fpdf.org/en/script/script29.php
//
// License: https://www.gnu.org/licenses/lgpl-3.0.html
//
//----------------------------------------------------------------------------
// VERSIONS:
// 1.0: Initial release
// 1.1: + Added unit in the constructor
//      + Now Positions start at (1,1).. then the first label at top-left of
//        a page is (1,1)
//      + Added in the description of a label:
//           font-size : defaut char size (can be changed by calling
//                       Set_Char_Size(xx);
//           paper-size: Size of the paper for this sheet (thanx to Al Canton)
//           metric    : type of unit used in this description
//                       You can define your label properties in inches by
//                       setting metric to 'in' and print in millimiters
//                       by setting unit to 'mm' in constructor
//      + Added some formats:
//           5160, 5161, 5162, 5163, 5164: thanks to Al Canton
//           8600                        : thanks to Kunal Walia
//      + Added 3mm to the position of labels to avoid errors 
// 1.2: = Bug of positioning
//      = Set_Font_Size modified -> Now, just modify the size of the font
// 1.3: + Labels are now printed horizontally
//      = 'in' as document unit didn't work
// 1.4: + Page scaling is disabled in printing options
// 1.5: + Added 3422 format
// 1.6: + FPDF 1.8 compatibility
// 1.6+rocketman.1:
//      + Migrated from FPDF to tFPDF (for unicode and ttf)
//      + Added optional orientation property to format descriptor
// 1.6+rocketman.2:
//      + Added new methods `currentLabel` and `writeQRCode`
//////////////////////////////////////////////////////////////////////////////

/**
 * PDF_Label - PDF label maker with support for unicode and ttf
 * @author Jim Mason
 * @copyright 2021-2023 Jim Mason
 * @author Laurent PASSEBECQ
 * @copyright 2003 Laurent PASSEBECQ
 * @license https://www.gnu.org/licenses/lgpl-3.0.html
 */
class PDF_Label extends tFPDF {
    // Private properties
    protected $_Margin_Left;        // Left margin of labels
    protected $_Margin_Top;         // Top margin of labels
    protected $_X_Space;            // Horizontal space between 2 labels
    protected $_Y_Space;            // Vertical space between 2 labels
    protected $_X_Number;           // Number of labels horizontally
    protected $_Y_Number;           // Number of labels vertically
    protected $_Width;              // Width of label
    protected $_Height;             // Height of label
    protected $_Line_Height;        // Line height
    protected $_Padding;            // Padding
    protected $_Metric_Doc;         // Type of metric for the document
    protected $_COUNTX;             // Current x position
    protected $_COUNTY;             // Current y position
    protected $_PosX;               // tFPDF x origin for current label
    protected $_PoxY;               // tFPDF y origin for current label

    // List of label formats
    protected $_Avery_Labels = array(
        '5160' => array('paper-size'=>'letter', 'metric'=>'mm',
                        'marginLeft'=>1.762, 'marginTop'=>10.7,
                        'NX'=>3, 'NY'=>10,
                        'SpaceX'=>3.175, 'SpaceY'=>0,
                        'width'=>66.675, 'height'=>25.4, 'font-size'=>8),
        '5161' => array('paper-size'=>'letter', 'metric'=>'mm',
                        'marginLeft'=>8,/*0.967,*/ 'marginTop'=>10.7,
                        'NX'=>2, 'NY'=>10,
                        'SpaceX'=>3.967, 'SpaceY'=>0,
                        'width'=>101.6, 'height'=>25.4, 'font-size'=>8),
        '5162' => array('paper-size'=>'letter', 'metric'=>'mm',
                        'marginLeft'=>0.97, 'marginTop'=>20.224,
                        'NX'=>2, 'NY'=>7,
                        'SpaceX'=>4.762, 'SpaceY'=>0,
                        'width'=>100.807, 'height'=>35.72, 'font-size'=>8),
        '5163' => array('paper-size'=>'letter', 'metric'=>'mm',
                        'marginLeft'=>1.762, 'marginTop'=>10.7,
                        'NX'=>2, 'NY'=>5,
                        'SpaceX'=>3.175, 'SpaceY'=>0,
                        'width'=>101.6, 'height'=>50.8, 'font-size'=>8),
        '5164' => array('paper-size'=>'letter', 'metric'=>'in',
                        'marginLeft'=>0.148, 'marginTop'=>0.5,
                        'NX'=>2, 'NY'=>3,
                        'SpaceX'=>0.2031, 'SpaceY'=>0,
                        'width'=>4.0, 'height'=>3.33, 'font-size'=>12),
        '8600' => array('paper-size'=>'letter', 'metric'=>'mm',
                        'marginLeft'=>7.1, 'marginTop'=>19,
                        'NX'=>3, 'NY'=>10,
                        'SpaceX'=>9.5, 'SpaceY'=>3.1,
                        'width'=>66.6, 'height'=>25.4, 'font-size'=>8),
        'L7163'=> array('paper-size'=>'A4', 'metric'=>'mm',
                        'marginLeft'=>5, 'marginTop'=>15,
                        'NX'=>2, 'NY'=>7,
                        'SpaceX'=>25, 'SpaceY'=>0,
                        'width'=>99.1, 'height'=>38.1,
                        'font-size'=>9),
        '3422' => array('paper-size'=>'A4', 'metric'=>'mm',
                        'marginLeft'=>0, 'marginTop'=>8.5,
                        'NX'=>3, 'NY'=>8,
                        'SpaceX'=>0, 'SpaceY'=>0,
                        'width'=>70, 'height'=>35, 'font-size'=>9)
    );

    // Constructor
    function __construct($format, $unit='mm', $posX=1, $posY=1) {
        if (is_array($format)) {
            // Custom format
            $Tformat = $format;
        } else {
            // Built-in format
            if (!isset($this->_Avery_Labels[$format]))
                $this->Error('Unknown label format: '.$format);
            $Tformat = $this->_Avery_Labels[$format];
        }

        $orientation = isset($Tformat['orientation'])?
            $Tformat['orientation']:'P';

        parent::__construct($orientation, $unit, $Tformat['paper-size']);
        $this->_Metric_Doc = $unit;
        $this->_Set_Format($Tformat);
        $this->SetFont('Arial');
        $this->SetMargins(0,0); 
        $this->SetAutoPageBreak(false); 
        $this->_COUNTX = $posX-2;
        $this->_COUNTY = $posY-1;
    }

    function _Set_Format($format) {
        $this->_Margin_Left = $this->_Convert_Metric($format['marginLeft'], $format['metric']);
        $this->_Margin_Top  = $this->_Convert_Metric($format['marginTop'], $format['metric']);
        $this->_X_Space     = $this->_Convert_Metric($format['SpaceX'], $format['metric']);
        $this->_Y_Space     = $this->_Convert_Metric($format['SpaceY'], $format['metric']);
        $this->_X_Number    = $format['NX'];
        $this->_Y_Number    = $format['NY'];
        $this->_Width       = $this->_Convert_Metric($format['width'], $format['metric']);
        $this->_Height      = $this->_Convert_Metric($format['height'], $format['metric']);
        $this->Set_Font_Size($format['font-size']);
        $this->_Padding     = $this->_Convert_Metric(3, 'mm');
    }

    // convert units (in to mm, mm to in)
    // $src must be 'in' or 'mm'
    function _Convert_Metric($value, $src) {
        $dest = $this->_Metric_Doc;
        if ($src != $dest) {
            $a['in'] = 39.37008;
            $a['mm'] = 1000;
            return $value * $a[$dest] / $a[$src];
        } else {
            return $value;
        }
    }

    // Give the line height for a given font size
    function _Get_Height_Chars($pt) {
        $a = array(6=>2, 7=>2.5, 8=>3, 9=>4, 10=>5, 11=>6, 12=>7, 13=>8, 14=>9, 15=>10);
        if (!isset($a[$pt]))
            $this->Error('Invalid font size: '.$pt);
        return $this->_Convert_Metric($a[$pt], 'mm');
    }

    // Set the character size
    // This changes the line height too
    function Set_Font_Size($pt) {
        $this->_Line_Height = $this->_Get_Height_Chars($pt);
        $this->SetFontSize($pt);
    }

    // Print a label
    function Add_Label($text) {
        $this->_COUNTX++;
        if ($this->_COUNTX == $this->_X_Number) {
            // Row full, we start a new one
            $this->_COUNTX=0;
            $this->_COUNTY++;
            if ($this->_COUNTY == $this->_Y_Number) {
                // End of page reached, we start a new one
                $this->_COUNTY=0;
                $this->AddPage();
            }
        }

        $this->_PosX = $this->_Margin_Left + $this->_COUNTX*($this->_Width+$this->_X_Space) + $this->_Padding;
        $this->_PosY = $this->_Margin_Top + $this->_COUNTY*($this->_Height+$this->_Y_Space) + $this->_Padding;
        $this->SetXY($this->_PosX, $this->_PosY);
        $this->MultiCell($this->_Width - $this->_Padding, $this->_Line_Height, $text, 0, 'L');
    }

    function currentLabel($text, $align = 'L') {
        $this->SetXY($this->_PosX, $this->_PosY);
        $this->MultiCell($this->_Width - $this->_Padding, $this->_Line_Height, $text, 0, $align);
    }

    function writeQRCode($text, $align = 'L', $eclevel = 'L') {
        $qrcode = new QRcode($text, $eclevel);
        $arrcode = $qrcode->getBarcodeArray();
        $rows = $arrcode['num_rows'] ?? 0;
        $cols = $arrcode['num_cols'] ?? 0;
        if ($rows == 0 || $cols == 0) {
            error_log("writeQRCode failed for text: $text");
            return;
        }

        $mw = $mh = 1;     // scale factor
        $hpad = $vpad = 0; // padding

        $w = ($cols + $hpad) * ($mw / $this->k);
        $h = ($rows + $vpad) * ($mh / $this->k);
        $bw = ($w * $cols) / ($cols + $hpad);
        $bh = ($h * $rows) / ($rows + $vpad);
        $cw = $bw / $cols;
        $ch = $bh / $rows;

        switch($align) {
        case 'L':
            $xpos = $this->_PosX;
            break;
        case 'C':
            $xpos = $this->_PosX + ($this->_Width - $this->_Padding * 2 - $this->_Margin_Left * 2 - $w) / 2;
            break;
        case 'R':
            $xpos = $this->_PosX + $this->_Width - $this->_Padding * 2 - $this->_Margin_Left * 2 - $w;
            break;
        default:
            $xpos = $this->x;
            break;
        }

        $xstart = $xpos;
        $ystart = $this->_PosY - 1;

        $this->SetXY($this->_PosX, $this->_PosY);

        for ($r = 0; $r < $rows; $r++) {
            $xr = $xstart;
            // for each column
            for ($c = 0; $c < $cols; $c++) {
                if ($arrcode['bcode'][$r][$c] == 1) {
                    // draw a single barcode cell
                    $this->Rect($xr, $ystart, $cw, $ch, 'F');
                }
                $xr += $cw;
            }
            $ystart += $ch;
        }
    }

    function _putcatalog() {
        parent::_putcatalog();
        // Disable the page scaling option in the printing dialog
        $this->_put('/ViewerPreferences <</PrintScaling /None>>');
    }
}
