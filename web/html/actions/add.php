<?php
$showNotificationClass = 'none';
if($_POST && isset($_POST['link']))
{
    $showNotificationClass = 'block';
    $db = new Song();
    $data = array(
        'link' => $_POST['link']
    );    
    $status = $db->addSong($data);
}
?>

<div class="container">
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-body">
                        Add new song
                    </h5>
                    <form action="<?=$_SERVER['REQUEST_URI']?>" method="POST" class="row g-3">
                        <div class="form-group">
                            <label for="link" class="form-label">Link</label>
                            <input type="text" name="link" id="link" class="form-control">
                        </div>
                        <div class="row">

                            <div class="col-md-4 offset-md-9" style="margin-top: 10px;">
                                <div class="form-group">                    
                                    <input type="submit" value="Add" class="btn btn-success form-control">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="alert alert-<?php echo isset($status) ? array_key_first($status) : '';?> <?=$showNotificationClass?>" role="alert">
                <h4 class="alert-heading"><?php echo isset($status) ? $status[array_key_first($status)] : '';?></h4>
            </div>
        </div>
    </div>
</div>



