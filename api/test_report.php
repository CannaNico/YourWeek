<?php
require_once __DIR__ . '/../includes/db_connection.php';
session_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION['user_id'] = 6;
$_SESSION['user_role'] = 'paziente';
$_GET['action'] = 'get_patient_dashboard';
include 'reports.php';
