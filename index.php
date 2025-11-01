<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

</head>

<body>
    <div class="container py-4">
        <form action="insert.php" method="post">
            <div class="mb-3">
                <input type="text" name="question" id="" class="form-control mb-2">
            </div>
            <button type="submit" class="btn btn-primary" name="add">add</button>
        </form>
    </div>
    <?php if (isset($_GET['add'])) {
        $q = $_GET['question'];
        $qst[] = $q;
    } ?>
    <div class="container py-4">
        <ul class="list-group">
            <?php
            $connection = new mysqli('localhost', 'root', null, 'question');
            $sql = "SELECT * FROM questions";
            $result = $connection->query($sql);
            while ($row = $result->fetch_assoc()) {
                echo "<li class='list-group-item'>";
                echo $row["nom"];
                echo "</li>";
            }
            ?>
        </ul>
    </div>
</body>

</html>