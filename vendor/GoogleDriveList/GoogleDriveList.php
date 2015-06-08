<?php

namespace GoogleDriveList;

/**
 * Class GoogleDriveList
 * @package GoogleDriveList
 */
class GoogleDriveList
{
	/**
	 * @var string google api client id
	 * see https://console.developers.google.com
	 */
	protected $clientId;
	/**
	 * @var string google api client project email
	 * see https://console.developers.google.com
	 */
	protected $serviceAccountName;
	/**
	 * @var string path to .p12 file
	 * see https://console.developers.google.com
	 */
	protected $keyFile;

	/**
	 * @var array Additional field to be returned
	 */
	protected $additionalReturn;
	/**
	 * @var string path to result file
	 */
	protected $outFile;

	/**
	 * @var \Google_Service_Drive Google drive service object
	 */
	protected $service;


	/**
	 * @var int number of retry
	 * This is needed because sometimes for some weird reason
	 * API request returns 401, we just need to re-submit request
	 */
	protected $retry = 3;
	/**
	 * @var array result of requests
	 */
	public $result = [];

	/**
	 * @param array $config
	 * @param array $additionalReturn
	 * @param string $outFile
	 */
	public function __construct(array $config, array $additionalReturn = [], $outFile = null)
	{
		$this->clientId = $config['clientId'];
		$this->serviceAccountName = $config['serviceAccountName'];
		$this->keyFile = $config['keyFile'];
		$this->outFile = $outFile;
		$this->additionalReturn = $additionalReturn;
		$this->getGoogleService();
		if (file_exists($outFile))
			unlink($outFile);
	}

	/**
	 * Authenticate to Google API
	 */
	private function getGoogleService()
	{
		$config = new \Google_Config();
		$config->setIoClass('Google_IO_Curl');

		$client = new \Google_Client(
			$config
		);
		$client->setClientId($this->clientId);
		$client->setScopes(['https://www.googleapis.com/auth/drive']);
		$client->setAssertionCredentials(
			new \Google_Auth_AssertionCredentials(
				$this->serviceAccountName,
				['https://www.googleapis.com/auth/drive'],
				file_get_contents($this->keyFile)
			)
		);
		$this->service = new \Google_Service_Drive($client);
	}

	/**
	 * List all files in google drive
	 *
	 * @param string/null $retryPageToken set if we need to resume
	 */
	function listFiles($retryPageToken = null) {
		static $retry = null;
		if ($retry === null)
			$retry = $this->retry;

		$pageToken = $retryPageToken;
		do {
			try {
				$parameters = array();
				if ($pageToken) {
					$parameters['pageToken'] = $pageToken;
				}
				$files = $this->service->files->listFiles($parameters);

				$this->result = array_merge($this->result, $files->getItems());
				$pageToken = $files->getNextPageToken();
			} catch (\Exception $e) {
				if ($retry > 0) {
					$retry--;
					sleep(3);
					$this->listFiles($pageToken);
				} else {
					$pageToken = null;
					echo $e->getMessage() . PHP_EOL;
					exit(1);
				}
			}
		} while ($pageToken);

		unset($retry);
	}

	/**
	 * Fetch all needed files data & store then as csv file
	 *
	 * @param \Google_Service_Drive_DriveFile $file
	 */
	function fetchFile(\Google_Service_Drive_DriveFile $file)
	{
		static $retry = null;
		if ($retry === null)
			$retry = $this->retry;

		$data = '';
		try {
			$data = $this->getFullPath($file, true);
		} catch (\Exception $e) {
			if ($retry > 0) {
				$retry--;
				sleep(3);
				$this->fetchFile($file);
			} else {
				echo $e->getMessage() . PHP_EOL;
			}
		}

		if ($this->outFile && $data !== '')
			file_put_contents($this->outFile, $data . PHP_EOL, FILE_APPEND);

		unset($retry);
	}

	/**
	 * Get Folder tree of current $file
	 *
	 * @param \Google_Service_Drive_DriveFile $file
	 * @param bool $child
	 * @return string
	 */
	function getFullPath(\Google_Service_Drive_DriveFile $file, $child = false) {
		static $parentCache = [];
		$parents = $this->service->parents->listParents($file->id);
		if (count($parentList = $parents->getItems()) == 0)
			return '/' . $file->title;

		$parent = $parentList[0];
		if (!isset($parentCache[$parent->getId()])) {
			$fileParent = $this->service->files->get($parent->getId());
			$parentCache[$parent->getId()] = $fileParent;
		}
		else
			$fileParent = $parentCache[$parent->getId()];

		$additionalReturnStr = '';
		if ($child) {
			if (count($this->additionalReturn)) {
				$additionalReturnStr = '';
				foreach ($this->additionalReturn as $returnValue) {
					if (is_array($file->{$returnValue}))
						$additionalReturnStr .= ',' . implode(';', $file->{$returnValue});
					else
						$additionalReturnStr .= ',' . $file->{$returnValue};
				}
			}
		}

		return $this->getFullPath($fileParent) . '/' . $file->title . $additionalReturnStr;
	}
}