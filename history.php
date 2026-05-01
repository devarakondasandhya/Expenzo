<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Get current username and build the user's database name
$username = $_SESSION['username'];
$dbname = "expenses_" . $username;

// Connect to that database
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "Karsan@35";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Get month/year from GET params or fallback to previous month
if (isset($_GET['year']) && isset($_GET['month'])) {
    $selectedYear = intval($_GET['year']);
    $selectedMonth = intval($_GET['month']);
} else {
    $selectedYear = date('Y', strtotime("first day of last month"));
    $selectedMonth = date('m', strtotime("first day of last month"));
}

$firstDay = date("Y-m-01", strtotime("$selectedYear-$selectedMonth-01"));
$lastDay = date("Y-m-t", strtotime($firstDay));

// Fetch distinct years from DB for dropdown
$yearsResult = $conn->query("SELECT DISTINCT YEAR(date) as year FROM expenses ORDER BY year DESC");
$years = [];
if ($yearsResult) {
    while ($row = $yearsResult->fetch_assoc()) {
        $years[] = $row['year'];
    }
}

// Query expenses for selected month/year
$stmt = $conn->prepare("SELECT * FROM expenses WHERE date BETWEEN ? AND ? ORDER BY date DESC");
$stmt->bind_param("ss", $firstDay, $lastDay);
$stmt->execute();
$result = $stmt->get_result();

$expenses = [];
$chartData = [];
$totalAmount = 0;
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
    $totalAmount += $row['amount'];
    $cat = $row['category'] ?: 'Uncategorized';
    if (isset($chartData[$cat])) {
        $chartData[$cat] += $row['amount'];
    } else {
        $chartData[$cat] = $row['amount'];
    }
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Previous Month History</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial; background: #f5f5f5; padding: 20px; }
    .container {
      max-width: 800px; margin: auto; background: white;
      padding: 20px; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    h2 { text-align: center; margin-bottom: 20px; }
    .expense-item {
      display: flex; justify-content: space-between;
      background: #f1f1f1; padding: 10px; border-radius: 8px;
      margin-bottom: 10px;
    }
    .total { text-align: right; font-size: 1.2em; font-weight: bold; }
    a.back-link { display: block; margin: 10px auto 20px; text-align: center; color: #007bff; text-decoration: none; }
    a.back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Previous Month's Expenses</h2>
    <a href="index.php" class="back-link">⬅️ Back to Current Month</a>
    <form method="GET" style="margin-bottom: 20px; text-align:center;">
  <label for="month">Month:</label>
  <select name="month" id="month" required>
    <?php
      for ($m = 1; $m <= 12; $m++) {
        $selected = ($m == $selectedMonth) ? 'selected' : '';
        $monthNum = str_pad($m, 2, '0', STR_PAD_LEFT);
        echo "<option value='$m' $selected>$monthNum</option>";
      }
    ?>
  </select>

  <label for="year">Year:</label>
  <select name="year" id="year" required>
    <?php
      foreach ($years as $year) {
        $selected = ($year == $selectedYear) ? 'selected' : '';
        echo "<option value='$year' $selected>$year</option>";
      }
    ?>
  </select>

  <button type="submit">View</button>
</form>

    <?php if (empty($expenses)): ?>
        <p style="text-align:center;">No expenses found for <?= date("F Y", strtotime($firstDay)) ?>.</p>
    <?php else: ?>
      <?php foreach ($expenses as $expense): ?>
        <div class="expense-item">
          <span><?= htmlspecialchars($expense['name']) ?> (<?= htmlspecialchars($expense['category']) ?>)</span>
          <span>₹<?= number_format($expense['amount'], 2) ?> &nbsp;&nbsp;&nbsp;&nbsp; <?= htmlspecialchars($expense['date']) ?></span>
        </div>
      <?php endforeach; ?>

      <canvas id="expense-chart" width="400" height="200"></canvas>
      <div class="total">Total: ₹<?= number_format($totalAmount, 2) ?></div>
    <?php endif; ?>
  </div>

<script>
  const expensesByCategory = <?= json_encode($chartData) ?>;
  const ctx = document.getElementById("expense-chart")?.getContext("2d");

  
  if (ctx && Object.keys(expensesByCategory).length > 0) {
  const categories = Object.keys(expensesByCategory);
  const values = Object.values(expensesByCategory);
  
  const uniformColor = 'hsl(210, 70%, 52%)'; // or any other color
  const backgroundColors = Array(categories.length).fill(uniformColor);

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: categories,
      datasets: [{
        label: 'Expenses by Category',
        data: values,
        backgroundColor: backgroundColors
      }]
    },
    options: {
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
}

</script>
</body>
</html>
