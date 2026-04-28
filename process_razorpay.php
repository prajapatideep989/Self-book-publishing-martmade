<?php
session_start();
if(!isset($_SESSION['razorpay_order_id'])) header("Location: checkout.php");
?>
<!DOCTYPE html>
<html>
<head>
<title>Pay Securely</title>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
<script>
var options = {
    key: "rzp_test_RyD18OGwpj6MwQ", // Razorpay test key
    amount: "<?php echo $_SESSION['razorpay_amount']; ?>",
    currency: "INR",
    name: "Alpha Book Store",
    description: "Book Purchase",
    order_id: "<?php echo $_SESSION['razorpay_order_id']; ?>",
    handler: function(response){
        // Redirect to your success page
        window.location.href = "success.php?method=Online&payment_id=" + response.razorpay_payment_id;
    },
    theme:{color:"#4f46e5"}
};
var rzp = new Razorpay(options);
window.onload = function(){ rzp.open(); }
</script>
</body>
</html>
