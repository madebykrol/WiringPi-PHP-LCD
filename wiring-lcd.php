<?php

class LCD {


  protected $lcds = array();
  protected $initFlag = false;

  const LCD_CLEAR   = 0x01;
  const LCD_HOME    = 0x02;
  const LCD_ENTRY   = 0x04;
  
  const LCD_ON_OFF  = 0x08;
  const LCD_CDSHIFT = 0x10;

  const LCD_FUNC    = 0x20;
  const LCD_CGRAM   = 0x40;
  const LCD_DGRAM   = 0x80;

  const LCD_ENTRY_SH   = 0x01;
  const LCD_ENTRY_ID   = 0x02;

  const LCD_ON_OFF_B   = 0x01;
  const LCD_ON_OFF_C   = 0x02;
  const LCD_ON_OFF_D   = 0x04;

  const LCD_FUNC_F     = 0x04;
  const LCD_FUNC_N     = 0x08;
  const LCD_FUNC_DL    = 0x10;

  const LCD_CDSHIFT_RL = 0x04;
  
  const OUTPUT     = 0;
  const INPUT      = 1;
  const PWM_OUTPUT = 2;
  
  const MAX_LCDS = 8;

  protected function strobe(LCDDisplay $lcd) {

    digitalWrite($lcd->strbPin, 1); delayMicroseconds(50);
    digitalWrite($lcd->strbPin, 0); delayMicroseconds(50);

  }


  protected function sendDataCmd(LCDDisplay $lcd, $data) {

    $i = 0;
    $d4 = 0;


    if($lcd->bits == 4) {

      $d4 = ($data >> 4) & 0x0F;

      for($i = 0; $i < 4; ++$i) {

	digitalWrite($lcd->dataPins[$i], ($d4 & 1));

	$d4 >>= 1;

      }

      $this->strobe($lcd);

      $d4 = $data & 0x0F;

      for($i = 0; $i < 4; ++$i) {
	digitalWrite($lcd->dataPins[$i], ($d4 & 1));
	$d4 >>= 1;
      }

    } else {

      for($i = 0; $i < 8; ++$i ) {
	
	digitalWrite($lcd->dataPins[$i], ($data & 1));
	$data >>= 1;
      
      }
    
    }

    $this->strobe($lcd);
  }


  protected function putCommand(LCDDisplay $lcd, $command) {

    digitalWrite($lcd->rsPin, 0);
    $this->sendDataCmd($lcd, $command);

  }


  protected function put4Command(LCDDisplay $lcd, $command) {


    $i;

    digitalWrite($lcd->rsPin, 0);
    for($i = 0; $i < 4; ++$i) {
      digitalWrite($lcd->dataPins[$i], ($command&1));
      $command >>= 1;
    }

    $this->strobe($lcd);

  }

  public function lcdHome($fd) {

    $lcd = $this->lcds[$fd];
    $this->putCommand($lcd, LCD::LCD_HOME);

  }


  public function lcdClear($fd) {

    $lcd = $this->lcds[$fd];
    $this->putCommand($lcd, LCD::LCD_CLEAR);

  }


  public function lcdPosition($fd, $x, $y) {

    $rowOff = array(0x00, 0x40, 0x14, 0x54);
    $lcd = $this->lcds[$fd];

    $this->putCommand($lcd, $x +(LCD::LCD_DGRAM | $rowOff[$y]));

  }

  public function lcdPutchar ( $fd, $data ) {

    $lcd = $this->lcds[$fd];

    digitalWrite($lcd->rsPin, 1);
    $this->sendDataCmd($lcd, $data);

  }


  public function lcdPuts($fd, $string) {

    foreach(str_split($string) as $char) {
      $this->lcdPutchar($fd, $char);
    }
    
  }


  public function lcdPrintf() {
    // Not implemented..
  }

  
  /**
   * 
   * @return int handle.
   * 
   */
  public function lcdInit($rows, $cols, $bits, $rs, $strb,
			  $d0, $d1, $d2, $d3, $d4, $d5, $d6, $d7) {

    
    $i = 0;
    $lcdFd = -1;
    $lcd = null;

    if(!$this->initFlag) {
      $this->initFlag = true;
      for($i = 0; $i < LCD::MAX_LCDS; ++$i) {
	$lcds[$i] = NULL;
      }
    }

    if(! (($bits == 4) || ($bits == 8)) ){
      
      return -1;

    } 

    if(($rows < 0) || ($rows > 20)) {
      return -1;
    }

    if(($cols < 0) || ($cols > 20)) {
      return -1;
    }


    // Create our new LCDObjec.
    for($i = 0; $i < LCD::MAX_LCDS; ++$i) {
      if(!isset($this->lcds[$i])) {
	$lcdFd = $i;
	break;
      }
    }

    if($lcdFd == -1) {
      return -1;
    }

    $lcd = new LCDDisplay($rows, $cols, $bits, $rs, $strb, 
			  $d0, $d1, $d2, $d3, $d4, $d5, $d6, $d7);

    $this->lcds[$lcdFd] = &$lcd;

    digitalWrite($lcd->rsPin, 0); pinMode($lcd->rsPin, LCD::OUTPUT);
    digitalWrite($lcd->strbPin, 0); pinMode($lcd->strbPin, LCD::OUTPUT);


    for($i = 0; $i < $bits; ++$i) {

      digitalWrite($lcd->dataPins[$i], 0);
      digitalWrite($lcd->dataPins[$i], LCD::OUTPUT);

    }

    delay(35);


    if($bits == 4) {

      $func = LCD::LCD_FUNC | LCD::LCD_FUNC_DL;
      $this->put4Command($lcd, $func >> 4); delay(35);
      $this->put4Command($lcd, $func >> 4); delay(35);
      $this->put4Command($lcd, $func >> 4); delay(35);

      $func = LCD::LCD_FUNC;
      $this->put4Command($lcd, $func >> 4); delay(35);
      
    } else {

      $func = LCD::LCD_FUNC | LCD::LCD_FUNC_DL;
      $this->putCommand($lcd, $func); delay(35);
      $this->putCommand($lcd, $func); delay(35);
      $this->putCommand($lcd, $func); delay(35);

    }


    if($lcd->rows > 1) {
      $func |= LCD::LCD_FUNC_N;
      $this->putCommand($lcd, $func); delay(35);
    }


    $this->putCommand($lcd, LCD::LCD_ON_OFF | LCD::LCD_ON_OFF_D); delay(2);
    $this->putCommand($lcd, LCD::LCD_ENTRY | LCD::LCD_ENTRY_ID); delay(2);
    $this->putCommand($lcd, LCD::LCD_CDSHIFT | LCD::LCD_CDSHIFT_RL); delay(2);
    $this->putCommand($lcd, LCD::LCD_CLEAR); delay(5);

    return $lcdFd;
    
  }


  
  

}



class LCDDisplay {

  public $bits = 0;
  public $rows = 0;
  public $cols = 0;

  public $rsPin = 0;
  public $strbPin = 0;
 
  public $dataPins = array(0,0,0,0,0,0,0,0);


  public function __construct($rows, $cols, $bits, $rs, $strb,
			      $d0, $d1, $d2, $d3, $d4, $d5, $d6, $d7) {


    $this->rows = $rows;
    $this->cols = $cols;
    $this->bits = $bits;
    $this->rsPin = $rs; 
    $this->strbPin = $strb;
    
    $this->dataPins[0] = $d0;
    $this->dataPins[1] = $d1;
    $this->dataPins[2] = $d2;
    $this->dataPins[3] = $d3;
    $this->dataPins[4] = $d4;
    $this->dataPins[5] = $d5;
    $this->dataPins[6] = $d6;
    $this->dataPins[7] = $d7;
    

  }


}
