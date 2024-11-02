<?php
// Check if session is not active, then start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <style>
        #passwordHelp{
            color: #f21d1d !important;
        }
    </style>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="assets/js/jquery-3.4.1.slim.min.js"></script>
    <script>
        function validatePassword() {
            var password = document.getElementById("exampleInputPassword1").value;
            var errorMsg = "";
            var regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

            if (!regex.test(password)) {
                errorMsg = "Password must be at least 8 characters long, include at least one uppercase letter, one number, and one special character.";
                document.getElementById("passwordHelp").innerHTML = errorMsg;
                return false;
            }
            document.getElementById("passwordHelp").innerHTML = "";
            return true;
        }
    </script>
</head>
<body>

<form action="./handle/handleSignUp.php" method="post" onsubmit="return validatePassword()">
    <?php if (isset($_SESSION['errors_signup'])) { ?>
        <script>
            $(document).ready(function(){
                $("#exampleModalCenter").modal('show');
            });
        </script>
        <?php foreach ($_SESSION['errors_signup'] as $error) { ?>
            <div class="alert alert-danger" role="alert">
                <p style="font-size: 15px;" class="text-center"><?php echo $error; ?></p>
            </div>
        <?php }
        unset($_SESSION['errors_signup']);
    }

    if (isset($_SESSION['username_exists'])) { ?>
        <div class="alert alert-danger" role="alert">
            <p style="font-size: 15px;" class="text-center">Username already exists.</p>
        </div>
        <?php unset($_SESSION['username_exists']);
    } ?>
    
    <div class="form-group">
        <input type="text" name="name" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Full Name" required>
    </div>
    <div class="form-group">
        <input type="text" name="username" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Username" required>
    </div>
    <div class="form-group">
        <input type="email" name="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp" placeholder="Email Address" required>
    </div>
    <div class="form-group">
        <input type="password" name="password" class="form-control" id="exampleInputPassword1" placeholder="Password" required>
        <small id="passwordHelp" class="form-text text-muted"></small>
    </div>
    <div class="text-center">
        <button type="submit" name="signup" class="btn btn-danger" >Sign Up</button>
    </div>
</form>

</body>
</html>
