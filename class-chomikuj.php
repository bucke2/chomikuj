<?php
/*

To do:

	important:
	
		- uploadFile(): checking if a file was actually uploaded and throwing exception if not
		
	not-so important for now:
		
		- getFolders getting first-level child folders only (and make an option to get the whole tree)
		
	not important:

		- checking if a folder was created / removed successfully

*/

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
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 0);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, 0);
		
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
			
			throw new ChomikujException('Curl error (while logging in): '.curl_error($this->curl));
			
		}
		
		preg_match('/accountid\=\"([0-9]+)\"/',$result,$matches);
		
		if(empty($matches)) {
			
			throw new ChomikujException('Couldn\'t retrieve account id.');
			
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
			
			throw new ChomikujException('Curl error (while logging out): '.curl_error($this->curl));
			
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
			
			throw new ChomikujException('Curl error: '.curl_error($this->curl));
			
		}
		
		$json = json_decode($result);
		
		//Upload file
		
		//First of these lines works on my PC and doesn't work on my VPS and the second works on VPS but does not on PC. I guess I should make a research to answer why is this happening.
		$cfile = '@' . realpath($filename);
		//$cfile = curl_file_create($filename);
		
		$postfields = array('files' => $cfile);
		
		curl_setopt($this->curl, CURLOPT_URL, $json->Url);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new ChomikujException('Curl error: '.curl_error($this->curl));
			
		}
		
		$json = json_decode($result);
		//print_r($json);
		
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
			
			throw new ChomikujException('Curl error: '.curl_error($this->curl));
			
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
			
			throw new ChomikujException('Curl error: '.curl_error($this->curl));
			
		}
		
		//echo $result;
		
	}
	
	public function getFolders($folderId) {
		
		$postfields = array();
		
		$postfields['FolderId'] = $folderId;
		$postfields['ChomikId'] = $this->accountId;
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/action/tree/loadtree');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new ChomikujException('Curl error: '.curl_error($this->curl));
			
		}
		
		preg_match_all('/href\=\"(.+?)\"\ rel\=\"([0-9]+)\"\ title\=\"(.+?)\"/',$result,$matches);
		
		$folders = array(
			'paths' => $matches[1],
			'ids' => $matches[2],
			'names' => $matches[3]
		);
		
		return $folders;
		
	}
	
	public function moveFile($fileId,$folderId,$destinationFolderId) {
		
		$postfields = array();
		
		$postfields['__RequestVerificationToken'] = $this->getToken();
		$postfields['FolderId'] = $folderId; //This is actually needed
		$postfields['FolderTo'] = $destinationFolderId;
		$postfields['FileId'] = $fileId;
		$postfields['ChomikId'] = $this->accountId;
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/action/FileDetails/MoveFileAction');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new ChomikujException('Curl error: '.curl_error($this->curl));
			
		}
		
		$json = json_decode($result);
		
		if(empty($json->IsSuccess)) {
			
			throw new ChomikujException('Couldn\'t move a file.');
			
		}
		
		//print_r($json);
		
	}
	
	public function copyFile($fileId,$folderId,$destinationFolderId) {
		
		$postfields = array();
		
		$postfields['__RequestVerificationToken'] = $this->getToken();
		$postfields['FolderId'] = $folderId; //This is actually needed
		$postfields['FolderTo'] = $destinationFolderId;
		$postfields['FileId'] = $fileId;
		$postfields['ChomikId'] = $this->accountId;
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/action/FileDetails/CopyFileAction');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new ChomikujException('Curl error: '.curl_error($this->curl));
			
		}
		
		$json = json_decode($result);
		
		if(empty($json->IsSuccess)) {
			
			throw new ChomikujException('Couldn\'t copy a file.');
			
		}
		
		//print_r($json);
		
	}
	
	public function renameFile($fileId,$newFilename,$newDescription) {
		
		$postfields = array();
		
		$postfields['__RequestVerificationToken'] = $this->getToken();
		$postfields['FileId'] = $fileId;
		$postfields['Name'] = $newFilename;
		$postfields['Description'] = $newDescription;
		//$postfields['ChomikId'] = $this->accountId;
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/action/FileDetails/EditNameAndDescAction');
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postfields);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new ChomikujException('Curl error: '.curl_error($this->curl));
			
		}
		
		$json = json_decode($result);
		
		if(empty($json->IsSuccess)) {
			
			throw new ChomikujException('Couldn\'t rename a file.');
			
		}
		
		//print_r($json);
		
	}
	
	public function getToken() {
		
		curl_setopt($this->curl, CURLOPT_URL, 'http://chomikuj.pl/'.$this->username);
		curl_setopt($this->curl, CURLOPT_POST, 0);
		
		if(!$result = curl_exec($this->curl)) {
			
			throw new ChomikujException('Curl error (while retrieving the token): '.curl_error($this->curl));
			
		}
		
		preg_match('/\_\_RequestVerificationToken\"\ type\=\"hidden\"\ value\=\"(.+?)\"/',$result,$matches);
		
		if(empty($matches)) {
			
			throw new ChomikujException('Couldn\'t retrieve the token.');
			
		}
		
		return $matches[1];
		
	}
	
}

class ChomikujException extends Exception {}

?>
