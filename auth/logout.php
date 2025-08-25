<?php
require_once '../includes/functions.php';

session_destroy();
session_write_close();
redirect('auth/login.php');
