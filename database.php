<?php
$connection = mysqli_connect('localhost', 'newuser', 'password', 'educrm');

function query($sql){
    return $GLOBALS['connection']->query($sql);
}
