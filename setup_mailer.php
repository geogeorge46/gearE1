<?php
// Create PHPMailer directory if it doesn't exist
$mailerDir = __DIR__ . '/includes/PHPMailer';
if (!file_exists($mailerDir)) {
    mkdir($mailerDir . '/src', 0777, true);
}

// Files to download
$files = [
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php',
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
    'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php'
];

// Download each file
foreach ($files as $filename => $url) {
    $content = file_get_contents($url);
    if ($content === false) {
        die("Failed to download $filename");
    }
    
    if (file_put_contents($mailerDir . '/src/' . $filename, $content) === false) {
        die("Failed to save $filename");
    }
}

echo "PHPMailer setup completed successfully!";
?>
