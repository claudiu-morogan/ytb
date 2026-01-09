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
            case 'playlists':
                require 'html/actions/playlists.php';
                break;
            case 'delete':
                require 'html/actions/delete.php';
                break;
            case 'delete_all':
                require 'html/actions/delete_all.php';
                break;
            case 'debug_delete':
                require 'html/actions/debug_delete.php';
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