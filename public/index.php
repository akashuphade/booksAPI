<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

ini_set('max_execution_time', '0');

//Include required files
require '../vendor/autoload.php';
require '../config/db.php';
require 'functions.php';
require '../src/Routes/bookAccess.php';

$app->run();