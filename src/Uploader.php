<?php

namespace YTUploader;

use YTUploader\Exception\UploaderException;

/**
 * YouTube video uploader
 *
 * @author Yegor Chuperka <ychuperka@live.com>
 * @package YTUploader
 */
class Uploader
{
    /**
     * @var \Google_Client
     */
    private $client;

    /**
     * @var \Google_Service_YouTube
     */
    private $youtubeService;

    /**
     * @var string
     */
    private $tokenStoragePath;

    /**
     * @var string
     */
    private $lastUploadStatus;

    /**
     * Constructor
     *
     * @param string $appName
     * @param string $clientId
     * @param string $clientSecret
     * @param string $tokenStoragePath
     * @throws UploaderException
     */
    public function __construct($appName, $clientId, $clientSecret, $tokenStoragePath)
    {
        // Check token storage
        if (!file_exists($tokenStoragePath)) {
            throw new UploaderException("A token storage is not found, path: $tokenStoragePath");
        }

        if (filesize($tokenStoragePath) == 0) {
            throw new UploaderException('A token storage is empty');
        }

        if (!is_writable($tokenStoragePath)) {
            throw new UploaderException('A token storage is not writable');
        }

        $token = file_get_contents($tokenStoragePath);
        $this->tokenStoragePath = $tokenStoragePath;

        // Build client
        $c = new \Google_Client();
        $c->setApplicationName($appName);
        $c->setClientId($clientId);
        $c->setClientSecret($clientSecret);
        $c->setAccessToken($token);
        $c->setAccessType('offline');
        $c->setScopes(
            [
                'https://www.googleapis.com/auth/youtube.upload',
                'https://www.googleapis.com/auth/youtube',
                'https://www.googleapis.com/auth/youtubepartner'
            ]
        );
        $this->client = $c;

        $this->youtubeService = new \Google_Service_YouTube($c);
    }

    /**
     * Upload a video
     *
     * Upload a video to YouTube.
     * Returns false if upload is fails or true if it is ok.
     * May throw exceptions, use only in a try/catch block.
     *
     * @param string $path Video file path on a disc
     * @param string $status A video status ("private", "public" or "unlisted")
     * @param null $title A video title
     * @param null $description A video description
     * @param int $categoryId Video category id
     * @param array|null $tags Tags list
     * @return bool
     * @throws UploaderException
     */
    public function upload($path, $status = 'private', $title = null, $description = null, $categoryId = 0, array $tags = null)
    {
        if (!file_exists($path)) {
            throw new UploaderException("The video file is not found, path: $path");
        }

        if ($title === null) {
            $title = sha1(mt_rand());
        }

        if ($description === null) {
            $description = sha1(mt_rand());
        }

        // Refresh token
        $this->checkAndUpdateAnAccessToken();

        // Snippet
        $snippet = new \Google_Service_YouTube_VideoSnippet();
        $snippet->setTitle($title);
        $snippet->setDescription($description);
        if ($categoryId > 0) {
            $snippet->setCategoryId($categoryId);
        }
        if ($tags !== null) {
            $snippet->setTags($tags);
        }

        // Video status
        $vStatus = new \Google_Service_YouTube_VideoStatus();
        $vStatus->setPrivacyStatus($status);

        // Video
        $video = new \Google_Service_YouTube_Video();
        $video->setSnippet($snippet);
        $video->setStatus($vStatus);

        // Upload
        $chunkSizeBytes = 1 * 1024 * 1014; // 1 mb
        $this->client->setDefer(true);
        $insertRequest = $this->youtubeService->videos->insert('status,snippet', $video);

        $media = new \Google_Http_MediaFileUpload(
            $this->client,
            $insertRequest,
            'video/*',
            null,
            true,
            $chunkSizeBytes
        );
        $media->setFileSize(filesize($path));

        // Send the video file chunk by chunk
        $uplStatus = false;
        $vHandle = fopen($path, 'rb');
        while (!$uplStatus && !feof($vHandle)) {
            $chunk = fread($vHandle, $chunkSizeBytes);
            $uplStatus = $media->nextChunk($chunk);
        }
        fclose($vHandle);

        $this->lastUploadStatus = $uplStatus->status['uploadStatus'];
        return $this->lastUploadStatus === 'uploaded';
    }

    /**
     * Get last upload status
     *
     * @return string
     */
    public function getLastUploadStatus()
    {
        return $this->lastUploadStatus;
    }

    /**
     * Check an access token and update it if expired
     *
     * @throws UploaderException
     */
    protected function checkAndUpdateAnAccessToken()
    {
        if (!$this->client->getAccessToken()) {
            throw new UploaderException('Can not get access token from a client');
        }

        if (!$this->client->isAccessTokenExpired()) {
            return;
        }

        $newToken = json_decode($this->client->getAccessToken());
        $this->client->refreshToken($newToken->refresh_token);
        file_put_contents($this->tokenStoragePath, $this->client->getAccessToken());
    }
}