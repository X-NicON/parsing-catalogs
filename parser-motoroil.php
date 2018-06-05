<?php
  require 'simple_html_dom.php';
  require 'utils.php';
  require 'models/model-motoroil.php';
  require 'xml-generator.php';

  set_time_limit(0);
  ini_set('memory_limit', '512M');

  Utils::logMessage('Start '.date('d-m-Y')."\n", 'StartParse');

  $run = new Motoroil;
  $DataArr = $run->parse_site();