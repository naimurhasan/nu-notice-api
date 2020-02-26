<?php

$mysqli = mysqli_connect("localhost","root","","nu_notice");

// Check connection
if (mysqli_connect_errno()){
  throw new Exception("Failed to connect to MySQL: " . mysqli_connect_error(), 1);	
}
