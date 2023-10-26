<?php
    if(isset($_GET['action']))
    {
        $action = $_GET['action'];
        switch($action)
        {
            case 'list':
                require 'html/actions/list.php';
                break;
            case 'add':
                require 'html/actions/add.php';
                break;
            case 'download':
                require 'html/actions/download.php';
                break;
            default:
                require 'html/actions/list.php';
                break;
        }
    } else 
    {
        require 'html/actions/list.php';
    }
?>