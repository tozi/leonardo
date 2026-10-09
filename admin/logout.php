<?php
require_once dirname(__DIR__) . '/includes/functions.php';
start_session();
session_destroy();
header('Location: login.php');
exit;
