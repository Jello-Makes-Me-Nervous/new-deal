<?php
include_once('setup.php');

function imgUp($fileToUpload, $newName, $target_dir) {

    $array = explode('.', $fileToUpload["name"]);
    $ext = end($array);
    $ext=strtolower($ext);

    $target_file = $target_dir. basename($fileToUpload["name"]);

    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
// Check if image file is a actual image or fake image
    if (isset($_POST["submit"])) {
        $check = getimagesize($fileToUpload["tmp_name"]);
        if ($check !== false) {
            echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
            echo "File is not an image.";
            $uploadOk = 0;
        }
    }
// Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }
    // Check file size
    if ($fileToUpload["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }
// Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
    && $imageFileType != "gif" ) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }
// Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($fileToUpload["tmp_name"], $target_file)) {
            $filename = $fileToUpload["name"];

            $old = $target_dir.$filename;
            $new = $target_dir.$newName.".".$ext;
            rename($old , $new);

            echo "The file ".$new. " has been uploaded.";
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
    return $newName.".".$ext;
}

function copyImages($advertClassId, $originalPath, $classPath, $thumbPath) {
    global $DB;

    $sql = "
    SELECT maxheight, maxwidth
      FROM advertclass
     WHERE advertclassid = $advertClassId
    ";
    $row = $DB->sql_query_params($sql);
    if (!empty($row)) {
        $r = reset($row);
// $className    = $r['advertclassname'];
        $maxHeight    = $r['maxheight'];
        $maxWidth     = $r['maxwidth'];
    }
//use to resize image
    if ($maxHeight > $maxWidth) {
        $type = "height";
        $maxSize = $maxHeight;
    } elseif ($maxWidth > $maxHeight) {
        $type = "width";
        $maxSize = $maxWidth;
    }

//after upload copy the original to each folder
    copy($originalPath, $classPath);
    copy($originalPath, $thumbPath);

    $info = array();
    $info['type'] = $type;
    $info['maxSize'] = $maxSize;

    return $info;
}

function resizeImages($type, $maxSize = NULL, $classPath, $originalPath, $targetFile) {

    $info = getimagesize($originalPath);
    $mime = $info['mime'];

    switch ($mime) {
            case 'image/jpeg':
                $image_create_func = 'imagecreatefromjpeg';
                $image_save_func = 'imagejpeg';
                $new_image_ext = 'jpg';
                break;
            case 'image/png':
                $image_create_func = 'imagecreatefrompng';
                $image_save_func = 'imagepng';
                $new_image_ext = 'png';
                break;
            case 'image/gif':
                $image_create_func = 'imagecreatefromgif';
                $image_save_func = 'imagegif';
                $new_image_ext = 'gif';
                break;
            default:
                throw new Exception('Unknown image type.');
    }

    $img = $image_create_func($originalPath);
    list($width, $height) = getimagesize($originalPath);

    if ($type == "height") {
        $newHeight = $maxSize;
        $newWidth = ($width / $height) * $newHeight;
    } elseif ($type == "width") {
        $newWidth = $maxSize;
        $newHeight = ($height / $width) * $newWidth;
    } elseif ($type == "thumb") {
        $newHeight = 30;
        $newWidth = ($width / $height) * $newHeight;
    }
//$newHeight = ($height / $width) * $newWidth;//width
//$newWidth = ($width / $height) * $newHeight;//height
    $tmp = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    if (file_exists($targetFile)) {
            unlink($targetFile);
    }
    $image_save_func($tmp, "$targetFile");
}
?>