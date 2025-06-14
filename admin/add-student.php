<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php include 'header.php'; ?>
<?php include '../includes/db.php'; ?>

<h3>Add New Student</h3>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize inputs
    $first_name      = trim($_POST['first_name']);
    $middle_name     = trim($_POST['middle_name']);
    $last_name       = trim($_POST['last_name']);
    $date_of_birth   = $_POST['date_of_birth'];
    $address         = trim($_POST['address']);
    $contact_number  = trim($_POST['contact_number']);
    $email           = trim($_POST['email']);
    $program         = trim($_POST['program']);
    $enrollment_year = isset($_POST['enrollment_year']);
    $semester        = $_POST['semester'];
    $username        = trim($_POST['username']);
    $password        = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Validation check
    if (empty($first_name) || empty($last_name) || empty($email) || empty($program) || empty($username) || empty($_POST['password'])) {
        echo "<p class='text-danger'>Please fill in all required fields.</p>";
    } else {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $check_email->bind_param('s', $email);
        $check_email->execute();
        $check_email->store_result();
        if ($check_email->num_rows > 0) {
            echo "<p class='text-danger'>Email already exists. Please use a different email.</p>";
            $check_email->close();
        } else {
            $check_email->close();

            // Check if username already exists in users table
            $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_username->bind_param('s', $username);
            $check_username->execute();
            $check_username->store_result();
            if ($check_username->num_rows > 0) {
                echo "<p class='text-danger'>Username already exists. Please choose a different username.</p>";
                $check_username->close();
            } else {
                $check_username->close();

                $sql = "INSERT INTO students 
                        (first_name, middle_name, last_name, date_of_birth, address, contact_number, email, program, enrollment_year, semester, username, password, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    echo "<p class='text-danger'>Prepare failed: " . $conn->error . "</p>";
                } else {
                    $stmt->bind_param(
                        "ssssssssssss",
                        $first_name, $middle_name, $last_name, $date_of_birth, $address, $contact_number,
                        $email, $program, $enrollment_year, $semester, $username, $password
                    );
                    if ($stmt->execute()) {
                        // Get the new student's ID
                        $new_student_id = $conn->insert_id;

                        // Prepare user insert (use the same username and password, role = 'student')
                        $user_stmt = $conn->prepare("INSERT INTO users (username, password, role, student_id, created_at) VALUES (?, ?, 'student', ?, NOW())");
                        $user_stmt->bind_param('ssi', $username, $password, $new_student_id);

                        if ($user_stmt->execute()) {
                            header("Location: manage-students.php?success=1");
                            exit;
                        } else {
                            echo "<p class='text-danger'>User creation failed: " . $user_stmt->error . "</p>";
                        }
                        $user_stmt->close();
                    } else {
                        echo "<p class='text-danger'>Execute failed: " . $stmt->error . "</p>";
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>

<!-- 🧾 Student Form -->
<!-- This also echo the entered inputs if there are duplicate entries -->
<form method="POST" action="">
    <div class="mb-3">
        <label>First Name:</label>
        <input type="text" name="first_name" class="form-control" required placeholder="e.g., Juan Han"
            value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>">
    </div>
    <div class="mb-3">
        <label>Middle Name:</label>
        <input type="text" name="middle_name" class="form-control" required placeholder="e.g., Andrade"
            value="<?= isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : '' ?>">
    </div>
    <div class="mb-3">
        <label>Last Name:</label>
        <input type="text" name="last_name" class="form-control" required placeholder="e.g., Dela Cruz"
            value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>">
    </div>
    <div class="mb-3">
        <label>Date of Birth:</label>
        <input type="date" name="date_of_birth" class="form-control" required title="Date of Birth must be in YYYY-MM-DD format"
            value="<?= isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : '' ?> ">
    </div>
    <div class="mb-3">
        <label>Address:</label>
        <textarea name="address" class="form-control" placeholder="e.g., 123 Summit Avenue" required><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
    </div>
    <div class="mb-3">
        <label>Contact Number:</label>
        <input type="tel" name="contact_number" class="form-control" placeholder="e.g., 09123456789" pattern="^09\d{9}$" maxlength="11"title="Contact number must start with 09 and be 11 digits long"
               value="<?= isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : '' ?>">
    </div>
    <div class="mb-3">
        <label>Email:</label>
        <input type="email" name="email" class="form-control" required placeholder="e.g., JuanDelaCruz@gmail.com"
            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
    </div>
    <div class="mb-3">
        <label for="program">Program:</label>
        <select name="program" id="program" class="form-control" required>
            <option value="">-- Select Program --</option>
            <option value="BSCpE">BSCpE – Bachelor of Science in Computer Engineering</option>
            <option value="BSECE">BSECE – Bachelor of Science in Electronics Engineering</option>
            <option value="BSEE">BSEE – Bachelor of Science in Electrical Engineering</option>
            <option value="BSME">BSME – Bachelor of Science in Mechanical Engineering</option>
            <option value="BSCE">BSCE – Bachelor of Science in Civil Engineering</option>
            <option value="BSCS-SE">BSCS-SE – Bachelor of Science in Computer Science major in Software Engineering</option>
            <option value="BSCS-DS">BSCS-DS – Bachelor of Science in Computer Science major in Data Science</option>
            <option value="BSIT-BA">BSIT-BA – Bachelor of Science in Information Technology major in Business Analytics</option>
            <option value="BSIT-IB">BSIT-IB – Bachelor of Science in Information Technology major in Innovation and Business</option>
            <option value="BSIT-AGD">BSIT-AGD – Bachelor of Science in Information Technology major in Animation and Game Development</option>
            <option value="BSIT-WMA">BSIT-WMA – Bachelor of Science in Information Technology major in Web and Mobile Applications</option>
            <option value="BSIT-CY">BSIT-CY – Bachelor of Science in Information Technology major in Cybersecurity</option>
            <option value="BMMA">BMMA – Bachelor of Multimedia Arts</option>
        </select>
    </div>
    <div class="mb-3">
        <label>Enrollment Year:</label>
        <input type="number" name="enrollment_year" min="2023" max="<?= date('Y') ?>" class="form-control" placeholder="e.g., 2023" required
            value="<?= isset($_POST['enrollment_year']) ? htmlspecialchars($_POST['enrollment_year']) : '' ?>">
    </div>
    <div class="mb-3">
        <label>Semester:</label>
        <select name="semester" class="form-control">
            <option value="">-- Select Year --</option>
            <option value="1st" <?= (isset($_POST['semester']) && $_POST['semester'] == '1st') ? 'selected' : '' ?>>1st Year</option>
            <option value="2nd" <?= (isset($_POST['semester']) && $_POST['semester'] == '2nd') ? 'selected' : '' ?>>2nd Year</option>
            <option value="3rd" <?= (isset($_POST['semester']) && $_POST['semester'] == '3rd') ? 'selected' : '' ?>>3rd Year</option>
            <option value="4th" <?= (isset($_POST['semester']) && $_POST['semester'] == '4th') ? 'selected' : '' ?>>4th Year</option>
            <option value="5th" <?= (isset($_POST['semester']) && $_POST['semester'] == '5th') ? 'selected' : '' ?>>5th Year</option>
            <option value="Summer" <?= (isset($_POST['semester']) && $_POST['semester'] == 'Summer') ? 'selected' : '' ?>>Summer</option>
        </select>
    </div>
    <hr>
    <div class="mb-3">
        <label>Username:</label>
        <input type="text" name="username" class="form-control" required
            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
    </div>
    <div class="mb-3">
        <label>Password:</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Add Student</button>
</form>

<?php include 'footer.php'; ?>
