Example:

<?php

include('wiringpi.php');
include('wiring-lcd.php');

$LCD = new LCD();

$lcdHandler = $LCD->lcdInit(4,20,4,8,9,4,5,6,7,0,0,0,0);

sleep(2);

$LCD->lcdPosition($lcdHandler, 0,0);
$LCD->lcdPuts($lcdHandler, "Hello, World");