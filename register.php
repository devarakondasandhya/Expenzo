<?php
session_start();

// Central DB for users
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "Karsan@35";
$usersDB = "users_db";

// Connect to users_db
$mysqli = new mysqli($servername, $dbUsername, $dbPassword, $usersDB);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$username || !$password || !$confirmPassword) {
        $error = "All fields are required.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        // Check if username already exists
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user into users table
            $stmtInsert = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmtInsert->bind_param("ss", $username, $hashedPassword);

            if ($stmtInsert->execute()) {
                // Create a new database for this user
                $userDBName = "expenses_" . strtolower($username);

                // Connect without selecting DB to create new DB
                $conn = new mysqli($servername, $dbUsername, $dbPassword);
                if ($conn->connect_error) {
                    $error = "Error connecting to server to create user database: " . $conn->connect_error;
                } else {
                    // Create user-specific database
                    if ($conn->query("CREATE DATABASE `$userDBName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") === TRUE) {
                        // Create expenses table in the new DB
                        $conn->select_db($userDBName);

                        $createTableSQL = "CREATE TABLE expenses (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            category VARCHAR(100) DEFAULT 'Uncategorized',
                            amount DECIMAL(10,2) NOT NULL,
                            date DATE NOT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

                        if ($conn->query($createTableSQL) === TRUE) {
                            $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                        } else {
                            $error = "Failed to create expenses table: " . $conn->error;
                        }
                    } else {
                        $error = "Failed to create user database: " . $conn->error;
                    }
                    $conn->close();
                }
            } else {
                $error = "Registration failed: " . $stmtInsert->error;
            }
            $stmtInsert->close();
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Register</title>
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
  .container {
    max-width: 400px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  h2 {
    text-align: center;
    margin-bottom: 20px;
  }
  input[type="text"], input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 1px solid #ccc;
    box-sizing: border-box;
  }
  button {
    width: 100%;
    padding: 10px;
    background-color: #007bff;
    color: white;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 1em;
  }
  button:hover {
    background-color: #0056b3;
  }
  .error {
    color: red;
    margin-bottom: 15px;
    text-align: center;
  }
  .success {
    color: green;
    margin-bottom: 15px;
    text-align: center;
  }
  a {
    color: #007bff;
    text-decoration: none;
  }
  a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

  <div class="container">
    <h2>Register</h2>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="text" name="username" placeholder="Username" required />
      <input type="password" name="password" placeholder="Password" required />
      <input type="password" name="confirm_password" placeholder="Confirm Password" required />
      <button type="submit">Register</button>
    </form>
    <p style="text-align:center; margin-top: 15px;">
      Already have an account? <a href="login.php">Login here</a>.
    </p>
  </div>
</body>
</html>
