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

        $stmt = $this->connection->prepare("INSERT INTO ytb_downloads (link) VALUES (?)");
        $stmt->bind_param("s", $link);
        $stmt->execute();
        $stmt->close();

        $message = ['success' => 'New record created successfully'];

        return $message;

    }    

    public function editSong($data)
    {

    }

    public function deleteSong($data)
    {

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