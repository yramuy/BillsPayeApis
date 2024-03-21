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
		$sql = mysqli_query($this->conn, $query);
		if (mysqli_num_rows($sql) > 0) {
			$row = mysqli_fetch_assoc($sql);
			$user_password = $row['user_password'];
			$state = $row['state_id'];
			$city = $row['city_id'];
			$verify = password_verify($password, $user_password);
			if ($verify) {
				$locQry = "SELECT s.name as state,c.city FROM cities c LEFT JOIN states s ON c.state_id = s.id WHERE c.state_id = '$state' AND c.id = '$city'";
				$locSql = mysqli_query($this->conn, $locQry);

				$data['user_name'] = $row['user_name'];
				$data['user_id'] = $row['id'];
				if ($row['user_role_id'] == 1) {
					$data['role_name'] = 'Admin';
				}
				$data['user_role_id'] = $row['user_role_id'];
				$data['mobileno'] = $row['mobile_number'];
				$data['email'] = $row['email'];

				if (mysqli_num_rows($locSql) > 0) {
					$locRow = mysqli_fetch_assoc($locSql);
					$data['state_id'] = $row['state_id'];
					$data['state'] = $locRow['state'];
					$data['city_id'] = $row['city_id'];
					$data['city'] = $locRow['city'];
					$data['pincode'] = $row['pincode'];
				} else {
					$data['state_id'] = "";
					$data['state'] = "";
					$data['city_id'] = "";
					$data['city'] = "";
					$data['pincode'] = "";
				}

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
		$OTP = rand(0000, 9999);
		$API = "6d0f846348a856321729a2f36734d1a7";
		$PHONE = $number;
		// $OTP=1234;
		$REQUEST_URI = "https://sms.renflair.in/V1.php?";
		$URL = "https://sms.renflair.in/V1.php?API=$API&PHONE=$PHONE&OTP=$OTP";
		$curl = curl_init($URL);
		curl_setopt($curl, CURLOPT_URL, $URL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$resp = curl_exec($curl);
		curl_close($curl);
		$response = json_decode($resp);
		// Accessing status and message values

		$key = 'ErrorMessage';
		if (property_exists($response, $key)) {
			// Accessing values
			$ErrorCode = $response->ErrorCode;
			$ErrorMessage = $response->ErrorMessage;
			// $JobId = $response->JobId;
			// $Number = $response->MessageData[0]->Number;
			// $MessageId = $response->MessageData[0]->MessageId;
			$data1['mobnumber'] = $number;
			$data1['otp'] = $OTP;
			$data['status'] = $ErrorMessage;
			$data['message'] = 'Success';
			$data['otpDetails'] = $data1;
			// echo "Key $key exists in the object.\n";
		} else {
			$status = $response->status;
			$message = $response->message;
			$data1['mobnumber'] = $number;
			$data1['otp'] = "";
			$data['status'] = $status;
			$data['message'] = $message;
			$data['otpDetails'] = $data1;
			// echo "Key $key does not exist in the object.\n";
		}
		// print_r($response);die;

		return $data;
	}

	function saveUser($data)
	{
		$output = array();

		$hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
		$roleId = 2;
		$firstname = $data['first_name'];
		$lastname = $data['last_name'];
		$username = $firstname . '' . $lastname;
		$email = $data['email'];
		$mobnumber = $data['mobile_number'];
		$state = $data['state_id'];
		$city = $data['city_id'];
		$pincode = $data['pincode'];

		$sql = "INSERT INTO tbl_user(user_role_id,user_name,email,mobile_number,user_password,state_id,city_id,pincode) VALUES(?,?,?,?,?,?,?,?)";
		if ($stmt = mysqli_prepare($this->conn, $sql)) {
			mysqli_stmt_bind_param($stmt, "issisiii", $roleId, $username, $email, $mobnumber, $hashed_password, $state, $city, $pincode);
			if (mysqli_stmt_execute($stmt)) {
				$output["status"] = 1;
				$output["message"] = "Signup successfully";
			} else {
				$output["status"] = 0;
				$output["message"] = "Signup failed";
			}
		} else {
			$output["status"] = 0;
			$output["message"] = "Signup failed1";
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

	function getCategories($path)
	{

		$output = array();

		$query = "SELECT * FROM tbl_categories";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {

			while ($row = mysqli_fetch_assoc($sql)) {
				$output['id'] = $row['id'];
				$output['name'] = $row['name'];
				$output['top_picks'] = $this->getTopPicks($row['id'], $path);
				$output1[] = $output;
			}

			$output['status'] = 1;
			$output['category'] = $output1;
		} else {
			$output['status'] = 0;
			$output['category'] = array();
		}

		return $output;
	}

	function getCategoryTopPicks($path)
	{

		$output = array();

		$query = "SELECT c.name,sc.* FROM tbl_categories c LEFT JOIN tbl_sub_categories sc ON c.id = sc.category_id ORDER BY sc.rating DESC";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			$output1 = array();
			while ($row = mysqli_fetch_assoc($sql)) {
				if ($row['category_id'] != null) {
					$output['category_id'] = $row['category_id'];
					$output['category_name'] = $row['name'];
					$output['sub_category_id'] = $row['id'];
					$output['sub_category_name'] = $row['sub_category_name'];
					$output['rating'] = $row['rating'];
					// $output['offerDetails'] = $this->getOffer($row['id']);
					$output['file_name'] = $row['file_name'];
					$output['imagePath'] = $path . '' . $row['file_name'];
					$output1[] = $output;
				}
			}

			$output['status'] = 1;
			$output['top_picks'] = $output1;
		} else {
			$output['status'] = 0;
			$output['top_picks'] = array();
		}

		return $output;
	}

	function getTopPicks($id, $path)
	{
		$data1 = array();
		$query = "SELECT * FROM tbl_sub_categories sc WHERE sc.category_id = $id ORDER BY sc.rating DESC";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			while ($row = mysqli_fetch_assoc($sql)) {
				$output['sub_category_id'] = $row['id'];
				$output['category_id'] = $row['category_id'];
				$output['sub_category_name'] = $row['sub_category_name'];
				$output['rating'] = $row['rating'];
				$output['offerDetails'] = $this->getOffer($row['id']);
				$output['file_name'] = $row['file_name'];
				$output['imagePath'] = $path . '' . $row['file_name'];
				$data1[] = $output;
			}
		}

		return $data1;
	}

	function getSubCategories($data, $path)
	{
		$output = array();
		$id = $data['category_id'];
		$user_id = $data['user_id'];
		$query = "SELECT * FROM tbl_sub_categories WHERE category_id = $id";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			while ($row = mysqli_fetch_assoc($sql)) {
				$output['sub_category_id'] = $row['id'];
				$output['category_id'] = $row['category_id'];
				$output['sub_category_name'] = $row['sub_category_name'];
				$output['rating'] = $row['rating'];
				$output['distance'] = $row['distance'];
				$output['wishlist_status'] = $this->getWishlistData($id, $row['id'], $user_id);
				$ftvalue = "";
				if (!empty($row['food_type'])) {
					$ftarray = explode(',', $row['food_type']);
					$ftarray = array_map(function ($item) {
						return $item == 1 ? "Veg" : ($item == 2 ? "Non Veg" : "");
					}, $ftarray);
					$ftvalue = implode(',', $ftarray);
				}
				$output['foodTypes'] = $ftvalue;
				$output['file_name'] = $row['file_name'];
				$output['imagePath'] = $path . '' . $row['file_name'];
				$output['offerDetails'] = $this->getOffer($row['id']);


				$data1[] = $output;
			}
			$output['status'] = 1;
			$output['sub_categories'] = $data1;
			$output['categoryOffers'] = $this->getCategoryOffers($id, $path);
		} else {
			$output['status'] = 0;
			$output['sub_categories'] = array();
			$output['categoryOffers'] = array();
		}

		return $output;
	}

	// Display single offer details for Category and Subcategory
	function getOffer($id)
	{

		$offer = array();
		$query = "SELECT * FROM tbl_offers WHERE sub_cat_id = $id ORDER BY id DESC limit 1";
		$sql = mysqli_query($this->conn, $query);
		if (mysqli_num_rows($sql) > 0) {
			$row = mysqli_fetch_array($sql);

			$offer['offer_title'] = $row['offer_title'];
			$offer['offer'] = $row['offer'];
		} else {
			$offer['offer_title'] = "";
			$offer['offer'] = 0;
		}

		return $offer;
	}

	function getStates()
	{
		$output = array();
		$query = "SELECT * FROM states";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			while ($row = mysqli_fetch_assoc($sql)) {
				$output['id'] = $row['id'];
				$output['name'] = $row['name'];
				$data1[] = $output;
			}
			$output['status'] = 1;
			$output['states'] = $data1;
		} else {
			$output['status'] = 0;
			$output['states'] = array();
		}

		return $output;
	}

	function getCitiesByState($data)
	{
		$output = array();
		$id = $data['state_id'];
		$query = "SELECT * FROM cities WHERE state_id = $id";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			while ($row = mysqli_fetch_assoc($sql)) {
				$output['id'] = $row['id'];
				$output['city'] = $row['city'];
				$output['state_id'] = $row['state_id'];
				$data1[] = $output;
			}
			$output['status'] = 1;
			$output['cities'] = $data1;
		} else {
			$output['status'] = 0;
			$output['cities'] = array();
		}

		return $output;
	}

	// Most Exciting offers for all Categories
	function getMostExcitingOffers($data, $path)
	{
		$output = array();
		$id = $data['state_id'];
		$query = "SELECT o.*,sc.sub_category_name FROM tbl_offers o LEFT JOIN tbl_sub_categories sc ON o.sub_cat_id = sc.id
			ORDER BY o.offer DESC";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			while ($row = mysqli_fetch_assoc($sql)) {
				$output['id'] = $row['id'];
				$output['cat_id'] = $row['cat_id'];
				$output['sub_cat_id'] = $row['sub_cat_id'];
				$output['offer_title'] = $row['offer_title'];
				$output['offer'] = $row['offer'];
				$output['sub_category_name'] = $row['sub_category_name'];
				$output['offer_description'] = $row['offer_description'];
				$output['imagePath'] = $path . '' . $row['image_name'];

				$data1[] = $output;
			}
			$output['status'] = 1;
			$output['offers'] = $data1;
		} else {
			$output['status'] = 0;
			$output['offers'] = array();
		}

		return $output;
	}

	// About Subcategory Details
	function getSubCategoryDetailsById($data, $path)
	{
		$output = array();
		$id = $data['sub_category_id'];
		$user_id = $data['user_id'];
		$query = "SELECT * FROM tbl_sub_categories WHERE id = $id";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			while ($row = mysqli_fetch_assoc($sql)) {
				$output['sub_category_id'] = $row['id'];
				$output['category_id'] = $row['category_id'];
				$output['sub_category_name'] = $row['sub_category_name'];
				$output['rating'] = $row['rating'];
				$output['description'] = $row['sub_cat_description'];
				$output['address'] = $row['sub_cat_address'];
				$output['distance'] = $row['distance'];

				$ftvalue = "";
				if (!empty($row['food_type'])) {
					$ftarray = explode(',', $row['food_type']);
					$ftarray = array_map(function ($item) {
						return $item == 1 ? "Veg" : ($item == 2 ? "Non Veg" : "");
					}, $ftarray);
					$ftvalue = implode(',', $ftarray);
				}
				$output['foodTypes'] = $ftvalue;
				$output['file_name'] = $row['file_name'];
				$output['subCatImg'] = $path . '' . $row['file_name'];
				$output['offerDetails'] = $this->getOfferDetails($row['id'], $path);
				$output['menuDetails'] = $this->getMenus($row['id'], $path);
				$output['reviews'] = $this->getReviews($row['id'], $path);
				$output['reviewCnt'] = $this->getReviewCount($row['id'], $user_id);
				$output['latestOffer'] = $this->getOffer($row['id']);
				$output['photos'] = [];
				$data1[] = $output;
			}
			$output['status'] = 1;
			$output['subCategoryDetails'] = $data1;
		} else {
			$output['status'] = 0;
			$output['subCategoryDetails'] = array();
		}

		return $output;
	}

	function getOfferDetails($id, $path)
	{
		$output1 = array();

		$query = "SELECT * FROM tbl_offers WHERE sub_cat_id = $id ORDER BY offer DESC";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			while ($row = mysqli_fetch_assoc($sql)) {
				$output['id'] = $row['id'];
				$output['cat_id'] = $row['cat_id'];
				$output['sub_cat_id'] = $row['sub_cat_id'];
				$output['offer_title'] = $row['offer_title'];
				$output['offer'] = $row['offer'];
				$output['offer_description'] = $row['offer_description'];
				$output['offerImg'] = $path . '' . $row['image_name'];
				$output1[] = $output;
			}
		}

		return $output1;
	}

	function getMenus($id, $path)
	{
		$output1 = array();
		$query = "SELECT * FROM tbl_menus WHERE sub_cat_id = $id ORDER BY id DESC";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			while ($row = mysqli_fetch_assoc($sql)) {
				$output['id'] = $row['id'];
				$output['cat_id'] = $row['cat_id'];
				$output['sub_cat_id'] = $row['sub_cat_id'];
				$output['menu_name'] = $row['menu_name'];
				$output['description'] = $row['menu_description'];
				$output['menuImg'] = $path . '' . $row['image_name'];
				$output1[] = $output;
			}
		}

		return $output1;
	}

	function getReviews($id, $path)
	{
		$output1 = array();
		$query = "SELECT r.*,u.user_name, DATE(r.created_on) as reviewDate FROM tbl_reviews r 
		LEFT JOIN tbl_user u ON r.user_id = u.id
		WHERE r.sub_cat_id = $id ORDER BY r.id DESC";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			while ($row = mysqli_fetch_assoc($sql)) {
				$output['id'] = $row['id'];
				$output['cat_id'] = $row['cat_id'];
				$output['sub_cat_id'] = $row['sub_cat_id'];
				$output['user_id'] = $row['user_id'];
				$output['user_name'] = $row['user_name'];
				$output['rating'] = $row['rating'];
				$output['short_text'] = $row['short_text'];
				$output['long_text'] = $row['long_text'];
				$output['date'] = $row['reviewDate'];
				// $output['menuImg'] = $path.''.$row['image_name'];
				$output1[] = $output;
			}
		}

		return $output1;
	}

	// Category offers details
	function getCategoryOffers($id, $path)
	{
		$output = array();
		$query = "SELECT o.*, sc.sub_category_name FROM tbl_offers o 
	            LEFT JOIN tbl_sub_categories sc ON o.sub_cat_id = sc.id
	            WHERE o.cat_id = $id ORDER BY o.id DESC";
		$sql = mysqli_query($this->conn, $query);

		while ($row = mysqli_fetch_assoc($sql)) {
			$offer = array(
				'sub_category_name' => $row['sub_category_name'],
				'offer_title' => $row['offer_title'],
				'offer' => $row['offer'],
				'offerImg' => $path . $row['image_name'],
				'description' => $row['offer_description']
			);
			$output[] = $offer;
		}

		return $output;
	}

	// addRemoveWishlist
	function addRemoveWishlist($data)
	{
		$output = array();
		$cat_id = $data['cat_id'];
		$sub_cat_id = $data['sub_cat_id'];
		$user_id = $data['user_id'];
		$wishlist_on = date('Y-m-d H:i:s');
		$checkQry = "SELECT * FROM tbl_wishlist WHERE cat_id = $cat_id AND (sub_cat_id = $sub_cat_id AND user_id = $user_id)";
		$sql = mysqli_query($this->conn, $checkQry);
		$checkFavorite = mysqli_num_rows($sql);
		$row = mysqli_fetch_array($sql);

		if ($checkFavorite > 0) {
			$status = $row['status'] == 0 ? 1 : 0;
			$id = $row['id'];
			$updateQry = "UPDATE tbl_wishlist SET status = '$status', wishlist_on = '$wishlist_on' WHERE id = $id";
			if ($result = mysqli_query($this->conn, $updateQry)) {
				$output['status'] = 1;
				$output['message'] = $row['status'] == 0 ? "Added to wishlist" : "Removed from wishlist";
			} else {
				$output['status'] = 0;
				$output['message'] = "Failed to update wishlist";
			}
		} else {
			$status = 1;
			$query = "INSERT INTO tbl_wishlist(cat_id,sub_cat_id,user_id,status,wishlist_on) VALUES(?,?,?,?,?)";
			if ($stmt = mysqli_prepare($this->conn, $query)) {
				mysqli_stmt_bind_param($stmt, 'iiiis', $cat_id, $sub_cat_id, $user_id, $status, $wishlist_on);
				if (mysqli_stmt_execute($stmt)) {
					$output['status'] = 1;
					$output['message'] = "Added to wishlist";
				} else {
					$output['status'] = 0;
					$output['message'] = "Failed to add to wishlist";
				}
			} else {
				$output['status'] = 0;
				$output['message'] = "Failed to prepare wishlist query";
			}
		}

		return $output;
	}

	// Wishlist Data
	function getUserWishlist($data, $base_url)
	{
		$output = array();
		$userId = $data['user_id'];

		if ($userId != '') {
			$query = "SELECT w.*,c.name as category,sc.sub_category_name,sc.file_name,u.user_name FROM tbl_wishlist w 
			LEFT JOIN tbl_categories c ON w.cat_id = c.id
			LEFT JOIN tbl_sub_categories sc ON w.sub_cat_id = sc.id
			LEFT JOIN tbl_user u ON w.user_id = u.id WHERE w.status = 1 AND w.user_id = $userId";
			$sql = mysqli_query($this->conn, $query);

			if (mysqli_num_rows($sql) > 0) {
				$data1 = array(); // Initialize an array to store multiple rows
				while ($row = mysqli_fetch_assoc($sql)) {
					$data1[] = array(
						"id" => $row['id'],
						"cat_id" => $row['cat_id'],
						"sub_cat_id" => $row['sub_cat_id'],
						"category" => $row['category'],
						"subCatName" => $row['sub_category_name'],
						"user_name" => $row['user_name'],
						"status" => $row['status'],
						"image" => $base_url . $row['file_name']
					);
				}

				$output['status'] = 1;
				$output['wishlist'] = $data1;
			} else {
				$output['status'] = 0;
				$output['wishlist'] = array();
			}
		} else {
			$output['status'] = 0;
			$output['wishlist'] = array();
		}



		return $output;
	}

	// User Wishlist Status
	function getWishlistData($cat_id, $sub_cat_id, $user_id)
	{

		$query = "SELECT * FROM tbl_wishlist WHERE cat_id = $cat_id AND sub_cat_id = $sub_cat_id AND user_id = $user_id";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			$row = mysqli_fetch_assoc($sql);
			$status = $row['status'];
		} else {
			$status = 0;
		}

		return $status;
	}

	// User Wishlist Status
	function getReviewCount($sub_cat_id, $user_id)
	{

		$query = "SELECT * FROM tbl_reviews WHERE sub_cat_id = $sub_cat_id AND user_id = $user_id";
		$sql = mysqli_query($this->conn, $query);
		$count = mysqli_num_rows($sql);

		return $count;
	}

	// saveUserReview
	function saveUserReview($data)
	{
		$output = array();
		$cat_id = $data['cat_id'];
		$sub_cat_id = $data['sub_cat_id'];
		$user_id = $data['user_id'];
		$rating = $data['rating'];
		$short_text = $data['short_text'];
		$long_text = $data['long_text'];
		$created_on = date('Y-m-d H:i:s');

		$query = "INSERT INTO tbl_reviews(cat_id, sub_cat_id, user_id, rating, short_text, long_text, created_on) VALUES(?,?,?,?,?,?,?)";

		if ($stmt = mysqli_prepare($this->conn, $query)) {
			mysqli_stmt_bind_param($stmt, 'iiissss', $cat_id, $sub_cat_id, $user_id, $rating, $short_text, $long_text, $created_on);
			if (mysqli_stmt_execute($stmt)) {
				$output['status'] = 1;
				$output['message'] = "Review send successfully";
			} else {
				$output['status'] = 0;
				$output['message'] = "Review failed";
			}
		} else {
			$output['status'] = 0;
			$output['message'] = "Failed to prepare review query";
		}

		return $output;
	}

	// All Category With Offers
	function getAllCategoryWithOffers($data, $base_url)
	{
		$output = array();
		$category_id = $data['category_id'];
		$user_id = $data['user_id'];

		$query = "SELECT o.*,c.name as category,sc.sub_category_name,sc.file_name,sc.sub_cat_description FROM tbl_offers o 
			LEFT JOIN tbl_categories c ON o.cat_id = c.id
			LEFT JOIN tbl_sub_categories sc ON o.sub_cat_id = sc.id";

		if ($category_id != 0) {
			$query .= " WHERE o.cat_id = $category_id";
		}

		$query .= " ORDER BY o.id DESC";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			$data1 = array(); // Initialize an array to store multiple rows
			while ($row = mysqli_fetch_assoc($sql)) {
				$data1[] = array(
					"id" => $row['id'],
					"cat_id" => $row['cat_id'],
					"sub_cat_id" => $row['sub_cat_id'],
					"category" => $row['category'],
					"subCatName" => $row['sub_category_name'],
					"description" => $row['sub_cat_description'],
					"offer_title" => $row['offer_title'],
					"offer" => $row['offer'],
					"wishlist_status" => $this->getWishlistData($row['cat_id'], $row['sub_cat_id'], $user_id),
					"subCatImage" => $base_url . $row['file_name'],
					"offerImage" => $base_url . $row['image_name']
				);
			}

			$output['status'] = 1;
			$output['categoryWithOffers'] = $data1;
		} else {
			$output['status'] = 0;
			$output['categoryWithOffers'] = $output;
		}

		return $output;
	}

	function proceedToPayCalculation($data)
	{

		$userId = $data['user_id'];
		$catId = $data['cat_id'];
		$subCatId = $data['sub_cat_id'];

		if (!empty($data['offer'])) {
			$offer = $data['offer'];
		} else {
			$offer = 0;
		}
		$amount = $data['amount'];

		$originalPrice = $amount;
		$discount = $offer; // 20%
		$discountAmount = $originalPrice * ($discount / 100);
		$discountedPrice = $originalPrice - $discountAmount;
		$bill_date = date("j F Y");
		$bill_time = date("H:i:s a");

		$billingDetails = $this->billingDetails($userId, $subCatId);
		$offer = $this->getOffer($subCatId);
		$output = array();

		$output1 = array(
			"billingById" => $userId,
			"billingBy" => $billingDetails['billingBy'],
			"billingToId" => $subCatId,
			"billingTo" => $billingDetails['billingTo'],
			"originalPrice" => $originalPrice,
			"discount" => $discount . "%",
			"offer_title" => $offer['offer_title'],
			"savingPrice" => $discountAmount,
			"finalPrice" => $discountedPrice,
			"date" => $bill_date,
			"time" => $bill_time,
		);

		$output['status'] = 1;
		$output['billDetails'] = $output1;

		return $output;
	}

	function billingDetails($userId, $subCatId)
	{
		$query = "SELECT sub_category_name as billingTo, (SELECT user_name FROM tbl_user WHERE id = '$userId') as billingBy FROM tbl_sub_categories WHERE id = '$subCatId'";
		$sql = mysqli_query($this->conn, $query);
		$row = "";
		if (mysqli_num_rows($sql) > 0) {
			$row = mysqli_fetch_array($sql);
		}

		return $row;
	}

	function getPosts($path)
	{
		$output = array();

		$query = "SELECT p.*,c.name as category,sc.sub_category_name FROM tbl_posts p 
		LEFT JOIN tbl_categories c ON p.cat_id = c.id
		LEFT JOIN tbl_sub_categories sc ON p.sub_cat_id = sc.id ORDER BY p.id DESC";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {
			$data = array(); // Initialize an array to store multiple rows
			while ($row = mysqli_fetch_array($sql)) {
				$data[] = array(
					"id" => $row['id'],
					"cat_id" => $row['cat_id'],
					"sub_cat_id" => $row['sub_cat_id'],
					"category" => $row['category'],
					"sub_category_name" => $row['sub_category_name'],
					"post_title" => $row['post_title'],
					"post_description" => $row['post_description'],
					"image" => $path . '' . $row['image_name'],
				);
			}

			$output['status'] = 1;
			$output['posts'] = $data;
		} else {
			$output['status'] = 0;
			$output['posts'] = $output;
		}

		return $output;
	}

	function saveTransaction($data)
	{
		$merchant_id = $data['merchant_id'];
		$transaction_id = $data['transaction_id'];
		$transaction_by = $data['transaction_by'];
		$transaction_to = $data['transaction_to'];
		$transaction_amount = $data['transaction_amount'];
		// $transaction_date = $data['transaction_date'];
		$original_amount = $data['original_amount'];
		$discount = $data['discount'];
		$discounted_amount = $data['discounted_amount'];
		$final_amount = $data['final_amount'];

		$output = array();
		$query = "INSERT INTO tbl_transactions(merchant_id,transaction_id,transaction_by,transaction_to,transaction_amount,original_amount,discount,final_amount,discounted_amount) VALUES(?,?,?,?,?,?,?,?,?)";
		if ($stmt = mysqli_prepare($this->conn, $query)) {
			mysqli_stmt_bind_param($stmt, "ssiisssss", $merchant_id, $transaction_id, $transaction_by, $transaction_to, $transaction_amount, $original_amount, $discount, $final_amount, $discounted_amount);
			if (mysqli_stmt_execute(($stmt))) {
				$output['status'] = 1;
				$output['message'] = "Transaction saved succefully";
			} else {
				$output['status'] = 0;
				$output['message'] = "Transaction failed";
			}
		} else {
			$output['status'] = 0;
			$output['message'] = "Transaction failed";
		}

		return $output;
	}
	/* ------------------------------ END API's-----------------------*/
}
