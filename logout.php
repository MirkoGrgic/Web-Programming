<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

sign_out();
session_start();
flash('success', 'Uspješno ste odjavljeni.');
redirect('login.php');
