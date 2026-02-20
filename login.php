<?php
require_once 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'All fields are required';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit();
            } else {
                $error = 'Invalid credentials';
            }
        } else {
            $error = 'Invalid credentials';
        }
        $stmt->close();
    }
}
?>

<div class="auth-container">
    <div class="auth-icon">🔐</div>
    <h2>Welcome Back</h2>
    <p class="auth-subtitle">Sign in to access your notes and stay organized</p>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">👤 Username or Email</label>
            <input type="text" id="username" name="username" placeholder="Enter your username or email" required>
        </div>
        
        <div class="form-group">
            <label for="password">🔒 Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        
        <div class="form-options">
            <label class="checkbox-label">
                <input type="checkbox" name="remember"> Remember me
            </label>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">🚀 Sign In</button>
    </form>
    
    <p class="auth-link">Don't have an account? <a href="register.php">Create one now</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>
