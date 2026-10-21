<?php
require_once __DIR__ . '/app/auth.php';
logout();
redirect(base_url('login.php'));
