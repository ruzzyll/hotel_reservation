<?php
require_once __DIR__ . '/config/bootstrap.php';

if (is_logged_in()) {
    $role = current_user()['role'];
    if ($role === 'admin') {
        redirect('/admin/index.php');
    }
    if ($role === 'customer') {
        redirect('/customer/index.php');
    }
    redirect('/staff/index.php');
}

redirect('/auth/login.php');
