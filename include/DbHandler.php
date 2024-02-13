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

class DbHandler
{
	private $conn;

	const COMPLAINT_DRAFT = 0; // POSH
	const COMPLAINT_SUBMIT = 1; // POSH
	const COMPLAINT_ACCEPTED = 2;
	const COMPLAINT_REJECTED = 3;
	const SHOW_CAUSE_NOTICE = 4;
	const PERSECUTOR_EXPLAINATION = 5;
	const MEETING_OUTPUT_SETTLEMENT = 6;
	const MANAGEMENT_ACTION = 7;
	const MEETING_OUTPUT_NO_SETTLEMENT = 8;
	const INVESTIGATION_DRAFT = 9;
	const INVESTIGATION_SUBMIT = 10;
	const SCHEDULE_MEETING = 11;

	const COMP_TYPE_ORGANISATION = 1; // POSH
	const COMP_TYPE_CONTRACT_OUTSOURCE = 2; // POSH
	const COMP_TYPE_TRAINEE = 3; // POSH
	const COMP_TYPE_OTHER = 4; // POSH

	const HARASSMENT_TYPE_VISUAL = 1; // POSH
	const HARASSMENT_TYPE_PHYSICAL = 2; // POSH
	const HARASSMENT_TYPE_OTHER = 3; // POSH

	const REPORTING_PERSON_YES = 0; // POSH
	const REPORTING_PERSON_NO = 1; // POSH

	const REPORTING_TYPE_ANONYMOUS = 0; // POSH
	const REPORTING_TYPE_VOLUNTARY = 1; // POSH

	const SICKLEAVE = 12; // Sick leave
	const ANNUALLEAVE = 13; // Annual Leave
	const FULLDAY = 0; // full day
	const HALFDAY = 1; // Half day
	const SPECIFIEDTIME = 1; // Half day
	const SUBMITT = 1;
	const APPROVE = 2;
	const CANCEL = 0;
	const REJECT = -1;
	const TAKEN = 3;

	// USER ROLE IDS

	const ADMIN_USER_ROLE_ID = 1;
	const ESS_USER_ROLE_ID = 2;
	const SUPERVISOR = 3;
	const PROJECTADMIN = 4;
	const INTERVIEWER = 5;
	const HIRING_MANAGER_ROLE_ID = 6;
	const REVIEWER_ROLE_ID = 7;
	const FINANCE_MANAGER_ROLE_ID = 8;
	const PROJECTMANAGER = 9;
	const EMC_USER_ROLE_ID = 10;
	const ENG_USER_ROLE_ID = 11;
	const TECH_USER_ROLE_ID = 12;
	const SHIFT_INCHARGE_USER_ROLE_ID = 13;
	const OPERATOR = 14;
	const SHIFT_TECHNICIAN_USER_ROLE_ID = 15;
	const HEADOFFICETEAM = 17;
	const PLANT_MANAGER_USER_ROLE_ID = 18;
	const SHIFT_SUPERVISOR_USER_ROLE_ID = 19;
	const CENTRALSTOREMANAGER = 20;
	const DEPARTMENT_MANAGER_ID = 22;
	const DRIVER_ID = 24;
	const PROJECTCONTROLLER = 25;
	const SECURITY = 30;

	const CEO_USER_ROLE_ID = 31;
	const BID_UPLOAD_ROLE_ID = 32;
	const ASSIGNER_ROLE_ID = 33;
	const RESPONDER_ROLE_ID = 34;
	const ICCACTIONOWNER_ROLE_ID = 35;
	const RECRUITER_ROLE_ID = 37;
	const TRAINING_MANAGER_ID = 34;
	const PLANT_MANAGER = 39;
	const CORPORATE_HEAD = 40;

	function __construct()
	{
		require_once dirname(__FILE__) . '/DbConnect.php';
		require_once dirname(__FILE__) . '/SmsService.php';
		require_once dirname(__FILE__) . '/PasswordHash.php';
		require_once dirname(__FILE__) . '/WhatsappService.php';
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
		$token = $this->generateApiKey();
		$query = "SELECT u.id AS id,u.user_role_id AS user_roleid,u.user_name AS user_name,u.user_password AS user_password, emp.emp_number AS emp_number,emp.emp_mobile AS mobile_number,emp.emp_work_email AS email,emp.business_area AS companyId FROM erp_user u LEFT JOIN hs_hr_employee emp ON emp.emp_number = u.emp_number WHERE u.deleted=0 and u.user_name ='$username'";
		$count = mysqli_query($this->conn, $query);
		if (mysqli_num_rows($count) > 0) {
			$row = mysqli_fetch_assoc($count);
			$user_name = $row['user_name'];
			$user_password = $row['user_password'];
			$user_id = $row['id'];
			$user_roleid = $row['user_roleid'];
			$mobileno = $row['mobile_number'];
			$email = $row['email'];
			$companyId = $row['companyId'];
			$emp_num = $row['emp_number'];
			$data['emp_number'] = $emp_num;
			$verify = password_verify($password, $user_password);
			if ($verify) {

				$rndno = rand(1000, 9999);

				$mobile = $mobileno;

				$query = "SELECT * FROM erp_user_token WHERE userId = $user_id";
				$count = mysqli_query($this->conn, $query);
				$otpnumber = md5($rndno);

				if (mysqli_num_rows($count) > 0) {
					$row = mysqli_fetch_assoc($count);
					$token_userid = $row['userid'];
					if ($token_userid == $user_id) {
						$updatesql = "UPDATE erp_user_token SET userid=$user_id, otp='$otpnumber',session_token='$token' WHERE userid=$user_id";
						if ($result2 = mysqli_query($this->conn, $updatesql)) {
							$data['session_token'] = $token;
							$data['user_id'] = $user_id;
							$data['user_roleid'] = $user_roleid;
							$data['company_id'] = $companyId;
							$supervisor = $this->isSupervisor($emp_num);
							$userRoleId = $this->getUserRoleByUserId($user_id);

							if ($userRoleId['name'] == 'Department Manager') {
								$data['role'] = 'Department Manager';
							} else {

								if (!empty($supervisor)) {
									$data['role'] = 'Supervisor';
								} else {
									$data['role'] = $userRoleId['name'];
								}
							}

							$data['userDetails'] = $data;
							// $data['role'] = $userRoleId['name'];
							$data['status'] = 1;
						} else {
							$data['status'] = 0;
						}
					} else {
						$data['status'] = 0;
					}
				} else {
					$sql = "INSERT INTO erp_user_token (userid,otp,session_token) VALUES (?,?,?)";

					if ($stmt = mysqli_prepare($this->conn, $sql)) {
						// Bind variables to the prepared statement as parameters
						mysqli_stmt_bind_param($stmt, "iss", $user_id, $otpnumber, $token);

						// Attempt to execute the prepared statement
						if (mysqli_stmt_execute($stmt)) {
							$data['session_token'] = $token;
							$data['user_id'] = $user_id;
							$data['company_id'] = $companyId;
							$supervisor = $this->isSupervisor($emp_num);
							if ($supervisor) {
								$data['supervisorId'] = $supervisor;
								$data['supervisor'] = 'Supervisor';
							}
							$data['userDetails'] = $data;
							$data['status'] = 1;
						} else {
							$data['status'] = 0;
						}
					} else {
						//echo "ERROR: Could not prepare query: $sql. " . mysqli_error($this->conn);
						$data['status'] = 0;
					}
				}
			} else {
				$data['status'] = 0;
			}
		} else {
			$data['status'] = 0;
		}
		return $data;
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