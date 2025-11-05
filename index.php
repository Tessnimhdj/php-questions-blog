<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>

<body>



    <div class="container py-4">
        <ul class="list-group">
            <li class='list-group-item d-flex justify-content-between align-items-center'>
                <div class="flex-grow-1 me-2">
                    <form action="insert.php" method="post" class="d-flex">
                        <input type="text" class="form-control me-2" name="qst_name" placeholder="اضافه سؤال جديد......">
                        <button type="submit" class="btn btn-primary" name="add">Add</button>
                    </form>
                </div>
            </li>


            <?php
            $conn = new mysqli('localhost', 'root', '', 'question_blog_db');
            $sql = "SELECT * FROM questions ";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo " <li class='list-group-item d-flex justify-content-between align-items-center'>
                        <span>" . $row['nom'] . "</span>
                        <div>
                            <a href='info.php?id=" . $row['id'] . "' class='btn btn-info'>اضافه محتوى..</a>
                            <a href='update.php?id=" . $row['id'] . "' class='btn btn-success btn-sm mx-1'>تعديل سؤال</a>
                            <a href='delete.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm mx-1'>حذف سؤال</a>
                        </div>
                    </li>";
                }
            }
                $conn->close();

            ?>
        </ul>
    </div>




</body>

</html>