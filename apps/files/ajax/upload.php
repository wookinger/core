<?php

// Init owncloud


// Firefox and Konqueror tries to download application/json for me.  --Arthur
OCP\JSON::setContentTypeHeader('text/plain');

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
$l=OC_L10N::get('files');

if (!isset($_FILES['files'])) {
	OCP\JSON::error(array('data' => array( 'message' => $l->t( 'No file was uploaded. Unknown error' ))));
	exit();
}

foreach ($_FILES['files']['error'] as $error) {
	if ($error != 0) {
		$errors = array(
			UPLOAD_ERR_OK=>$l->t('There is no error, the file uploaded with success'),
			UPLOAD_ERR_INI_SIZE=>$l->t('The uploaded file exceeds the upload_max_filesize directive in php.ini: ')
										.ini_get('upload_max_filesize'),
			UPLOAD_ERR_FORM_SIZE=>$l->t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified'
										.' in the HTML form'),
			UPLOAD_ERR_PARTIAL=>$l->t('The uploaded file was only partially uploaded'),
			UPLOAD_ERR_NO_FILE=>$l->t('No file was uploaded'),
			UPLOAD_ERR_NO_TMP_DIR=>$l->t('Missing a temporary folder'),
			UPLOAD_ERR_CANT_WRITE=>$l->t('Failed to write to disk'),
		);
		OCP\JSON::error(array('data' => array( 'message' => $errors[$error] )));
		exit();
	}
}
$files=$_FILES['files'];

$dir = $_POST['dir'];
$error='';

$totalSize=0;
foreach($files['size'] as $size) {
	$totalSize+=$size;
}
if($totalSize>OC_Filesystem::free_space($dir)) {
	OCP\JSON::error(array('data' => array( 'message' => $l->t( 'Not enough space available' ))));
	exit();
}

$result=array();
if(strpos($dir, '..') === false) {
	$fileCount=count($files['name']);
	for($i=0;$i<$fileCount;$i++) {
		$target = OCP\Files::buildNotExistingFileName(stripslashes($dir), $files['name'][$i]);
		// $path needs to be normalized - this failed within drag'n'drop upload to a sub-folder
		$target = OC_Filesystem::normalizePath($target);
		if(is_uploaded_file($files['tmp_name'][$i]) and OC_Filesystem::fromTmpFile($files['tmp_name'][$i], $target)) {
			$meta = OC_FileCache::get($target);
			$id = OC_FileCache::getId($target);
			$result[]=array( 'status' => 'success',
				'mime'=>$meta['mimetype'],
				'size'=>$meta['size'],
				'id'=>$id,
				'name'=>basename($target));
		}
	}
	OCP\JSON::encodedPrint($result);
	exit();
} else {
	$error=$l->t( 'Invalid directory.' );
}

OCP\JSON::error(array('data' => array('message' => $error )));
