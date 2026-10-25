<?php

$result = mail(
    'your-email@gmail.com',
    'Test Email',
    'Hello from XAMPP'
);

var_dump($result);