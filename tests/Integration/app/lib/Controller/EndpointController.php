<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControlTesting\Controller;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IDBConnection;
use OCP\IRequest;

class EndpointController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		protected IDBConnection $db,
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

		return new DataResponse();
	}
}
