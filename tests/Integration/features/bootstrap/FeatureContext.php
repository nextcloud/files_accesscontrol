<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 David Toledo <dtoledo@solidgear.es>
 * SPDX-FileCopyrightText: 2016 Sergio Bertolín <sbertolin@solidgear.es>
 * SPDX-FileCopyrightText: 2016 Thomas Müller <thomas.mueller@tmit.eu>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/WebDav.php';

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context {
	use WebDav;

	protected $regularUser = '123456';
	protected $adminUser = ['admin', 'admin'];

	/** @var string */
	protected $currentUser;

	/** @var ResponseInterface */
	private $response = null;

	/** @var CookieJar */
	private $cookieJar;

	/** @var string */
	protected $baseUrl;

	protected string $tagId = '';
	protected array $createdUsers = [];

	protected array $changedConfigs = [];

	/**
	 * FeatureContext constructor.
	 */
	public function __construct() {
		$this->cookieJar = new CookieJar();
		$this->baseUrl = getenv('TEST_SERVER_URL');
	}

	/**
	 * @BeforeScenario
	 * @AfterScenario
	 */
	public function cleanUpBetweenTests() {
		$this->setCurrentUser('admin');
		$this->sendingTo('DELETE', '/apps/files_accesscontrol_testing');
		$this->assertStatusCode($this->response, 200);

		foreach ($this->changedConfigs as $appId => $configs) {
			foreach ($configs as $config) {
				$this->sendingTo('DELETE', '/apps/provisioning_api/api/v1/config/apps/' . $appId . '/' . $config);
			}
		}
	}

	/**
	 * @AfterScenario
	 */
	public function tearDown() {
		foreach ($this->createdUsers as $user) {
			$this->deleteUser($user);
		}
	}

	/**
	 * @Given /^Ensure tag exists$/
	 */
	public function createTag() {
		$this->setCurrentUser('admin');
		$this->sendingTo('POST', '/apps/files_accesscontrol_testing');
		$this->assertStatusCode($this->response, 200);

		$ocsData = json_decode($this->response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
		$data = $ocsData['ocs']['data'];
		$this->tagId = $data['tagId'];
	}

	/**
	 * @Given /^user "([^"]*)" tags file "([^"]*)"$/
	 */
	public function tagFile(string $user, string $path) {
		// TODO: Remove all created tags?
		$this->setCurrentUser($user);
		$this->sendingToWith('POST', '/apps/files_accesscontrol_testing/tag-file', [
			'path' => $path,
		]);
		$this->assertStatusCode($this->response, 200);
	}

	/**
	 * @Given /^user "([^"]*)" creates (global|user) flow with (\d+)$/
	 */
	public function createFlow(string $user, string $scope, int $statusCode, TableNode $tableNode) {
		$this->setCurrentUser($user);

		$formData = $tableNode->getRowsHash();
		$checks = [];
		foreach ($formData as $key => $value) {
			if (strpos($key, 'checks-') === 0) {
				$value = str_replace('{{{FILES_ACCESSCONTROL_INTEGRATIONTEST_TAGID}}}', $this->tagId, $value);
				$checks[] = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
				unset($formData[$key]);
			}
		}

		$formData['checks'] = $checks;
		$formData['events'] = [];

		$this->sendingToWith('POST', '/apps/workflowengine/api/v1/workflows/' . $scope, $formData);
		Assert::assertSame($statusCode, $this->response->getStatusCode(), 'HTTP status code mismatch:' . "\n" . $this->response->getBody()->getContents());
	}

	/**
	 * @Given /^user "([^"]*)" shares file "([^"]*)" with user "([^"]*)"$/
	 */
	public function userSharesFile(string $sharer, string $file, string $sharee): void {
		$this->setCurrentUser($sharer);
		$this->sendingToWith('POST', '/apps/files_sharing/api/v1/shares', [
			'path' => $file,
			'permissions' => 19,
			'shareType' => 0,
			'shareWith' => $sharee,
		]);
	}

	/**
	 * @Given /^user "([^"]*)" shares file "([^"]*)" publicly$/
	 */
	public function userSharesFilePublicly(string $sharer, string $file): void {
		$this->setCurrentUser($sharer);
		$this->sendingToWith('POST', '/apps/files_sharing/api/v1/shares', [
			'path' => $file,
			'permissions' => 19,
			'shareType' => 3,
		]);
		$responseBody = json_decode($this->response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);
		$this->lastShareData = $responseBody['ocs']['data'];
	}

	// ChecksumsContext
	/**
	 * @Then The webdav response should have a status code :statusCode
	 * @param string $statusCode
	 * @throws \Exception
	 */
	public function theWebdavResponseShouldHaveAStatusCode($statusCode) {
		if (str_contains($statusCode, '|')) {
			$statusCodes = array_map('intval', explode('|', $statusCode));
		} else {
			$statusCodes = [(int)$statusCode];
		}
		if (!in_array($this->response->getStatusCode(), $statusCodes, true)) {
			throw new \Exception("Expected $statusCode, got " . $this->response->getStatusCode());
		}
	}


	#[\Behat\Step\Given('the following :appId app config is set')]
	public function setAppConfig(string $appId, TableNode $formData): void {
		$this->setCurrentUser('admin');
		foreach ($formData->getRows() as $row) {
			$this->sendingToWith('POST', '/apps/provisioning_api/api/v1/config/apps/' . $appId . '/' . $row[0], [
				'value' => $row[1],
			]);
			$this->changedConfigs[$appId][] = $row[0];
		}
	}

	/**
	 * User management
	 */

	/**
	 * @Given /^as user "([^"]*)"$/
	 * @param string $user
	 */
	public function setCurrentUser(string $user): void {
		$this->currentUser = $user;
	}

	/**
	 * @Given /^user "([^"]*)" exists$/
	 * @param string $user
	 */
	public function assureUserExists(string $user): void {
		try {
			$this->userExists($user);
		} catch (ClientException $ex) {
			$this->createUser($user);
		}
		$response = $this->userExists($user);
		$this->assertStatusCode($response, 200);
	}

	private function userExists(string $user): ResponseInterface {
		$client = new Client();
		$options = [
			'auth' => ['admin', 'admin'],
			'headers' => [
				'OCS-APIREQUEST' => 'true',
			],
		];
		return $client->get($this->baseUrl . 'ocs/v2.php/cloud/users/' . $user, $options);
	}

	private function createUser(string $user): void {
		$previous_user = $this->currentUser;
		$this->currentUser = 'admin';

		$userProvisioningUrl = $this->baseUrl . 'ocs/v2.php/cloud/users';
		$client = new Client();
		$options = [
			'auth' => ['admin', 'admin'],
			'form_params' => [
				'userid' => $user,
				'password' => '123456'
			],
			'headers' => [
				'OCS-APIREQUEST' => 'true',
			],
		];
		$client->post($userProvisioningUrl, $options);

		// Quick hack to log in once with the current user
		$options2 = [
			'auth' => [$user, '123456'],
			'headers' => [
				'OCS-APIREQUEST' => 'true',
			],
		];
		$client->get($userProvisioningUrl . '/' . $user, $options2);

		$this->createdUsers[] = $user;
		$this->currentUser = $previous_user;
	}

	private function deleteUser(string $user): void {
		$previous_user = $this->currentUser;
		$this->currentUser = 'admin';

		$userProvisioningUrl = $this->baseUrl . 'ocs/v2.php/cloud/users/' . $user;
		$client = new Client();
		$options = [
			'auth' => ['admin', 'admin'],
			'headers' => [
				'OCS-APIREQUEST' => 'true',
			],
		];
		$client->delete($userProvisioningUrl, $options);

		unset($this->createdUsers[$user]);

		$this->currentUser = $previous_user;
	}

	/*
	 * Requests
	 */

	/**
	 * @When /^sending "([^"]*)" to "([^"]*)"$/
	 * @param string $verb
	 * @param string $url
	 */
	public function sendingTo(string $verb, string $url): void {
		$this->sendingToWith($verb, $url, null);
	}

	/**
	 * @When /^sending "([^"]*)" to "([^"]*)" with$/
	 * @param string $verb
	 * @param string $url
	 * @param array|null $body
	 * @param array $headers
	 */
	public function sendingToWith(string $verb, string $url, ?array $body = null, array $headers = []): void {
		$fullUrl = $this->baseUrl . 'ocs/v2.php' . $url;
		$client = new Client();
		$options = [];

		if ($this->currentUser === 'admin') {
			$options['auth'] = [$this->currentUser, 'admin'];
		} else {
			$options['auth'] = [$this->currentUser, '123456'];
		}

		if (is_array($body)) {
			$options[RequestOptions::JSON] = $body;
		}

		$options['headers'] = array_merge($headers, [
			'OCS-APIREQUEST' => 'true',
			'Accept' => 'application/json',
		]);

		try {
			$this->response = $client->{$verb}($fullUrl, $options);
		} catch (ClientException $ex) {
			$this->response = $ex->getResponse();
		} catch (ServerException $ex) {
			$this->response = $ex->getResponse();
		}
	}

	/**
	 * @param ResponseInterface $response
	 * @param int $statusCode
	 */
	protected function assertStatusCode(ResponseInterface $response, int $statusCode): void {
		Assert::assertEquals($statusCode, $response->getStatusCode());
	}
}
