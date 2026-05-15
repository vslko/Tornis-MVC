<?php

final class ExifParser {

    // more information: http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/EXIF.html

    private static $_whitebalance = array(
        0	=> "Auto",
        1	=> "Daylight",
        2   => "Cloudy",
        3   => "Tungsten",
        4   => "Fluorescent",
        5   => "Flash",
        6   => "Custom",
        7   => "Black & White",
        8   => "Shade",
        9   => "Manual Temperature",
        10  => "PC Set", // PC Set1
        11  => "PC Set", // PC Set2
        12  => "PC Set", // PC Set3
        14  => "Daylight Fluorescent",
        15  => "Custom", // Custom 1,
        16  => "Custom", // Custom 2,
        17  => "Underwater",
        18  => "Custom", // Custom 3
        19  => "Custom", // Custom 4
        20  => "PC Set", // PC Set4
        21  => "PC Set", // PC Set5
        23  => "Auto (ambience priority)",
    );

    private static $_flash = array(
        0	    => false, // No Flash
        1       => true, // Fired
        5	    => true, // Fired, Return not detected
        7	    => true, // Fired, Return detected
        8	    => false, // On, Did not fire
        9	    => true, // On, Fired
        13	    => true, // On, Return not detected
        15	    => true, // On, Return detected
        16	    => false, // Off, Did not fire
        20	    => false, // Off, Did not fire, Return not detected
        24	    => false, // Auto, Did not fire
        25	    => true, // Auto, Fired
        29	    => true, // Auto, Fired, Return not detected
        31	    => true, // Auto, Fired, Return detected
        32	    => false, // No flash function
        48	    => false, // Off, No flash function
        65	    => true, // Fired, Red-eye reduction
        69	    => true, // Fired, Red-eye reduction, Return not detected
        71	    => true, // Fired, Red-eye reduction, Return detected
        73	    => true, // On, Red-eye reduction
        77	    => true, // On, Red-eye reduction, Return not detected
        79	    => true, // On, Red-eye reduction, Return detected
        80	    => false, // Off, Red-eye reduction
        88	    => true, // Auto, Did not fire, Red-eye reduction
        89	    => true, // Auto, Fired, Red-eye reduction
        93	    => true, // Auto, Fired, Red-eye reduction, Return not detected
        95	    => true, // Auto, Fired, Red-eye reduction, Return detected
    );


    public static function parse($filename) {
        $D = @exif_read_data($filename, 0, true);
        if ($D === false) { $D = array(); }

        $exif = array(
            'filesize'  => 0,
            'brand'     => '',
            'camera'    => '',
            'width'     => 0,
            'height'    => 0,
            'dimension' => '0x0',
            'aperture'  => '',
            'exposure'  => '',
            'iso'       => '',
            'flash'     => 0,
            'whitebalance'  => '',
            'focal_length'  => '',
            'lens'          => '',
        );

        if ( isset($D['FILE']) ) {
            $exif['filesize']       = isset($D['FILE']['FileSize']) ? $D['FILE']['FileSize'] : "0";
        }

        if ( isset($D['IFD0']) ) {
            $exif['brand']          = isset($D['IFD0']['Make']) ? $D['IFD0']['Make'] : "";
            $exif['camera']         = isset($D['IFD0']['Model']) ? $D['IFD0']['Model'] : "";
        }

        if ( isset($D['COMPUTED']) ) {
            $exif['width']          = isset($D['COMPUTED']['Width']) ? (int)$D['COMPUTED']['Width'] : 0;
            $exif['height']         = isset($D['COMPUTED']['Height']) ? (int)$D['COMPUTED']['Height'] : 0;
            $exif['dimension']      = $exif['width'] . "x" . $exif['height'];
            $exif['aperture']       = str_replace( array('f/',' '), '', isset($D['COMPUTED']['ApertureFNumber']) ? $D['COMPUTED']['ApertureFNumber'] : "" );
        }

        if ( isset($D['EXIF']) ) {
            $exif['exposure']       = isset($D['EXIF']['ExposureTime']) ? $D['EXIF']['ExposureTime'] : '';
            $exif['iso']            = isset($D['EXIF']['ISOSpeedRatings']) ? (int)$D['EXIF']['ISOSpeedRatings'] : '';
            $exif['flash']          = isset($D['EXIF']['Flash']) ? (isset(self::$_flash[$D['EXIF']['Flash']]) ? self::$_flash[$D['EXIF']['Flash']] : null) : null;
            $exif['whitebalance']   = isset($D['EXIF']['WhiteBalance']) ? (isset(self::$_whitebalance[$D['EXIF']['WhiteBalance']]) ? self::$_whitebalance[$D['EXIF']['WhiteBalance']] : null) : null;
            $tmp = isset($D['EXIF']['FocalLength']) ? explode("/", $D['EXIF']['FocalLength']) : array(1,1);
            $exif['focal_length']   = (int)($tmp[0] / $tmp[1]);
            $exif['lens']           = isset($D['EXIF']['LensModel']) ? $D['EXIF']['LensModel'] : (isset($D['EXIF']['UndefinedTag:0xA434']) ? $D['EXIF']['UndefinedTag:0xA434'] : "");
        }

        return $exif;

    }


}
