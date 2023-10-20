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
        if($this->checkForDuplicates($data['link']) == true)
        {
            $message = ['danger' => 'Duplicates found'];   
            return $message;         
        }

        if(trim($data['link']) == '')
        {
            $message = [ 'danger' => 'Link is empty'];   
            return $message;         
        }

        $sql = "
            INSERT INTO ytb_downloads (link)
            VALUES ('".trim($data['link'])."')";        
        $this->execute($sql);

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
        $sql = "
            SELECT * FROM ytb_downloads
            WHERE link = '".$url."'";
        
        $result = $this->query($sql);
        if ($result->num_rows > 0) {
            return true;
        }
        return false;

    }
}