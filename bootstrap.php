<?php
require_once __DIR__ . '/vendor/autoload.php';

if(!ini_get('date.timezone')) {
	ini_set('date.timezone', 'Europe/Copenhagen');
}