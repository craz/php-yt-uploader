<?php

require __DIR__ . '/vendor/autoload.php';

session_start();

// Check config file
$cfPath = __DIR__ . '/config.json';
if (!file_exists($cfPath)) {
    die('Configuration file is not found! (config.json)' . PHP_EOL);
}
$cf = json_decode(
    file_get_contents($cfPath)
);

// Check token storage is writable
$tokenStoragePath = __DIR__ . '/access_token.json';
if ((file_exists($tokenStoragePath) && !is_writable($tokenStoragePath))
    || !touch($tokenStoragePath)) {
    die("The path \"$tokenStoragePath\" is not writable!\n");
}

// Build google client
$gClient = new \Google_Client();
$gClient->setApplicationName($cf->client->app_name);
$gClient->setClientId($cf->client->client_id);
$gClient->setClientSecret($cf->client->client_secret);
$gClient->setAccessType('offline');
$gClient->setScopes('https://www.googleapis.com/auth/youtube');
$gClient->setRedirectUri($cf->client->redirect_url);

// Build youtube service
$ytSrv = new \Google_Service_YouTube($gClient);

// Google connected to the script, try to auth
if (isset($_GET['code'])) {
    if (isset($_GET['state']) && strval($_SESSION['state']) !== strval($_GET['state'])) {
        die('The session state did not match!');
    }
    try {
        $gClient->authenticate($_GET['code']);
    } catch (\Google_Exception $e) {

    }
    $_SESSION['token'] = $gClient->getAccessToken();
}

// If has a token the use it
if (isset($_SESSION['token'])) {
    $gClient->setAccessToken($_SESSION['token']);
    file_put_contents($tokenStoragePath, $_SESSION['token']);
    echo '<code> Your access token is: ' . $_SESSION['token'] . '</code>';
}

// If has an access token, then possible authorized, check
if ($gClient->getAccessToken()) {
    try {
        $htmlBody = '';

        // Call the channels.list method to retrieve information about the
        // currently authenticated user's channel.
        $channelsResponse = $ytSrv->channels->listChannels('contentDetails', array(
            'mine' => 'true',
        ));

        foreach ($channelsResponse['items'] as $channel) {
            // Extract the unique playlist ID that identifies the list of videos
            // uploaded to the channel, and then call the playlistItems.list method
            // to retrieve that list.
            $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

            $playlistItemsResponse = $ytSrv->playlistItems->listPlaylistItems('snippet', array(
                'playlistId' => $uploadsListId,
                'maxResults' => 10
            ));

            $htmlBody .= "<h3>Videos in list $uploadsListId</h3><ul>";
            foreach ($playlistItemsResponse['items'] as $playlistItem) {
                $htmlBody .= sprintf('<li>%s (%s)</li>', $playlistItem['snippet']['title'],
                    $playlistItem['snippet']['resourceId']['videoId']);
            }
            $htmlBody .= '</ul>';
        }
    } catch (\Google_Service_Exception $e) {
        $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    } catch (\Google_Exception $e) {
        $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
            htmlspecialchars($e->getMessage()));
    }

    $_SESSION['token'] = $gClient->getAccessToken();
} else {
    // Does not have a token, ask user to auth
    $authUrl = $gClient->createAuthUrl();
    $htmlBody =<<<HTML
<h1>Authorization required!</h1>
<p>
    You need to <a href="$authUrl">authorize access</a> before processing.
</p>
HTML;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>OAuth2 check</title>
</head>
<body>
<?= $htmlBody ?>
</body>
</html>