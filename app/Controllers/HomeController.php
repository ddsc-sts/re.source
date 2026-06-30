<?php

class HomeController
{
    public static function index(): void
    {
        require_once __DIR__ . '/../Views/home/index.php';
    }
}