<?php

include 'Db.php';

class Song extends DataBase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addSong($data)
    {
        $link = trim($data['link']);

        if($link == '')
        {
            $message = [ 'danger' => 'Link is empty'];
            return $message;
        }

        if(!$this->isValidYouTubeUrl($link))
        {
            $message = ['danger' => 'Invalid YouTube URL'];
            return $message;
        }

        if($this->checkForDuplicates($link) == true)
        {
            $message = ['danger' => 'Duplicates found'];
            return $message;
        }

        // Step 1: Insert link into database
        $stmt = $this->connection->prepare("INSERT INTO ytb_downloads (link, playlist) VALUES (?, ?)");
        $playlist = trim($data['playlist'] ?? 'General');
        $stmt->bind_param("ss", $link, $playlist);
        $stmt->execute();
        $video_id = $this->connection->insert_id;
        $stmt->close();

        // Step 2: Fetch metadata from Python API
        $apiUrl = 'http://ytb_python:353/get-metadata?url=' . urlencode($link);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200) {
            error_log("Failed to fetch metadata: " . ($curlError ?: "HTTP $httpCode"));
            $message = ['warning' => 'Song added but metadata fetch failed. Will retry on download.'];
            return $message;
        }

        $metadata = json_decode($response, true);

        if ($metadata && isset($metadata['status']) && $metadata['status'] === 'success') {
            // Step 3: Store metadata in ytb_song_details
            $artist = $metadata['artist'];
            $title = $metadata['title'];

            $stmt = $this->connection->prepare("INSERT INTO ytb_song_details (video_id, artist, song) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $video_id, $artist, $title);
            $stmt->execute();
            $stmt->close();

            $message = ['success' => "Added: $artist - $title"];
        } else {
            error_log("Metadata API returned error: " . ($metadata['message'] ?? 'Unknown error'));
            $message = ['warning' => 'Song added but metadata extraction failed. Will retry on download.'];
        }

        return $message;

    }    

    public function editSong($data)
    {

    }

    public function deleteSong($video_id)
    {
        // Call Python API to handle deletion (database + file)
        // This respects the application architecture: PHP handles UI, Python handles business logic
        $apiUrl = 'http://ytb_python:353/delete-song/' . intval($video_id);

        // Initialize cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Handle cURL errors
        if ($curlError) {
            $errorMsg = "Failed to connect to Python API: " . $curlError;
            error_log($errorMsg);
            return $errorMsg;
        }

        // Log response for debugging
        error_log("Delete API Response: HTTP $httpCode - " . $response);

        // Parse response
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['status'])) {
                if ($result['status'] === 'success') {
                    error_log("Successfully deleted song via API: " . $result['message']);
                    return true;
                } else {
                    $errorMsg = $result['message'] ?? 'Unknown error from API';
                    error_log("API returned error: " . $errorMsg);
                    return $errorMsg;
                }
            } else {
                $errorMsg = "Invalid response from API: " . $response;
                error_log($errorMsg);
                return $errorMsg;
            }
        } else {
            $errorMsg = "API returned HTTP code $httpCode: " . $response;
            error_log($errorMsg);
            return $errorMsg;
        }
    }

    protected function checkForDuplicates($url)
    {
        $stmt = $this->connection->prepare("SELECT * FROM ytb_downloads WHERE link = ?");
        $stmt->bind_param("s", $url);
        $stmt->execute();
        $result = $stmt->get_result();
        $hasDuplicates = $result->num_rows > 0;
        $stmt->close();

        return $hasDuplicates;
    }

    protected function isValidYouTubeUrl($url)
    {
        // Check if URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse the URL
        $parsedUrl = parse_url($url);
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';

        // Check for valid YouTube domains
        $validHosts = ['www.youtube.com', 'youtube.com', 'youtu.be', 'm.youtube.com'];

        return in_array($host, $validHosts);
    }
}