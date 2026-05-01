<?php
session_start();

$mysqli = new mysqli("localhost", "root", "Karsan@35", "users_db");
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($userId, $hashedPassword);
        $stmt->fetch();
        if (password_verify($_POST['password'], $hashedPassword)) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['user_db'] = 'expenses_' . $username;
            header("Location: index.php");
            exit;
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Login - Monthly Expenses Tracker</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f0f4f8;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .login-box {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.15);
      width: 350px;
    }

    .login-box h2 {
      margin-bottom: 20px;
      text-align: center;
      color: #333;
    }

    .login-box input[type="text"],
    .login-box input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-sizing: border-box;
    }

    .login-box button {
      width: 100%;
      padding: 12px;
      background: #007bff;
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .login-box button:hover {
      background: #0056b3;
    }

    .login-box .link {
      margin-top: 15px;
      text-align: center;
    }

    .login-box .link a {
      color: #007bff;
      text-decoration: none;
    }

    .login-box .link a:hover {
      text-decoration: underline;
    }

    .error {
      color: red;
      margin-bottom: 10px;
      text-align: center;
    }
  </style>
</head>
<body>

  <div class="login-box">
    <h2>Login</h2>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
      <div class="link">
        Don't have an account? <a href="register.php">Register here</a>
      </div>
    </form>
  </div>
</body>
</html>
