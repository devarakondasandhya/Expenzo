<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = strtolower($_SESSION['username']);
$userDB = "expenses_" . $username;

// Connect without selecting a database
$conn = new mysqli("localhost", "root", "Karsan@35");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user's database exists
$result = $conn->query("SHOW DATABASES LIKE '$userDB'");
if ($result->num_rows === 0) {
    die("User database '$userDB' not found. Please contact support or re-register.");
}

// Select the user's personal database
$conn->select_db($userDB);

// Handle form submission (Add expense)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['expense-name'] ?? '');
    $category = trim($_POST['expense-category'] ?? '');
    $amount = floatval($_POST['expense-amount'] ?? 0);
    $date = $_POST['expense-date'] ?? '';

    if ($name && $amount > 0 && $date) {
        $stmt = $conn->prepare("INSERT INTO expenses (name, category, amount, date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $name, $category, $amount, $date);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Please fill all fields correctly.";
    }
}

// Handle remove expense (via GET param ?remove=id)
if (isset($_GET['remove'])) {
    $removeId = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param("i", $removeId);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch all expenses
// Fetch only current month's expenses
$firstDay = date("Y-m-01");
$lastDay = date("Y-m-t");

$stmt = $conn->prepare("SELECT * FROM expenses WHERE date BETWEEN ? AND ? ORDER BY date DESC");
$stmt->bind_param("ss", $firstDay, $lastDay);
$stmt->execute();
$result = $stmt->get_result();

$expenses = [];
$totalAmount = 0;
$categorySums = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
        $totalAmount += $row['amount'];
        $cat = strtolower(trim($row['category']));
        if (!isset($categorySums[$cat])) {
            $categorySums[$cat] = 0;
        }
        $categorySums[$cat] += $row['amount'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title> Expenzo </title>
  
  <div style="text-align: center; margin-bottom: 20px;">
    <a href="history.php" style="text-decoration: none; color: #007bff;">🔎 View Previous Months</a> 
  </div>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="container">
<div style="text-align: right; margin-bottom: 10px;">
  👋 Welcome, <strong><?= htmlspecialchars($username) ?></strong> |
  <a href="logout.php" style="text-decoration: none; color: #dc3545;">Logout</a>
</div>
  <h2 style="color:#007bff">EXPENZO</h2>
  <h2>The Monthly Expenses Tracker</h2>

  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="input-group">
    <input type="text" name="expense-category" placeholder="Category" required />
    <input type="text" name="expense-name" placeholder="Expense name" required />
    <input type="number" name="expense-amount" placeholder="Amount" step="0.01" required />
    <input type="date" name="expense-date" required />
    <button type="submit">Add</button>
  </form>

  <ul id="expense-list" class="expense-list">
    <?php foreach ($expenses as $expense): ?>
      <li class="expense-item">
        <span><?= htmlspecialchars($expense['date']) ?>&nbsp;&nbsp;&nbsp;&nbsp;<?= htmlspecialchars($expense['name']) ?> : ₹<?= number_format($expense['amount'], 2) ?></span>
        <a href="?remove=<?= $expense['id'] ?>" onclick="return confirm('Remove this expense?')" class="remove-btn">Remove</a>
      </li>
    <?php endforeach; ?>
  </ul>

  <h3>Expenses Breakdown</h3>
  <canvas id="expense-chart" width="400" height="200"></canvas>

  <div class="total">Total: ₹<span id="total-amount"><?= number_format($totalAmount, 2) ?></span></div>
</div>

<script>
  const expensesByCategory = <?php echo json_encode($categorySums); ?>;

  const ctx = document.getElementById("expense-chart").getContext("2d");
  const expenseChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: Object.keys(expensesByCategory),
      datasets: [{
        label: 'Expenses by Category',
        data: Object.values(expensesByCategory),
        backgroundColor: '#36A2EB'
      }]
    },
    options: {
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 50
          }
        }
      }
    }
  });
</script>
</body>
</html>
