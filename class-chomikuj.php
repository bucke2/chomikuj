<?php

class Chomikuj {
	
	public function __construct() {
		
		$this->curl = curl_init();
		
		curl_setopt($this->curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, realpath(__DIR__."/cookie.txt"));
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, realpath(__DIR__."/cookie.txt"));
		curl_setopt($this->curl, CURLOPT_COOKIESESSION, true);
		curl_setopt($this->curl, CURLOPT_HEADER, 0);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, 20);
		
	}
	
	public function login($username,$password) {
		
		//Preparing postfields
		
		$postfields = array();
		
		$postfields['ReturnUrl'] = '';
		$postfields['Login'] = $username;
		$postfields['Password'] = $password;
		
		//Posting fields

		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/action/Login/TopBarLogin');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new Exception('Curl error (while logging in).');
			
		}
		
		preg_match('/accountid\=\"([0-9]+)\"/',$result,$matches);
		
		if(empty($matches)) {
			
			throw new Exception('Couldn\'t retrieve account id.');
			
		}
		
		$this->accountId = $matches[1];
		$this->username = $username;
		
		return true;
		
	}
	
	public function logout() {
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/action/Login/LogOut');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, array());
		
		if(!curl_exec($this->curl)) {
			
			throw new Exception('Curl error (while logging out).');
			
		}
		
	}
	
	public function uploadFile($folderId,$filename) {
		
		//Get url
		
		$postfields = array();
		$postfields['accountid'] = $this->accountId;
		$postfields['folderid'] = $folderId;
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/action/Upload/GetUrl/');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new Exception('Curl error.');
			
		}
		
		$json = json_decode($result);
		
		//Upload file
		
		$cfile = '@' . realpath($filename);
		$postfields = array('files' => $cfile);
		
		curl_setopt($this->curl, CURLOPT_URL, $json->Url);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new Exception('Curl error.');
			
		}
		
		$json = json_decode($result);
		print_r($json);
		
	}
	
	public function createFolder($parentFolderId,$folderName,$adult = false,$password = null) {
		
		//Building postfields
		
		$postfields = array();
		
		$postfields['__RequestVerificationToken'] = $this->getToken();
		
		
		$postfields['FolderId'] = $parentFolderId;
		$postfields['ChomikId'] = $this->accountId;
		$postfields['FolderName'] = $folderName;
		
		//it has to be 'true' not true
		
		if($adult) {
		
			$postfields['AdultContent'] = 'true';
			
		} else {
			
			$postfields['AdultContent'] = 'false';
			
		}
		
		if(!$password) {
			
			$postfields['NewFolderSetPassword'] = 'false';
			$postfields['Password'] = '';
			
		} else {
			
			$postfields['NewFolderSetPassword'] = 'true';
			$postfields['Password'] = $password;
			
		}
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/action/FolderOptions/NewFolderAction');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new Exception('Curl error.');
			
		}
		
		//echo $result;
		
	}
	
	public function removeFolder($folderId) {
		
		$postfields = array();
		
		$postfields['__RequestVerificationToken'] = $this->getToken();
		$postfields['FolderId'] = $folderId;
		$postfields['ChomikId'] = $this->accountId;
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/action/FolderOptions/DeleteFolderAction');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new Exception('Curl error.');
			
		}
		
		echo $result;
		
	}
	
	public function getToken() {
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/'.$this->username);
		curl_setopt($this->curl, CURLOPT_POST, 0);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new Exception('Curl error.');
			
		}
		
		preg_match('/\_\_RequestVerificationToken\"\ type\=\"hidden\"\ value\=\"(.+?)\"/',$result,$matches);
		
		if(empty($matches)) {
			
			throw new Exception('Couldn\'t retrieve the token.');
			
		}
		
		return $matches[1];
		
	}
	
}

?>
