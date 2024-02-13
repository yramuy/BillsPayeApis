<?php
/**
 * Handling database connection
 *
 * @author arun kumar
 * @link URL Tutorial link
 */
class DbConnect
{
  private $conn;

  function __construct()
  {
  }

  function connect()
  {

    // $host="192.168.235.39";
    // // $host="localhost";
    // $dbuser="erpposh";
    // $dbpassword="erpposh";
    // $database="erp_posh";

    $host = "193.203.184.4";
    $dbuser = "u378733873_Billspaye";
    $dbpassword = "Billspaye@2024";
    $database = "u378733873_billspaye";
    $conn = mysqli_connect($host, $dbuser, $dbpassword, $database);
    return $conn;
  }


}
?>