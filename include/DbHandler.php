<?php
/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author arun kumar,Pavan Kumar,Ramu,Sreekanth
 * @link URL Tutorial link
 */
ini_set("allow_url_fopen", 1);

// define(APPROVE, 2);

use Twilio\Rest\Client;

class DbHandler
{
	private $conn;

	function __construct()
	{
		require_once dirname(__FILE__) . '/DbConnect.php';
		require_once dirname(__FILE__) . '/SmsService.php';
		require_once dirname(__FILE__) . '/PasswordHash.php';
		require_once dirname(__FILE__) . '/WhatsappService.php';
		require_once '../vendor/autoload.php';
		// require_once '../vendor/twilio/sdk/src/Twilio/Rest/Client.php';


		// opening db connection
		date_default_timezone_set('UTC');
		$db = new DbConnect();
		$this->conn = $db->connect();

		// echo $this->conn;die();
		$this->apiUrl = 'https://www.whatsappapi.in/api';
	}

	/************function for check is valid api key*******************************/
	function isValidApiKey($token)
	{
		//echo 'SELECT userId FROM registerCustomers WHERE apiToken="'.$token.'"';exit;
		$query = 'SELECT userId FROM registerCustomers WHERE apiToken="' . $token . '"'; // AND password = $userPass";
		$result = mysqli_query($this->conn, $query);
		$num = mysqli_num_rows($result);
		return $num;
	}

	/************function for check is valid api key*******************************/
	function isValidSessionToken($token, $user_id)
	{
		//echo 'SELECT userId FROM registerCustomers WHERE apiToken="'.$token.'"';exit;
		$query = 'SELECT * FROM erp_user_token WHERE userid = "' . $user_id . '" and session_token ="' . $token . '"'; // AND password = $userPass";
		$result = mysqli_query($this->conn, $query);
		$num = mysqli_num_rows($result);
		return $num;
	}
	/**
	 * Generating random Unique MD5 String for user Api key
	 */
	function generateApiKey()
	{
		return md5(uniqid(rand(), true));
	}
	/** Password Encryption Algorithim*/
	function encrypt($str)
	{
		$key = 'grubvanapp1#20!8';
		$block = mcrypt_get_block_size('rijndael_128', 'ecb');
		$pad = $block - (strlen($str) % $block);
		$str .= str_repeat(chr($pad), $pad);
		$rst = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_ECB, str_repeat("\0", 16)));
		return str_ireplace('+', '-', $rst);
	}

	/************function for check is valid api key*******************************/

	function generateSessionToken($user_id)
	{
		$data = array();
		$token = $this->generateApiKey();
		$query = "SELECT * FROM erp_user_token WHERE userid = $user_id";
		$count = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($count) > 0) {
			$row = mysqli_fetch_assoc($count);
			$token_userid = $row['userid'];
			if ($token_userid == $user_id) {
				$updatesql = "UPDATE erp_user_token SET session_token='$token' WHERE userid=$user_id";
				if ($result2 = mysqli_query($this->conn, $updatesql)) {
					$data['session_token'] = $token;
					$data['status'] = 1;
				} else {
					$data['status'] = 0;
				}
			} else {
				$data['status'] = 0;
			}
		}
		return $data;
	}

	function userLogin($username, $password)
	{
		$data = array();
		$query = "SELECT * FROM tbl_user WHERE (email ='$username' OR mobile_number = '$username')";
		$count = mysqli_query($this->conn, $query);
		if (mysqli_num_rows($count) > 0) {
			$row = mysqli_fetch_assoc($count);
			$user_password = $row['user_password'];
			$verify = password_verify($password, $user_password);
			if ($verify) {
				$data['user_name'] = $row['user_name'];
				$data['user_id'] = $row['id'];
				if ($row['user_role_id'] == 1) {
					$data['role_name'] = 'Admin';
				}
				$data['user_role_id'] = $row['user_role_id'];
				$data['mobileno'] = $row['mobile_number'];
				$data['email'] = $row['email'];
				$data['status'] = $row['status'];
				$data['userDetails'] = $data;

			} else {
				$data['status'] = 0;
				$data['userDetails'] = [];
			}
		} else {
			$data['status'] = 0;
			$data['userDetails'] = [];
		}
		return $data;
	}

	function sendOTP($number)
	{
		$data = array();
		$data1 = array();
		// Twilio credentials
		$accountSid = 'AC5801d3baebbaef345085f50cad6a4c38';
		$authToken = 'c812c319cbc37b12547305eb19fe1da9';
		$twilioNumber = '+12512801976';

		// Your recipient's phone number (e.g., +1234567890)
		// $recipientNumber = '+917729070810';

		// Generate a random 6-digit OTP
		$otp = mt_rand(100000, 999999);

		// Store the OTP in a session or database for verification later
// For example, you can use $_SESSION['otp'] = $otp;

		// Twilio API client
		$client = new Client($accountSid, $authToken);

		// Twilio message body
		$messageBody = "Your OTP is: $otp";

		try {
			// Send SMS using Twilio
			$message = $client->messages->create(
				$number,
				[
					'from' => $twilioNumber,
					'body' => $messageBody,
				]
			);

			$data1['mobnumber'] = $number;
			$data1['otp'] = $otp;
			$data['status'] = 1;
			$data['message'] = 'OTP sent successfully';
			$data['otpDetails'] = $data1;


			// Output success message
			// echo "OTP sent successfully to $recipientNumber.";
		} catch (Exception $e) {
			$data['status'] = 0;
			$data['message'] = 'Otp failed';
			$data['otpDetails'] = $data;
			// Handle errors
			echo "Error: " . $e->getMessage();
		}

		return $data;
	}

	function saveUser($data)
	{
		$output = array();

		$hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
		$roleId = 2;
		$firstname = $data['first_name'];
		$lastname = $data['last_name'];
		$username = $firstname . ' ' . $lastname;
		$email = $data['email'];
		$mobnumber = $data['mobile_number'];


		$sql = "INSERT INTO tbl_user(user_role_id,user_name,email,mobile_number,user_password) VALUES(?,?,?,?,?)";
		if ($stmt = mysqli_prepare($this->conn, $sql)) {
			mysqli_stmt_bind_param($stmt, "issis", $roleId, $username, $email, $mobnumber, $hashed_password);
			if (mysqli_stmt_execute($stmt)) {
				$output["status"] = 1;
				$output["message"] = "Signup successfully";
			} else {
				$output["status"] = 0;
				$output["message"] = "failed";
			}
		} else {
			$output["status"] = 0;
			$output["message"] = "failed";
		}

		return $output;

	}

	function isSupervisor($empnum)
	{
		$data = array();
		$query = "SELECT * FROM hs_hr_emp_reportto where erep_sup_emp_number IN ($empnum)";
		$count = mysqli_query($this->conn, $query);
		$row = mysqli_fetch_assoc($count);
		if (isset($row['erep_sup_emp_number'])) {
			$supervisor = $row['erep_sup_emp_number'];
		} else {
			$supervisor = 0;
		}
		return $supervisor;
	}

	function getUserRoleByUserId($id)
	{
		$details = array();
		$query = "SELECT u.user_role_id AS id,ur.name AS name, u.emp_number AS empNumber FROM erp_user u LEFT JOIN erp_user_role ur ON u.user_role_id = ur.id WHERE u.id = $id"; //table
		$result = mysqli_query($this->conn, $query);
		if (mysqli_num_rows($result) > 0) {
			$row = mysqli_fetch_array($result);
			$id = $row['id'];
			$name = $row['name'];

			$empNumber = $row['empNumber'];

			$details['id'] = $id;
			$details['name'] = $name;
			$details['empNumber'] = $empNumber;
		}
		return $details;
	}

	function getMenuList()
	{

		$output = array();

		$query = "SELECT * FROM tbl_chat_menus WHERE status = 0";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {

			while ($row = mysqli_fetch_assoc($sql)) {
				$output['id'] = $row['id'];
				$output['name'] = $row['name'];
				$output1[] = $output;
			}

			$output['status'] = 1;
			$output['menu'] = $output1;

		} else {
			$output['status'] = 0;
			$output['menu'] = array();
		}

		return $output;
	}


	/* ------------------------------ END API's-----------------------*/

}

?>