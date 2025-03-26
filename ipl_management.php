<?php
session_start();

// Database connection
$host = 'localhost';
$db   = 'ipl_management';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Helper function to fetch all rows
function fetchAll($pdo, $query) {
    return $pdo->query($query)->fetchAll();
}

// Sign-up handling
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['signup'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $signup_error = "Passwords do not match";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $signup_error = "Username already exists";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            if ($stmt->execute([$username, $hashed_password])) {
                $signup_success = "Account created successfully. Please log in.";
            } else {
                $signup_error = "Error creating account";
            }
        }
    }
}

// Login handling
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
    } else {
        $login_error = "Invalid username or password";
    }
}

// Logout handling
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    // Create operations
    if (isset($_POST['add_team'])) {
        $stmt = $pdo->prepare("INSERT INTO Team (team_name, matches_lost, matches_won, coach_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['team_name'], $_POST['matches_lost'], $_POST['matches_won'], $_POST['coach_name']]);
    } elseif (isset($_POST['add_player'])) {
        $stmt = $pdo->prepare("INSERT INTO Player (name, nationality, bowling_average, batting_average, wickets_taken, highest_score, dob, is_captain, team_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['nationality'],
            $_POST['bowling_average'],
            $_POST['batting_average'],
            $_POST['wickets_taken'],
            $_POST['highest_score'],
            $_POST['dob'],
            isset($_POST['is_captain']) ? 1 : 0,
            $_POST['team_id']
        ]);
    } elseif (isset($_POST['add_sponsor'])) {
        $stmt = $pdo->prepare("INSERT INTO Sponsor (sponsor_name, sponsorship) VALUES (?, ?)");
        $stmt->execute([$_POST['sponsor_name'], $_POST['sponsorship']]);
    } elseif (isset($_POST['add_owner'])) {
        $stmt = $pdo->prepare("INSERT INTO Owner (company_name, amount_spent) VALUES (?, ?)");
        $stmt->execute([$_POST['company_name'], $_POST['amount_spent']]);
    }
    
    // Update operations
    elseif (isset($_POST['update_team'])) {
        $stmt = $pdo->prepare("UPDATE Team SET team_name = ?, matches_lost = ?, matches_won = ?, coach_name = ? WHERE team_id = ?");
        $stmt->execute([$_POST['team_name'], $_POST['matches_lost'], $_POST['matches_won'], $_POST['coach_name'], $_POST['team_id']]);
    } elseif (isset($_POST['update_player'])) {
        $stmt = $pdo->prepare("UPDATE Player SET name = ?, nationality = ?, bowling_average = ?, batting_average = ?, wickets_taken = ?, highest_score = ?, dob = ?, is_captain = ?, team_id = ? WHERE player_id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['nationality'],
            $_POST['bowling_average'],
            $_POST['batting_average'],
            $_POST['wickets_taken'],
            $_POST['highest_score'],
            $_POST['dob'],
            isset($_POST['is_captain']) ? 1 : 0,
            $_POST['team_id'],
            $_POST['player_id']
        ]);
    } elseif (isset($_POST['update_sponsor'])) {
        $stmt = $pdo->prepare("UPDATE Sponsor SET sponsor_name = ?, sponsorship = ? WHERE sponsor_id = ?");
        $stmt->execute([$_POST['sponsor_name'], $_POST['sponsorship'], $_POST['sponsor_id']]);
    } elseif (isset($_POST['update_owner'])) {
        $stmt = $pdo->prepare("UPDATE Owner SET company_name = ?, amount_spent = ? WHERE owner_id = ?");
        $stmt->execute([$_POST['company_name'], $_POST['amount_spent'], $_POST['owner_id']]);
    }
    
    // Delete operations
    elseif (isset($_POST['delete_team'])) {
        $stmt = $pdo->prepare("DELETE FROM Team WHERE team_id = ?");
        $stmt->execute([$_POST['team_id']]);
    } elseif (isset($_POST['delete_player'])) {
        $stmt = $pdo->prepare("DELETE FROM Player WHERE player_id = ?");
        $stmt->execute([$_POST['player_id']]);
    } elseif (isset($_POST['delete_sponsor'])) {
        $stmt = $pdo->prepare("DELETE FROM Sponsor WHERE sponsor_id = ?");
        $stmt->execute([$_POST['sponsor_id']]);
    } elseif (isset($_POST['delete_owner'])) {
        $stmt = $pdo->prepare("DELETE FROM Owner WHERE owner_id = ?");
        $stmt->execute([$_POST['owner_id']]);
    }
}

// Fetch data
$teams = fetchAll($pdo, "SELECT * FROM Team");
$players = fetchAll($pdo, "SELECT p.*, t.team_name FROM Player p LEFT JOIN Team t ON p.team_id = t.team_id");
$sponsors = fetchAll($pdo, "SELECT * FROM Sponsor");
$owners = fetchAll($pdo, "SELECT * FROM Owner");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPL Database Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center mb-8">IPL Database Management System</h1>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Login and Sign-up Forms -->
        <div class="flex justify-center space-x-8">
            <!-- Login Form -->
            <div class="w-full max-w-md">
                <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                    <h2 class="text-2xl font-bold mb-6">Login</h2>
                    <form action="" method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="login-username">
                                Username
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="login-username" name="username" type="text" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="login-password">
                                Password
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="login-password" name="password" type="password" required>
                        </div>
                        <div class="flex items-center justify-between">
                            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="login">
                                Sign In
                            </button>
                        </div>
                    </form>
                    <?php if (isset($login_error)): ?>
                        <p class="text-red-500 text-xs italic mt-4"><?= $login_error ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sign-up Form -->
            <div class="w-full max-w-md">
                <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                    <h2 class="text-2xl font-bold mb-6">Sign Up</h2>
                    <form action="" method="POST">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="signup-username">
                                Username
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="signup-username" name="username" type="text" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="signup-password">
                                Password
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="signup-password" name="password" type="password" required>
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="signup-confirm-password">
                                Confirm Password
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="signup-confirm-password" name="confirm_password" type="password" required>
                        </div>
                        <div class="flex items-center justify-between">
                            <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="signup">
                                Sign Up
                            </button>
                        </div>
                    </form>
                    <?php if (isset($signup_error)): ?>
                        <p class="text-red-500 text-xs italic mt-4"><?= $signup_error ?></p>
                    <?php endif; ?>
                    <?php if (isset($signup_success)): ?>
                        <p class="text-green-500 text-xs italic mt-4"><?= $signup_success ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Navigation -->
        <div class="flex justify-between items-center mb-8">
            <div class="flex space-x-4">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" onclick="showSection('teams')">Teams</button>
                <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded" onclick="showSection('players')">Players</button>
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded" onclick="showSection('sponsors')">Sponsors</button>
                <button class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" onclick="showSection('owners')">Owners</button>
            </div>
            <a href="?logout" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Logout</a>
        </div>

        <!-- Teams Section -->
        <div id="teams" class="section">
            <h2 class="text-2xl font-bold mb-4">Manage Teams</h2>
            <form action="" method="POST" class="mb-8 bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <input type="hidden" name="team_id" id="team_id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="team_name">Team Name</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="team_name" name="team_name" type="text" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="matches_lost">Matches Lost</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="matches_lost" name="matches_lost" type="number" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="matches_won">Matches Won</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="matches_won" name="matches_won" type="number" req class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="coach_name">Coach Name</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="coach_name" name="coach_name" type="text" req class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="add_team" id="teamSubmitBtn">Add Team</button>
                </div>
            </form>
            <table class="w-full bg-white shadow-md rounded mb-4">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Team Name</th>
                        <th class="py-3 px-6 text-left">Matches Lost</th>
                        <th class="py-3 px-6 text-left">Matches Won</th>
                        <th class="py-3 px-6 text-left">Coach Name</th>
                        <th class="py-3 px-6 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php foreach ($teams as $team): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap"><?= htmlspecialchars($team['team_name']) ?></td>
                        <td class="py-3 px-6 text-left"><?= $team['matches_lost'] ?></td>
                        <td class="py-3 px-6 text-left"><?= $team['matches_won'] ?></td>
                        <td class="py-3 px-6 text-left"><?= htmlspecialchars($team['coach_name']) ?></td>
                        <td class="py-3 px-6 text-left">
                            <button onclick="editTeam(<?= htmlspecialchars(json_encode($team)) ?>)" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded mr-2">Edit</button>
                            <form action="" method="POST" class="inline">
                                <input type="hidden" name="team_id" value="<?= $team['team_id'] ?>">
                                <button type="submit" name="delete_team" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded" onclick="return confirm('Are you sure you want to delete this team?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Players Section -->
        <div id="players" class="section hidden">
            <h2 class="text-2xl font-bold mb-4">Manage Players</h2>
            <form action="" method="POST" class="mb-8 bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <input type="hidden" name="player_id" id="player_id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Player Name</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" name="name" type="text" req class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="nationality">Nationality</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="nationality" name="nationality" type="text" req class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="bowling_average">Bowling Average</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="bowling_average" name="bowling_average" type="number" step="0.01" req class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="batting_average">Batting Average</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="batting_average" name="batting_average" type="number" step="0.01" req class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="wickets_taken">Wickets Taken</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="wickets_taken" name="wickets_taken" type="number" req class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="highest_score">Highest Score</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="highest_score" name="highest_score" type="number" req class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="dob">Date of Birth</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="dob" name="dob" type="date" req class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="is_captain">Is Captain</label>
                    <input class="mr-2 leading-tight" type="checkbox" id="is_captain" name="is_captain">
                    <span class="text-sm">Yes</span>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="team_id">Team</label>
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="team_id" name="team_id" required>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?= $team['team_id'] ?>"><?= htmlspecialchars($team['team_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="add_player" id="playerSubmitBtn">Add Player</button>
                </div>
            </form>
            <table class="w-full bg-white shadow-md rounded mb-4">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Name</th>
                        <th class="py-3 px-6 text-left">Nationality</th>
                        <th class="py-3 px-6 text-left">Bowling Avg</th>
                        <th class="py-3 px-6 text-left">Batting Avg</th>
                        <th class="py-3 px-6 text-left">Wickets</th>
                        <th class="py-3 px-6 text-left">Highest Score</th>
                        <th class="py-3 px-6 text-left">DOB</th>
                        <th class="py-3 px-6 text-left">Captain</th>
                        <th class="py-3 px-6 text-left">Team</th>
                        <th class="py-3 px-6 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php foreach ($players as $player): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap"><?= htmlspecialchars($player['name']) ?></td>
                        <td class="py-3 px-6 text-left"><?= htmlspecialchars($player['nationality']) ?></td>
                        <td class="py-3 px-6 text-left"><?= $player['bowling_average'] ?></td>
                        <td class="py-3 px-6 text-left"><?= $player['batting_average'] ?></td>
                        <td class="py-3 px-6 text-left"><?= $player['wickets_taken'] ?></td>
                        <td class="py-3 px-6 text-left"><?= $player['highest_score'] ?></td>
                        <td class="py-3 px-6 text-left"><?= $player['dob'] ?></td>
                        <td class="py-3 px-6 text-left"><?= $player['is_captain'] ? 'Yes' : 'No' ?></td>
                        <td class="py-3 px-6 text-left"><?= htmlspecialchars($player['team_name']) ?></td>
                        <td class="py-3 px-6 text-left">
                            <button onclick="editPlayer(<?= htmlspecialchars(json_encode($player)) ?>)" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded mr-2">Edit</button>
                            <form action="" method="POST" class="inline">
                                <input type="hidden" name="player_id" value="<?= $player['player_id'] ?>">
                                <button type="submit" name="delete_player" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded" onclick="return confirm('Are you sure you want to delete this player?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Sponsors Section -->
        <div id="sponsors" class="section hidden">
            <h2 class="text-2xl font-bold mb-4">Manage Sponsors</h2>
            <form action="" method="POST" class="mb-8 bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <input type="hidden" name="sponsor_id" id="sponsor_id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="sponsor_name">Sponsor Name</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="sponsor_name" name="sponsor_name" type="text" req class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="sponsorship">Sponsorship Amount</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="sponsorship" name="sponsorship" type="number" step="0.01" req class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="add_sponsor" id="sponsorSubmitBtn">Add Sponsor</button>
                </div>
            </form>
            <table class="w-full bg-white shadow-md rounded mb-4">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Sponsor Name</th>
                        <th class="py-3 px-6 text-left">Sponsorship Amount</th>
                        <th class="py-3 px-6 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php foreach ($sponsors as $sponsor): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap"><?= htmlspecialchars($sponsor['sponsor_name']) ?></td>
                        <td class="py-3 px-6 text-left"><?= number_format($sponsor['sponsorship'], 2) ?></td>
                        <td class="py-3 px-6 text-left">
                            <button onclick="editSponsor(<?= htmlspecialchars(json_encode($sponsor)) ?>)" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded mr-2">Edit</button>
                            <form action="" method="POST" class="inline">
                                <input type="hidden" name="sponsor_id" value="<?= $sponsor['sponsor_id'] ?>">
                                <button type="submit" name="delete_sponsor" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded" onclick="return confirm('Are you sure you want to delete this sponsor?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Owners Section -->
        <div id="owners" class="section hidden">
            <h2 class="text-2xl font-bold mb-4">Manage Owners</h2>
            <form action="" method="POST" class="mb-8 bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                <input type="hidden" name="owner_id" id="owner_id">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="company_name">Company Name</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="company_name" name="company_name" type="text" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="amount_spent">Amount Spent</label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="amount_spent" name="amount_spent" type="number" step="0.01" required>
                </div>
                <div class="flex items-center justify-between">
                    <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit" name="add_owner" id="ownerSubmitBtn">Add Owner</button>
                </div>
            </form>
            <table class="w-full bg-white shadow-md rounded mb-4">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Company Name</th>
                        <th class="py-3 px-6 text-left">Amount Spent</th>
                        <th class="py-3 px-6 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    <?php foreach ($owners as $owner): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap"><?= htmlspecialchars($owner['company_name']) ?></td>
                        <td class="py-3 px-6 text-left"><?= number_format($owner['amount_spent'], 2) ?></td>
                        <td class="py-3 px-6 text-left">
                            <button onclick="editOwner(<?= htmlspecialchars(json_encode($owner)) ?>)" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-2 rounded mr-2">Edit</button>
                            <form action="" method="POST" class="inline">
                                <input type="hidden" name="owner_id" value="<?= $owner['owner_id'] ?>">
                                <button type="submit" name="delete_owner" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded" onclick="return confirm('Are you sure you want to delete this owner?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Chart Section -->
        <div class="mt-8">
            <h2 class="text-2xl font-bold mb-4">Team Performance</h2>
            <canvas id="teamPerformanceChart"></canvas>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.classList.add('hidden');
            });
            document.getElementById(sectionId).classList.remove('hidden');
        }

        function editTeam(team) {
            document.getElementById('team_id').value = team.team_id;
            document.getElementById('team_name').value = team.team_name;
            document.getElementById('matches_lost').value = team.matches_lost;
            document.getElementById('matches_won').value = team.matches_won;
            document.getElementById('coach_name').value = team.coach_name;
            document.getElementById('teamSubmitBtn').name = 'update_team';
            document.getElementById('teamSubmitBtn').textContent = 'Update Team';
        }

        function editPlayer(player) {
            document.getElementById('player_id').value = player.player_id;
            document.getElementById('name').value = player.name;
            document.getElementById('nationality').value = player.nationality;
            document.getElementById('bowling_average').value = player.bowling_average;
            document.getElementById('batting_average').value = player.batting_average;
            document.getElementById('wickets_taken').value = player.wickets_taken;
            document.getElementById('highest_score').value = player.highest_score;
            document.getElementById('dob').value = player.dob;
            document.getElementById('is_captain').checked = player.is_captain == 1;
            document.getElementById('team_id').value = player.team_id;
            document.getElementById('playerSubmitBtn').name = 'update_player';
            document.getElementById('playerSubmitBtn').textContent = 'Update Player';
        }

        function editSponsor(sponsor) {
            document.getElementById('sponsor_id').value = sponsor.sponsor_id;
            document.getElementById('sponsor_name').value = sponsor.sponsor_name;
            document.getElementById('sponsorship').value = sponsor.sponsorship;
            document.getElementById('sponsorSubmitBtn').name = 'update_sponsor';
            document.getElementById('sponsorSubmitBtn').textContent = 'Update Sponsor';
        }

        function editOwner(owner) {
            document.getElementById('owner_id').value = owner.owner_id;
            document.getElementById('company_name').value = owner.company_name;
            document.getElementById('amount_spent').value = owner.amount_spent;
            document.getElementById('ownerSubmitBtn').name = 'update_owner';
            document.getElementById('ownerSubmitBtn').textContent = 'Update Owner';
        }

        // Chart.js
        const ctx = document.getElementById('teamPerformanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($teams, 'team_name')) ?>,
                datasets: [{
                    label: 'Matches Won',
                    data: <?= json_encode(array_column($teams, 'matches_won')) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }, {
                    label: 'Matches Lost',
                    data: <?= json_encode(array_column($teams, 'matches_lost')) ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Team Performance'
                    }
                }
            }
        });
    </script>
</body>
</html>