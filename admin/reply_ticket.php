<?php 
require_once "../functions.php";
if(!isAdmin()) die("Unauthorized");
if($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $conn->prepare("UPDATE support_tickets SET admin_reply = ?, status = ? WHERE id = ?");
    $status = "closed";
    $stmt->bind_param("ssi", $_POST["reply"], $status, $_POST["ticket_id"]);
    $stmt->execute();
    header("Location: index.php");
}
?>