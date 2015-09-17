YTUploader it is a tiny class that helps you upload videos to the YouTube using PHP.
Licensed under MIT license - https://opensource.org/licenses/MIT

# Usage #
* You should create an OAuth2 API client id in the Google Developer`s Console.
* After it you need to create config.json (see config.json.example) and fill it with yours client data.
"redirect_url" - should points to the auth.php
* You should install dependencies using composer (composer install), to install google-api client.
* "yt-uploader/access_token.json" - should be writable!
* You should authorize the uploader using OAuth 2, open the auth.php using your web-browser. After it "access_token.json"
stores yours access token, use it to upload videos.

# Configuration example #
```
{
  ...
  "client": {
    "app_name": "YTUploader",
    "client_id": "xxxxxxxxxxxxxxxxxxxx.apps.googleusercontent.com",
    "client_secret": "xxxxxxxxxxxxxxxxxxxxxx",
    "redirect_url": "http://xxxxxxxxxxxxxxx/auth.php"
  }
}
```

# Code example #
```
...
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
```