<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControlTesting\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;

class EndpointController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		protected IDBConnection $db,
		protected ISystemTagManager $systemTagManager,
		protected ISystemTagObjectMapper $systemTagObjectMapper,
		protected IRootFolder $rootFolder,
		protected string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return DataResponse
	 */
	public function reset(): DataResponse {
		$query = $this->db->getQueryBuilder();

		$query->delete('flow_checks')->executeStatement();
		$query->delete('flow_operations')->executeStatement();
		$query->delete('flow_operations_scope')->executeStatement();


		try {
			$tag = $this->systemTagManager->getTag('files_accesscontrol_intergrationtest', true, true);
			$this->systemTagManager->deleteTags([$tag->getId()]);
		} catch (TagNotFoundException) {
		}

		return new DataResponse();
	}

	/**
	 * @return DataResponse
	 */
	public function prepare(): DataResponse {
		try {
			$tag = $this->systemTagManager->getTag('files_accesscontrol_intergrationtest', true, true);
		} catch (TagNotFoundException) {
			$tag = $this->systemTagManager->createTag('files_accesscontrol_intergrationtest', true, true);
		}
		return new DataResponse(['tagId' => $tag->getId()]);
	}

	/**
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function tagFile(string $path): DataResponse {
		$tag = $this->systemTagManager->getTag('files_accesscontrol_intergrationtest', true, true);

		$userFolder = $this->rootFolder->getUserFolder($this->userId);
		$node = $userFolder->get($path);
		$this->systemTagObjectMapper->assignTags((string)$node->getId(), 'files', [$tag->getId()]);

		return new DataResponse();
	}
}
