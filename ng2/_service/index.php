<?php
defined('APP_ENV')
|| define('APP_ENV', (getenv('APP_ENV') ? getenv('APP_ENV') : 'prod'));

define('WEB_PATH', __DIR__);

if (APP_ENV == 'dev') {
    require '../../env/app_dev.php';
} else {
    require '../../env/app.php';
}

