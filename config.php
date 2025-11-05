<?php

$servername = 'localhost';
$username = 'root';
$password = null;
$dbname = 'Question_blog_db';


$connection = new mysqli($servername, $username, $password, $dbname);
if ($connection->connect_error) {
    die("فشل الاتصال" . $connection->connect_error);
} else {
    echo "تم الاتصال بقاعدة البيانات";
}
