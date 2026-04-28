<?php
include 'db.php';

$q = $_GET['q'] ?? '';
$q = trim($q);

if(strlen($q) < 2){
    exit;
}

$sql = "SELECT id, book_name, author_name, cover_image
        FROM books
        WHERE status='approved'
        AND (book_name LIKE ? OR author_name LIKE ?)
        LIMIT 8";

$stmt = $conn->prepare($sql);
$term = "%{$q}%";
$stmt->bind_param("ss", $term, $term);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){

        $cover = (!empty($row['cover_image']) && file_exists($row['cover_image']))
                ? $row['cover_image']
                : 'default_cover.png';

        $bookName = htmlspecialchars($row['book_name'], ENT_QUOTES);
        $author   = htmlspecialchars($row['author_name'], ENT_QUOTES);
        $searchQ  = urlencode($row['book_name']);

        echo "
        <div class='search-item'
             onclick=\"window.location='book.php?search={$searchQ}&from=search'\">
            <img src='{$cover}' alt='cover'>
            <div>
                <div class='search-title'>{$bookName}</div>
                <div class='search-author'>by {$author}</div>
            </div>
        </div>
        ";
    }
}else{
    echo "<div class='search-item'>No results found</div>";
}
