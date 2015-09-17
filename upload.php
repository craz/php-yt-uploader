<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// If no args then show usage message
if ($argc < 2) {
    die('Usage: php upload.php path_to_the_video_file.mp4 [private|public|unlisted] [title] [description] [tags,tag,tag]' . PHP_EOL);
}

// Get path to the file
$pathToTheFile = trim($argv[1]);
if (!file_exists($pathToTheFile)) {
    die("File \"$pathToTheFile\" not found!\n");
}

// Get privacy level
$privacy = 'private';
if ($argc > 2) {
    $privacy = trim($argv[2]);
}
if (!in_array($privacy, ['private', 'public', 'unlisted'])) {
    die('Invalid privacy value, should be: private, public or unlisted' . PHP_EOL);
}

// Get title
if ($argc > 3) {
    $title = trim($argv[3]);
} else {
    $title = sha1(mt_rand());
}

// Get description
if ($argc > 4) {
    $description = trim($argv[4]);
} else {
    $description = sha1(mt_rand());
}

// Get tags
if ($argc > 5) {
    $tags = explode(',', trim($argv[5]));
} else {
    $tags = null;
}

// Load configuration
$cfPath = __DIR__ . '/config.json';
$cf = json_decode(
    file_get_contents($cfPath)
);

$uploader = new YTUploader\Uploader(
    $cf->client->app_name, $cf->client->client_id, $cf->client->client_secret,
    __DIR__ . DIRECTORY_SEPARATOR . 'access_token.json'
);
try {
    echo 'Uploading...' . PHP_EOL;
    if (!$uploader->upload($pathToTheFile, $privacy, $title, $description, 0, $tags)) {
        echo 'Failed to upload the video! Upload status: ' . $uploader->getLastUploadStatus() . PHP_EOL;
    } else {
        echo 'Success!' . PHP_EOL;
    }
} catch (\Google_Exception $e) {
    echo 'Google_Exception occurs: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
} catch (YTUploader\Exception\UploaderException $e) {
    echo 'Uploader Exception occurs: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
} catch (\Exception $e) {
    echo 'Exception occurs: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}