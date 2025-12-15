<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 Sergio Bertolín <sbertolin@solidgear.es>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use GuzzleHttp\Client as GClient;
use PHPUnit\Framework\Assert;
use Sabre\DAV\Client as SClient;
use Sabre\DAV\Xml\Property\ResourceType;

trait WebDav {
	/** @var string */
	private $davPath = 'remote.php/webdav';
	/** @var boolean */
	private $usingOldDavPath = true;
	/** @var array map with user as key and another map as value, which has path as key and etag as value */
	private $storedETAG = null;
	/** @var int */
	private $storedFileID = null;
	private array $trashedFiles = [];
	protected array $lastShareData = [];

	/**
	 * @Given /^using dav path "([^"]*)"$/
	 */
	public function usingDavPath($davPath) {
		$this->davPath = $davPath;
	}

	/**
	 * @Given /^using old dav path$/
	 */
	public function usingOldDavPath() {
		$this->davPath = 'remote.php/webdav';
		$this->usingOldDavPath = true;
	}

	/**
	 * @Given /^using new dav path$/
	 */
	public function usingNewDavPath() {
		$this->davPath = 'remote.php/dav';
		$this->usingOldDavPath = false;
	}

	public function getDavFilesPath($user) {
		if ($this->usingOldDavPath === true) {
			return $this->davPath;
		} else {
			return $this->davPath . '/files/' . $user;
		}
	}

	public function makeDavRequest($user, $method, $path, $headers, $body = null, $type = 'files') {
		if ($type === 'files') {
			$fullUrl = $this->baseUrl . $this->getDavFilesPath($user) . "$path";
		} elseif ($type === 'uploads') {
			$fullUrl = $this->baseUrl . $this->davPath . "$path";
		} elseif ($type === 'trashbin') {
			$fullUrl = $this->baseUrl . ltrim($path, '/');
		} else {
			$fullUrl = $this->baseUrl . $this->davPath . '/' . $type . "$path";
		}
		$client = new GClient();
		$options = [
			'headers' => $headers,
			'body' => $body
		];
		if ($user === 'admin') {
			$options['auth'] = $this->adminUser;
		} else {
			$options['auth'] = [$user, $this->regularUser];
		}
		return $client->request($method, $fullUrl, $options);
	}

	/**
	 * @Given /^User "([^"]*)" moved (file|folder|entry) "([^"]*)" to "([^"]*)"$/
	 * @param string $user
	 * @param string $fileSource
	 * @param string $fileDestination
	 */
	public function userMovedFile($user, $entry, $fileSource, $fileDestination) {
		$fullUrl = $this->baseUrl . $this->getDavFilesPath($user);
		$headers['Destination'] = $fullUrl . $fileDestination;
		$this->response = $this->makeDavRequest($user, 'MOVE', $fileSource, $headers);
		Assert::assertEquals(201, $this->response->getStatusCode());
	}

	/**
	 * @When /^User "([^"]*)" moves (file|folder|entry) "([^"]*)" to "([^"]*)"$/
	 * @param string $user
	 * @param string $fileSource
	 * @param string $fileDestination
	 */
	public function userMovesFile($user, $entry, $fileSource, $fileDestination) {
		$fullUrl = $this->baseUrl . $this->getDavFilesPath($user);
		$headers['Destination'] = $fullUrl . $fileDestination;
		try {
			$this->response = $this->makeDavRequest($user, 'MOVE', $fileSource, $headers);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When /^User "([^"]*)" copies (file|folder|entry) "([^"]*)" to "([^"]*)"$/
	 * @param string $user
	 * @param string $fileSource
	 * @param string $fileDestination
	 */
	public function userCopiesFileTo($user, $entry, $fileSource, $fileDestination) {
		$fullUrl = $this->baseUrl . $this->getDavFilesPath($user);
		$headers['Destination'] = $fullUrl . $fileDestination;
		try {
			$this->response = $this->makeDavRequest($user, 'COPY', $fileSource, $headers);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When /^Downloading file "([^"]*)" with range "([^"]*)"$/
	 * @param string $fileSource
	 * @param string $range
	 */
	public function downloadFileWithRange($fileSource, $range) {
		$headers['Range'] = $range;
		try {
			$this->response = $this->makeDavRequest($this->currentUser, 'GET', $fileSource, $headers);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When /^Downloading last public shared file with range "([^"]*)"$/
	 * @param string $range
	 */
	public function downloadPublicFileWithRange($range) {
		$token = $this->lastShareData['token'];
		$fullUrl = $this->baseUrl . 'public.php/webdav';

		$client = new GClient();
		$options = [];
		$options['auth'] = [$token, ''];
		$options['headers'] = [
			'Range' => $range
		];

		try {
			$this->response = $client->request('GET', $fullUrl, $options);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When /^Downloading last public shared file inside a folder "([^"]*)" with range "([^"]*)"$/
	 * @param string $range
	 */
	public function downloadPublicFileInsideAFolderWithRange($path, $range) {
		$token = $this->lastShareData['token'];
		$fullUrl = $this->baseUrl . 'public.php/webdav' . "$path";

		$client = new GClient();
		$options = [
			'headers' => [
				'Range' => $range
			]
		];
		$options['auth'] = [$token, ''];

		try {
			$this->response = $client->request('GET', $fullUrl, $options);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @Then /^Downloaded content should be "([^"]*)"$/
	 * @param string $content
	 */
	public function downloadedContentShouldBe($content) {
		Assert::assertEquals($content, (string)$this->response->getBody());
	}

	/**
	 * @Then /^File "([^"]*)" should have prop "([^"]*):([^"]*)" equal to "([^"]*)"$/
	 * @param string $file
	 * @param string $prefix
	 * @param string $prop
	 * @param string $value
	 */
	public function checkPropForFile($file, $prefix, $prop, $value) {
		$elementList = $this->propfindFile($this->currentUser, $file, "<$prefix:$prop/>");
		if ($prefix === 'oc') {
			$prefix = '{http://owncloud.org/ns}';
		} else {
			$prefix = '{DAV:}';
		}
		$property = $elementList['/' . $this->getDavFilesPath($this->currentUser) . $file][200]["$prefix$prop"];
		Assert::assertEquals($value, $property);
	}

	/**
	 * @Then /^Propfind for file "([^"]*)" prop "([^"]*):([^"]*)" fails with ([0-9]+) "([^"]*)"$/
	 */
	public function checkPropForFileFails(string $file, string $prefix, string $prop, int $status, string $message): void {
		$this->propfindFileFailed($this->currentUser, $file, "<$prefix:$prop/>");
		Assert::assertEquals($status, $this->response['statusCode']);
		Assert::assertStringContainsString($message, $this->response['body']);
	}

	/**
	 * @Then /^Image search should work$/
	 */
	public function search(): void {
		$this->searchFile($this->currentUser);
		Assert::assertEquals(207, $this->response->getStatusCode());
	}

	/**
	 * @Then /^Downloaded content when downloading file "([^"]*)" with range "([^"]*)" should be "([^"]*)"$/
	 * @param string $fileSource
	 * @param string $range
	 * @param string $content
	 */
	public function downloadedContentWhenDownloadindShouldBe($fileSource, $range, $content) {
		$this->downloadFileWithRange($fileSource, $range);
		$this->downloadedContentShouldBe($content);
	}

	/**
	 * @When Downloading file :fileName
	 * @param string $fileName
	 */
	public function downloadingFile($fileName) {
		try {
			$this->response = $this->makeDavRequest($this->currentUser, 'GET', $fileName, []);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When Downloading first trashed file
	 * @param string $fileName
	 */
	public function downloadingTrashedFile() {
		try {
			$this->response = $this->makeDavRequest($this->currentUser, 'GET', array_shift($this->trashedFiles), [], type: 'trashbin');
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @Then Downloaded content should start with :start
	 * @param int $start
	 * @throws \Exception
	 */
	public function downloadedContentShouldStartWith($start) {
		if (strpos($this->response->getBody()->getContents(), $start) !== 0) {
			throw new \Exception(
				sprintf(
					"Expected '%s', got '%s'",
					$start,
					$this->response->getBody()->getContents()
				)
			);
		}
	}

	/**
	 * @Then /^as "([^"]*)" gets properties of (file|folder|entry) "([^"]*)" with$/
	 * @param string $user
	 * @param string $elementType
	 * @param string $path
	 * @param \Behat\Gherkin\Node\TableNode|null $propertiesTable
	 */
	public function asGetsPropertiesOfFolderWith($user, $elementType, $path, $propertiesTable) {
		$properties = null;
		if ($propertiesTable instanceof \Behat\Gherkin\Node\TableNode) {
			foreach ($propertiesTable->getRows() as $row) {
				$properties[] = $row[0];
			}
		}
		$this->response = $this->listFolder($user, $path, 0, $properties);
	}

	/**
	 * @Then /^as "([^"]*)" the (file|folder|entry) "([^"]*)" does not exist$/
	 * @param string $user
	 * @param string $entry
	 * @param string $path
	 * @param \Behat\Gherkin\Node\TableNode|null $propertiesTable
	 */
	public function asTheFileOrFolderDoesNotExist($user, $entry, $path) {
		$client = $this->getSabreClient($user);
		$response = $client->request('HEAD', $this->makeSabrePath($user, $path));
		if ($response['statusCode'] !== 404) {
			throw new \Exception($entry . ' "' . $path . '" expected to not exist (status code ' . $response['statusCode'] . ', expected 404)');
		}

		return $response;
	}

	/**
	 * @Then /^as "([^"]*)" the (file|folder|entry) "([^"]*)" exists$/
	 * @param string $user
	 * @param string $entry
	 * @param string $path
	 */
	public function asTheFileOrFolderExists($user, $entry, $path) {
		$this->response = $this->listFolder($user, $path, 0);
	}

	/**
	 * @Then the single response should contain a property :key with value :value
	 * @param string $key
	 * @param string $expectedValue
	 * @throws \Exception
	 */
	public function theSingleResponseShouldContainAPropertyWithValue($key, $expectedValue) {
		$keys = $this->response;
		if (!array_key_exists($key, $keys)) {
			throw new \Exception("Cannot find property \"$key\" with \"$expectedValue\"");
		}

		$value = $keys[$key];
		if ($value instanceof ResourceType) {
			$value = $value->getValue();
			if (empty($value)) {
				$value = '';
			} else {
				$value = $value[0];
			}
		}
		if ($value != $expectedValue) {
			throw new \Exception("Property \"$key\" found with value \"$value\", expected \"$expectedValue\"");
		}
	}

	/**
	 * @Then the response should contain a share-types property with
	 */
	public function theResponseShouldContainAShareTypesPropertyWith($table) {
		$keys = $this->response;
		if (!array_key_exists('{http://owncloud.org/ns}share-types', $keys)) {
			throw new \Exception('Cannot find property "{http://owncloud.org/ns}share-types"');
		}

		$foundTypes = [];
		$data = $keys['{http://owncloud.org/ns}share-types'];
		foreach ($data as $item) {
			if ($item['name'] !== '{http://owncloud.org/ns}share-type') {
				throw new \Exception('Invalid property found: "' . $item['name'] . '"');
			}

			$foundTypes[] = $item['value'];
		}

		foreach ($table->getRows() as $row) {
			$key = array_search($row[0], $foundTypes);
			if ($key === false) {
				throw new \Exception('Expected type ' . $row[0] . ' not found');
			}

			unset($foundTypes[$key]);
		}

		if ($foundTypes !== []) {
			throw new \Exception('Found more share types then specified: ' . $foundTypes);
		}
	}

	/**
	 * @Then the response should contain an empty property :property
	 * @param string $property
	 * @throws \Exception
	 */
	public function theResponseShouldContainAnEmptyProperty($property) {
		$properties = $this->response;
		if (!array_key_exists($property, $properties)) {
			throw new \Exception("Cannot find property \"$property\"");
		}

		if ($properties[$property] !== null) {
			throw new \Exception("Property \"$property\" is not empty");
		}
	}

	/**
	 * Returns the elements of a propfind, $folderDepth requires 1 to see elements without children
	 */
	public function listFolder($user, $path, $folderDepth, $properties = null) {
		$client = $this->getSabreClient($user);
		if (!$properties) {
			$properties = [
				'{DAV:}getetag'
			];
		}

		$response = $client->propfind($this->makeSabrePath($user, $path), $properties, $folderDepth);

		return $response;
	}

	/**
	 * Returns the elements of a propfind command
	 * @param string $properties properties which needs to be included in the report
	 */
	public function propfindFile(string $user, string $path, string $properties = ''): array {
		$client = $this->getSabreClient($user);

		$body = '<?xml version="1.0" encoding="utf-8" ?>
					<d:propfind  xmlns:d="DAV:"
						xmlns:oc="http://owncloud.org/ns"
						xmlns:nc="http://nextcloud.org/ns"
						xmlns:ocs="http://open-collaboration-services.org/ns">
						<d:prop>
							' . $properties . '
						</d:prop>
					</d:propfind>';

		$response = $client->request('PROPFIND', $this->makeSabrePath($user, $path), $body);
		$parsedResponse = $client->parseMultistatus($response['body']);
		return $parsedResponse;
	}

	/**
	 * Returns the elements of a propfind command
	 * @param string $properties properties which needs to be included in the report
	 */
	public function propfindFileFailed(string $user, string $path, string $properties = ''): void {
		$client = $this->getSabreClient($user);

		$body = '<?xml version="1.0" encoding="utf-8" ?>
					<d:propfind  xmlns:d="DAV:"
						xmlns:oc="http://owncloud.org/ns"
						xmlns:nc="http://nextcloud.org/ns"
						xmlns:ocs="http://open-collaboration-services.org/ns">
						<d:prop>
							' . $properties . '
						</d:prop>
					</d:propfind>';

		$this->response = $client->request('PROPFIND', $this->makeSabrePath($user, $path), $body);
	}

	/**
	 * Returns the elements of a search command
	 * @param string $properties properties which needs to be included in the report
	 * @param string $filterRules filter-rules to choose what needs to appear in the report
	 */
	public function searchFile(string $user, ?string $properties = null, ?string $scope = null, ?string $condition = null) {
		$client = $this->getSabreClient($user);

		if ($properties === null) {
			$properties = '<oc:fileid /> <d:getlastmodified /> <d:getetag />	<d:getcontenttype /> <d:getcontentlength /> <nc:has-preview /> <oc:favorite /> <d:resourcetype />';
		}

		if ($condition === null) {
			$condition = '<d:and>
	<d:or>
		<d:eq>
			<d:prop>
				<d:getcontenttype/>
			</d:prop>
			<d:literal>image/png</d:literal>
		</d:eq>

		<d:eq>
			<d:prop>
				<d:getcontenttype/>
			</d:prop>
			<d:literal>image/jpeg</d:literal>
		</d:eq>

		<d:eq>
			<d:prop>
				<d:getcontenttype/>
			</d:prop>
			<d:literal>image/heic</d:literal>
		</d:eq>

		<d:eq>
			<d:prop>
				<d:getcontenttype/>
			</d:prop>
			<d:literal>video/mp4</d:literal>
		</d:eq>

		<d:eq>
			<d:prop>
				<d:getcontenttype/>
			</d:prop>
			<d:literal>video/quicktime</d:literal>
		</d:eq>
	</d:or>
	<d:eq>
		<d:prop>
			<oc:owner-id/>
		</d:prop>
		<d:literal>' . $user . '</d:literal>
	</d:eq>
</d:and>';
		}

		if ($scope === null) {
			$scope = '<d:href>/files/' . $user . '</d:href><d:depth>infinity</d:depth>';
		}

		$body = '<?xml version="1.0" encoding="UTF-8"?>
<d:searchrequest xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns" xmlns:ns="https://github.com/icewind1991/SearchDAV/ns" xmlns:ocs="http://open-collaboration-services.org/ns">
	<d:basicsearch>
		<d:select>
			<d:prop>' . $properties . '</d:prop>
		</d:select>
		<d:from><d:scope>' . $scope . '</d:scope></d:from>
		<d:where>' . $condition . '</d:where>
		<d:orderby>
			<d:order>
				<d:prop><d:getlastmodified/></d:prop>
				<d:descending/>
			</d:order>
		</d:orderby>
		<d:limit>
			<d:nresults>35</d:nresults>
			<ns:firstresult>0</ns:firstresult>
		</d:limit>
	</d:basicsearch>
</d:searchrequest>';

		try {
			$this->response = $this->makeDavRequest($user, 'SEARCH', '', [
				'Content-Type' => 'text/xml'
			], $body, '');
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 5xx responses cause a server exception
			$this->response = $e->getResponse();
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			// 4xx responses cause a client exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * Returns the elements of a report command
	 * @param string $user
	 * @param string $path
	 * @param string $properties properties which needs to be included in the report
	 * @param string $filterRules filter-rules to choose what needs to appear in the report
	 */
	public function reportFolder($user, $path, $properties, $filterRules) {
		$client = $this->getSabreClient($user);

		$body = '<?xml version="1.0" encoding="utf-8" ?>
							 <oc:filter-files xmlns:a="DAV:" xmlns:oc="http://owncloud.org/ns" >
								 <a:prop>
									' . $properties . '
								 </a:prop>
								 <oc:filter-rules>
									' . $filterRules . '
								 </oc:filter-rules>
							 </oc:filter-files>';

		$response = $client->request('REPORT', $this->makeSabrePath($user, $path), $body);
		$parsedResponse = $client->parseMultistatus($response['body']);
		return $parsedResponse;
	}

	public function makeSabrePath($user, $path, $type = 'files') {
		if ($type === 'files') {
			return $this->encodePath($this->getDavFilesPath($user) . $path);
		} else {
			return $this->encodePath($this->davPath . '/' . $type . '/' . $user . '/' . $path);
		}
	}

	public function getSabreClient($user) {
		$fullUrl = $this->baseUrl;

		$settings = [
			'baseUri' => $fullUrl,
			'userName' => $user,
		];

		if ($user === 'admin') {
			$settings['password'] = $this->adminUser[1];
		} else {
			$settings['password'] = $this->regularUser;
		}
		$settings['authType'] = SClient::AUTH_BASIC;

		return new SClient($settings);
	}

	/**
	 * @Then /^user "([^"]*)" should see following elements$/
	 * @param string $user
	 * @param \Behat\Gherkin\Node\TableNode|null $expectedElements
	 */
	public function checkElementList($user, $expectedElements) {
		$elementList = $this->listFolder($user, '/', 3);
		if ($expectedElements instanceof \Behat\Gherkin\Node\TableNode) {
			$elementRows = $expectedElements->getRows();
			$elementsSimplified = $this->simplifyArray($elementRows);
			foreach ($elementsSimplified as $expectedElement) {
				$webdavPath = '/' . $this->getDavFilesPath($user) . $expectedElement;
				if (!array_key_exists($webdavPath, $elementList)) {
					Assert::fail("$webdavPath" . ' is not in propfind answer');
				}
			}
		}
	}

	/**
	 * @When User :user uploads file :source to :destination
	 * @param string $user
	 * @param string $source
	 * @param string $destination
	 */
	public function userUploadsAFileTo($user, $source, $destination) {
		$file = \GuzzleHttp\Psr7\Utils::streamFor(fopen($source, 'r'));
		try {
			$this->response = $this->makeDavRequest($user, 'PUT', $destination, [], $file);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 5xx responses cause a server exception
			$this->response = $e->getResponse();
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			// 4xx responses cause a client exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When User :user adds a file of :bytes bytes to :destination
	 * @param string $user
	 * @param string $bytes
	 * @param string $destination
	 */
	public function userAddsAFileTo($user, $bytes, $destination) {
		$filename = 'filespecificSize.txt';
		$this->createFileSpecificSize($filename, $bytes);
		Assert::assertEquals(1, file_exists("work/$filename"));
		$this->userUploadsAFileTo($user, "work/$filename", $destination);
		$this->removeFile('work/', $filename);
		$expectedElements = new \Behat\Gherkin\Node\TableNode([["$destination"]]);
		$this->checkElementList($user, $expectedElements);
	}

	/**
	 * @When User :user uploads file with content :content to :destination
	 */
	public function userUploadsAFileWithContentTo($user, $content, $destination) {
		$file = \GuzzleHttp\Psr7\Utils::streamFor($content);
		try {
			$this->response = $this->makeDavRequest($user, 'PUT', $destination, [], $file);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 5xx responses cause a server exception
			$this->response = $e->getResponse();
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			// 4xx responses cause a client exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When /^User "([^"]*)" deletes (file|folder) "([^"]*)"$/
	 * @param string $user
	 * @param string $type
	 * @param string $file
	 */
	public function userDeletesFile($user, $type, $file) {
		try {
			$this->response = $this->makeDavRequest($user, 'DELETE', $file, []);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 5xx responses cause a server exception
			$this->response = $e->getResponse();
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			// 4xx responses cause a client exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @When User "([^"]*)" empties trashbin
	 * @param string $user user
	 */
	public function emptyTrashbin($user) {
		$client = $this->getSabreClient($user);
		$response = $client->request('DELETE', $this->makeSabrePath($user, 'trash', 'trashbin'));
		Assert::assertEquals(204, $response['statusCode']);
	}

	/**
	 * @Given User :user created a folder :destination
	 * @param string $user
	 * @param string $destination
	 */
	public function userCreatedAFolder($user, $destination) {
		try {
			$destination = '/' . ltrim($destination, '/');
			$this->response = $this->makeDavRequest($user, 'MKCOL', $destination, []);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 5xx responses cause a server exception
			$this->response = $e->getResponse();
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			// 4xx responses cause a client exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @Given user :user uploads chunk file :num of :total with :data to :destination
	 * @param string $user
	 * @param int $num
	 * @param int $total
	 * @param string $data
	 * @param string $destination
	 */
	public function userUploadsChunkFileOfWithToWithChecksum($user, $num, $total, $data, $destination) {
		$num -= 1;
		$data = \GuzzleHttp\Psr7\Utils::streamFor($data);
		$file = $destination . '-chunking-42-' . $total . '-' . $num;
		$this->makeDavRequest($user, 'PUT', $file, ['OC-Chunked' => '1'], $data, 'uploads');
	}

	/**
	 * @Given user :user uploads bulked files :name1 with :content1 and :name2 with :content2 and :name3 with :content3
	 * @param string $user
	 * @param string $name1
	 * @param string $content1
	 * @param string $name2
	 * @param string $content2
	 * @param string $name3
	 * @param string $content3
	 */
	public function userUploadsBulkedFiles($user, $name1, $content1, $name2, $content2, $name3, $content3) {
		$boundary = 'boundary_azertyuiop';

		$body = '';
		$body .= '--' . $boundary . "\r\n";
		$body .= 'X-File-Path: ' . $name1 . "\r\n";
		$body .= "X-File-MD5: f6a6263167c92de8644ac998b3c4e4d1\r\n";
		$body .= "X-OC-Mtime: 1111111111\r\n";
		$body .= 'Content-Length: ' . strlen($content1) . "\r\n";
		$body .= "\r\n";
		$body .= $content1 . "\r\n";
		$body .= '--' . $boundary . "\r\n";
		$body .= 'X-File-Path: ' . $name2 . "\r\n";
		$body .= "X-File-MD5: 87c7d4068be07d390a1fffd21bf1e944\r\n";
		$body .= "X-OC-Mtime: 2222222222\r\n";
		$body .= 'Content-Length: ' . strlen($content2) . "\r\n";
		$body .= "\r\n";
		$body .= $content2 . "\r\n";
		$body .= '--' . $boundary . "\r\n";
		$body .= 'X-File-Path: ' . $name3 . "\r\n";
		$body .= "X-File-MD5: e86a1cf0678099986a901c79086f5617\r\n";
		$body .= "X-File-Mtime: 3333333333\r\n";
		$body .= 'Content-Length: ' . strlen($content3) . "\r\n";
		$body .= "\r\n";
		$body .= $content3 . "\r\n";
		$body .= '--' . $boundary . "--\r\n";

		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $body);
		rewind($stream);

		$client = new GClient();
		$options = [
			'auth' => [$user, $this->regularUser],
			'headers' => [
				'Content-Type' => 'multipart/related; boundary=' . $boundary,
				'Content-Length' => (string)strlen($body),
			],
			'body' => $body
		];

		return $client->request('POST', $this->baseUrl . 'remote.php/dav/bulk', $options);
	}

	/**
	 * @Given user :user creates a new chunking upload with id :id
	 */
	public function userCreatesANewChunkingUploadWithId($user, $id) {
		$destination = '/uploads/' . $user . '/' . $id;
		$this->makeDavRequest($user, 'MKCOL', $destination, [], null, 'uploads');
	}

	/**
	 * @Given user :user uploads new chunk file :num with :data to id :id
	 */
	public function userUploadsNewChunkFileOfWithToId($user, $num, $data, $id) {
		$data = \GuzzleHttp\Psr7\Utils::streamFor($data);
		$destination = '/uploads/' . $user . '/' . $id . '/' . $num;
		$this->makeDavRequest($user, 'PUT', $destination, [], $data, 'uploads');
	}

	/**
	 * @Given user :user moves new chunk file with id :id to :dest
	 */
	public function userMovesNewChunkFileWithIdToMychunkedfile($user, $id, $dest) {
		$source = '/uploads/' . $user . '/' . $id . '/.file';
		$destination = $this->baseUrl . $this->getDavFilesPath($user) . $dest;
		$this->makeDavRequest($user, 'MOVE', $source, [
			'Destination' => $destination
		], null, 'uploads');
	}

	/**
	 * @Then user :user moves new chunk file with id :id to :dest with size :size
	 */
	public function userMovesNewChunkFileWithIdToMychunkedfileWithSize($user, $id, $dest, $size) {
		$source = '/uploads/' . $user . '/' . $id . '/.file';
		$destination = $this->baseUrl . $this->getDavFilesPath($user) . $dest;

		try {
			$this->response = $this->makeDavRequest($user, 'MOVE', $source, [
				'Destination' => $destination,
				'OC-Total-Length' => $size
			], null, 'uploads');
		} catch (\GuzzleHttp\Exception\BadResponseException $ex) {
			$this->response = $ex->getResponse();
		}
	}

	/**
	 * @Given /^Downloading file "([^"]*)" as "([^"]*)"$/
	 */
	public function downloadingFileAs($fileName, $user) {
		try {
			$this->response = $this->makeDavRequest($user, 'GET', $fileName, []);
		} catch (\GuzzleHttp\Exception\ServerException $e) {
			// 5xx responses cause a server exception
			$this->response = $e->getResponse();
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			// 4xx responses cause a client exception
			$this->response = $e->getResponse();
		}
	}

	/**
	 * URL encodes the given path but keeps the slashes
	 *
	 * @param string $path to encode
	 * @return string encoded path
	 */
	private function encodePath($path) {
		// slashes need to stay
		return str_replace('%2F', '/', rawurlencode($path));
	}

	/**
	 * @When user :user favorites element :path
	 */
	public function userFavoritesElement($user, $path) {
		$this->response = $this->changeFavStateOfAnElement($user, $path, 1, 0, null);
	}

	/**
	 * @When user :user unfavorites element :path
	 */
	public function userUnfavoritesElement($user, $path) {
		$this->response = $this->changeFavStateOfAnElement($user, $path, 0, 0, null);
	}

	/*Set the elements of a proppatch, $folderDepth requires 1 to see elements without children*/
	public function changeFavStateOfAnElement($user, $path, $favOrUnfav, $folderDepth, $properties = null) {
		$fullUrl = $this->baseUrl;
		$settings = [
			'baseUri' => $fullUrl,
			'userName' => $user,
		];
		if ($user === 'admin') {
			$settings['password'] = $this->adminUser[1];
		} else {
			$settings['password'] = $this->regularUser;
		}
		$settings['authType'] = SClient::AUTH_BASIC;

		$client = new SClient($settings);
		if (!$properties) {
			$properties = [
				'{http://owncloud.org/ns}favorite' => $favOrUnfav
			];
		}

		$response = $client->proppatch($this->getDavFilesPath($user) . $path, $properties, $folderDepth);
		return $response;
	}

	/**
	 * @Given user :user stores etag of element :path
	 */
	public function userStoresEtagOfElement($user, $path) {
		$propertiesTable = new \Behat\Gherkin\Node\TableNode([['{DAV:}getetag']]);
		$this->asGetsPropertiesOfFolderWith($user, 'entry', $path, $propertiesTable);
		$pathETAG[$path] = $this->response['{DAV:}getetag'];
		$this->storedETAG[$user] = $pathETAG;
	}

	/**
	 * @Then etag of element :path of user :user has not changed
	 */
	public function checkIfETAGHasNotChanged($path, $user) {
		$propertiesTable = new \Behat\Gherkin\Node\TableNode([['{DAV:}getetag']]);
		$this->asGetsPropertiesOfFolderWith($user, 'entry', $path, $propertiesTable);
		Assert::assertEquals($this->response['{DAV:}getetag'], $this->storedETAG[$user][$path]);
	}

	/**
	 * @Then etag of element :path of user :user has changed
	 */
	public function checkIfETAGHasChanged($path, $user) {
		$propertiesTable = new \Behat\Gherkin\Node\TableNode([['{DAV:}getetag']]);
		$this->asGetsPropertiesOfFolderWith($user, 'entry', $path, $propertiesTable);
		Assert::assertNotEquals($this->response['{DAV:}getetag'], $this->storedETAG[$user][$path]);
	}

	/**
	 * @When Connecting to dav endpoint
	 */
	public function connectingToDavEndpoint() {
		try {
			$this->response = $this->makeDavRequest(null, 'PROPFIND', '', []);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			$this->response = $e->getResponse();
		}
	}

	/**
	 * @Then there are no duplicate headers
	 */
	public function thereAreNoDuplicateHeaders() {
		$headers = $this->response->getHeaders();
		foreach ($headers as $headerName => $headerValues) {
			// if a header has multiple values, they must be different
			if (count($headerValues) > 1 && count(array_unique($headerValues)) < count($headerValues)) {
				throw new \Exception('Duplicate header found: ' . $headerName);
			}
		}
	}

	/**
	 * @Then /^user "([^"]*)" in folder "([^"]*)" should have favorited the following elements$/
	 * @param string $user
	 * @param string $folder
	 * @param \Behat\Gherkin\Node\TableNode|null $expectedElements
	 */
	public function checkFavoritedElements($user, $folder, $expectedElements) {
		$elementList = $this->reportFolder($user,
			$folder,
			'<oc:favorite/>',
			'<oc:favorite>1</oc:favorite>');
		if ($expectedElements instanceof \Behat\Gherkin\Node\TableNode) {
			$elementRows = $expectedElements->getRows();
			$elementsSimplified = $this->simplifyArray($elementRows);
			foreach ($elementsSimplified as $expectedElement) {
				$webdavPath = '/' . $this->getDavFilesPath($user) . $expectedElement;
				if (!array_key_exists($webdavPath, $elementList)) {
					Assert::fail("$webdavPath" . ' is not in report answer');
				}
			}
		}
	}

	/**
	 * @When /^User "([^"]*)" deletes everything from folder "([^"]*)"$/
	 * @param string $user
	 * @param string $folder
	 */
	public function userDeletesEverythingInFolder($user, $folder) {
		$elementList = $this->listFolder($user, $folder, 1);
		$elementListKeys = array_keys($elementList);
		array_shift($elementListKeys);
		$davPrefix = '/' . $this->getDavFilesPath($user);
		foreach ($elementListKeys as $element) {
			if (substr($element, 0, strlen($davPrefix)) == $davPrefix) {
				$element = substr($element, strlen($davPrefix));
			}
			$this->userDeletesFile($user, 'element', $element);
		}
	}

	/**
	 * @Then /^User "([^"]*)" sees no files in the trashbin$/
	 * @param string $user
	 */
	public function userSeesNoFilesInTheTrashbin($user) {
		$client = $this->getSabreClient($user);
		$properties = [
			'{DAV:}getetag'
		];

		$response = $client->propfind($this->makeSabrePath($user, 'trash', 'trashbin'), $properties, 1);

		// Remove the root entry to only have the directory listing
		unset($response['/remote.php/dav/trashbin/' . $user . '/trash/']);

		$count = count($response);
		if ($count !== 0) {
			var_dump($response);
		}

		Assert::assertEquals(0, $count);
	}

	/**
	 * @Then /^User "([^"]*)" loads file list of trashbin$/
	 * @param string $user
	 */
	public function userLoadsFileListOfTrashbin($user) {
		$client = $this->getSabreClient($user);
		$properties = [
			'{DAV:}getetag'
		];

		$response = $client->propfind($this->makeSabrePath($user, 'trash', 'trashbin'), $properties, 1);

		// Remove the root entry to only have the directory listing
		unset($response['/remote.php/dav/trashbin/' . $user . '/trash/']);

		Assert::assertNotCount(0, $response);
		$this->trashedFiles = array_keys($response);
	}


	/**
	 * @param string $user
	 * @param string $path
	 * @return int
	 */
	private function getFileIdForPath($user, $path) {
		$propertiesTable = new \Behat\Gherkin\Node\TableNode([['{http://owncloud.org/ns}fileid']]);
		$this->asGetsPropertiesOfFolderWith($user, 'file', $path, $propertiesTable);
		return (int)$this->response['{http://owncloud.org/ns}fileid'];
	}

	/**
	 * @Given /^User "([^"]*)" stores id of file "([^"]*)"$/
	 * @param string $user
	 * @param string $path
	 */
	public function userStoresFileIdForPath($user, $path) {
		$this->storedFileID = $this->getFileIdForPath($user, $path);
	}

	/**
	 * @Given /^User "([^"]*)" checks id of file "([^"]*)"$/
	 * @param string $user
	 * @param string $path
	 */
	public function userChecksFileIdForPath($user, $path) {
		$currentFileID = $this->getFileIdForPath($user, $path);
		Assert::assertEquals($currentFileID, $this->storedFileID);
	}

	/**
	 * This function is needed to use a vertical fashion in the gherkin tables.
	 *
	 * @param array $arrayOfArrays
	 * @return array
	 */
	public function simplifyArray($arrayOfArrays) {
		$a = array_map(function ($subArray) {
			return $subArray[0];
		}, $arrayOfArrays);
		return $a;
	}
}
