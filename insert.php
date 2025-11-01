<?php
$connection = new mysqli('localhost', 'root', null, 'question');
if (!$connection) {
    echo "فشل الاتصال";
} else {
    echo "تم الاتصال بقاعدة البيانات";
}



if (isset($_POST['add'])) {
    $title = $_POST['question'];
    $sql = "INSERT INTO `questions` (`nom`, `contenu`, `date`) VALUES ('$title', NULL, current_timestamp())";
    if ($connection->query($sql)) {
        echo "<br>";
        echo "تم اضافة البياتات";
    } else {
        echo 'no insert';
    }

    // echo "<script>window.history.back()</script>";
    header("location: index.php");
}
