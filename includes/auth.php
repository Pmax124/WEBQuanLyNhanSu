<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}