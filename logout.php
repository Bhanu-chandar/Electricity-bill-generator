<?php

require_once __DIR__ . '/includes/auth.php';

logout();
setFlashMessage('success', 'You have been logged out successfully');
header('Location: /index.php');
exit;
?>
