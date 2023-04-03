<?php

declare(strict_types=1);
/**
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FilesAccessControlTesting\Controller;

use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IRequest;

class EndpointController extends OCSController {
	protected IDBConnection $db;
	public function __construct(
		string $appName,
		IRequest $request,
		IDBConnection $db
	) {
		parent::__construct($appName, $request);
		$this->db = $db;
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
