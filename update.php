<?php

include 'config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "select * from questions where id=$id";
    $result = $connection->query($sql);
    $row = $result->fetch_assoc();
    $question = $row['nom'];
}


?>
<form action="" method="post" class="d-flex">
    <label>تعديل السؤال:</label>
    <input type="text" class="form-control me-2" name="qst_name" value="<?php echo $question; ?>">
    <button type="submit" name="update" lass="btn btn-success">حفظ التعديل</button>
    <a  href="index.php" class="btn btn-secondary" >إلغاء</a>

</form>

<?php

if (isset($_POST['update'])) {

    $new_question = $_POST['qst_name'];
    $sql = "update questions set nom='" . $new_question . "' where id=$id";
    $result = $connection->query($sql);


    if ($result) {
        echo "تم التعديل بنجاح";
        header("Location: index.php");
    } else {
        echo "خطأ في التعديل: " . $connection->error;
    }
}
