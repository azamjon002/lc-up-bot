<?php
$connection = mysqli_connect('localhost', 'user', 'M4D3dXFPNwwR_m5', 'educrm2');

function query($sql){
    return $GLOBALS['connection']->query($sql);
}
