<?php

function prefixFullPath($targetDir) {
    global $CFG;

    return $CFG->dataroot.$targetDir;
}


function prefixDeleteImage($fileName, $targetDir) {
    global $page;

    $success = false;

    if ($fileName && $targetDir) {
        $fullFileName = $page->cfg->dataroot.$targetDir.$fileName;
        if (file_exists($fullFileName)) {
            if (unlink($fullFileName)) {
                $success = true;
            } else {
                $page->messages->addErrorMsg("Unable to delete file.");
            }
        } else {
            $page->messages->addErrorMsg("Unable to delete file. File does not exist.");
        }
    } else {
        $page->messages->addErrorMsg("Unable to delete file. File and path not specified.");
    }

    return $success;
}

function prefixImgUp($fileToUpload, $newName, $target_dir, &$page) {
    global $CFG;

    $uploadOk = true;
    $imageName = NULL;

    $array = explode('.', $fileToUpload["name"]);
    $ext = end($array);
    $ext=strtolower($ext);

    $target_name = $newName.".".$ext;
    $target_file = $CFG->dataroot.$target_dir.$target_name;

    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Allow certain file formats
    if ($imageFileType != "jpg"  && $imageFileType != "png" &&
        $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $page->messages->addErrorMsg("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
        $uploadOk = false;
    }

    // Check if image file is a actual image or fake image
    if ($uploadOk && isset($fileToUpload)) {
        $check = getimagesize($fileToUpload["tmp_name"]);
        if ($check !== false) {
            $uploadOk = true;
        } else {
            $page->messages->addErrorMsg("File is not an image.");
            $uploadOk = false;
        }
    }

    // Check if file already exists
    if ($uploadOk && file_exists($target_file)) {
        $page->messages->addErrorMsg("Sorry, your file already exists. Please delete and add again.");
        $uploadOk = false;
    }

    // Check file size
    if ($uploadOk && ($fileToUpload["size"] > $CFG->IMG_MAX_UPLOAD)) {
        $page->messages->addErrorMsg("Sorry, your file is too large.");
        $uploadOk = false;
    }

    if ($uploadOk) {
        //echo "Move ".$fileToUpload["tmp_name"]." to ".$target_file."<br />\n";
        if (move_uploaded_file($fileToUpload["tmp_name"], $target_file)) {
            chmod($target_file, 0666);
            $imageName = $target_name;
            $page->messages->addSuccessMsg("The file ".$fileToUpload["name"]. " has been uploaded.");
        } else {
            $page->messages->addErrorMsg("Sorry, there was an error uploading your file.");
        }
    }

    return $imageName;
}

function prefixCopyPublicImage($sourcePath, $sourceFile, $targetPath, $targetBaseName) {
    global $page;
    
    $targetFile = null;

    $array = explode('.', $sourceFile);
    $ext = end($array);
    $ext=strtolower($ext);
    $sourceFull = $sourcePath.$sourceFile;
    echo "prefixCopyPublicImage: sourceFull:".$sourceFull."<br />\n";
    if (file_exists($sourceFull)) {
        $targetFile = $targetBaseName.".".$ext;
        $targetFull = $targetPath.$targetFile;
        echo "prefixCopyPublicImage: targetFull:".$targetFull."<br />\n";
        if (file_exists($targetFull)) {
            $page->messages->addWarningMsg("Public image ".$targetFile." with same name already exists. Public image not uploaded.");
            $targetFile = null;
            echo "prefixCopyPublicImage: targetFull:".$targetFull." already exists<br />\n";
        } else {
            echo "prefixCopyPublicImage: copying<br />\n";
            if (copy($sourceFull, $targetFull)) {
                echo "prefixCopyPublicImage: copied<br />\n";
                chmod($targetFull, 0666);
                $page->messages->addSuccessMsg("Public image ".$targetFile. " has been uploaded.");
            } else {
                echo "prefixCopyPublicImage: copy failed<br />\n";
                $page->messages->addWarningMsg("Unable to copy  listing image ".$sourceFile." to public image ".$targetFile.". Public image not uploaded.");
                $targetFile = null;
            }
        }
    } else {
        echo "prefixCopyPublicImage: no source file<br />\n";
        $page->messages->addWarningMsg("Unable to locate source image ".$sourceFull.". Public image not uploaded.");
        $targetFile = null;
    }
    
    return $targetFile;
}

function prefixDocUp($fileToUpload, $newName, $target_dir, &$page) {
    global $CFG;

    $uploadOk = true;
    $imageName = NULL;

    $array = explode('.', $fileToUpload["name"]);
    $ext = end($array);
    $ext=strtolower($ext);

    $target_name = $newName.".".$ext;
    $target_file = $CFG->dataroot.$target_dir.$target_name;

    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Allow certain file formats
    if ($imageFileType != "pdf") {
        $page->messages->addErrorMsg("Sorry, only PDF files are allowed.");
        $uploadOk = false;
    }

    if (isset($fileToUpload)) {
        if ($fileToUpload["type"] != 'application/pdf') {
            $page->messages->addErrorMsg("File is not an PDF document.");
            $uploadOk = false;
        }
    }

    // Check if file already exists
    if ($uploadOk && file_exists($target_file)) {
        $page->messages->addErrorMsg("Sorry, your file already exists. Please delete and add again.");
        $uploadOk = false;
    }

    // Check file size
    if ($uploadOk && ($fileToUpload["size"] > $CFG->DOC_MAX_UPLOAD)) {
        $page->messages->addErrorMsg("Sorry, your file is too large.");
        $uploadOk = false;
    }

    if ($uploadOk) {
        if (move_uploaded_file($fileToUpload["tmp_name"], $target_file)) {
            chmod($target_file, 0666);
            $imageName = $target_name;
            $page->messages->addSuccessMsg("The file ".$fileToUpload["name"]. " has been uploaded.");
        } else {
            $page->messages->addErrorMsg("Sorry, there was an error uploading your file.");
        }
    }

    return $imageName;
}

function imgUp($fileToUpload, $newName, $target_dir, &$page) {
    global $CFG;

    $array = explode('.', $fileToUpload["name"]);
    $ext = end($array);
    $ext=strtolower($ext);

    $target_file = $target_dir. basename($fileToUpload["name"]);

    $uploadOk = 1;

    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    // Check if image file is a actual image or fake image
    if (isset($fileToUpload)) {
        $check = getimagesize($fileToUpload["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $page->messages->addErrorMsg("File is not an image.");
            $uploadOk = 0;
        }
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        $page->messages->addErrorMsg("Sorry, your file already exists. Please delete and add again.");
        $uploadOk = 0;
    }

    // Check file size
    if ($fileToUpload["size"] > $CFG->IMG_MAX_UPLOAD) {
        $page->messages->addErrorMsg("Sorry, your file is too large.");
        $uploadOk = 0;
    }

    // Allow certain file formats
    if ($imageFileType != "jpg"  && $imageFileType != "png" &&
        $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $page->messages->addErrorMsg("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
        $uploadOk = 0;
    }
    if ($uploadOk == 0) {
        // Check if $uploadOk is set to 0 by an error
    } else {
        if (move_uploaded_file($fileToUpload["tmp_name"], $target_file)) {
            $filename = $fileToUpload["name"];

            $old = $target_dir.$filename;
            $new = $target_dir.$newName.".".$ext;
            rename($old , $new);
            chmod($new, 0666);

            $page->messages->addSuccessMsg("The file ".$filename. " has been uploaded.");
        } else {
            $page->messages->addErrorMsg("Sorry, there was an error uploading your file.");
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
/*
    if (!empty($row)) {
        $r = reset($row);
// $className    = $r['advertclassname'];
        $maxHeight    = $r['maxheight'];
        $maxWidth     = $r['maxwidth'];
    }
 */
 $maxWidth = 110;
 $maxHeight = 100;
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

function resizeImages($type, $maxSize, $classPath, $originalPath, $targetFile) {

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