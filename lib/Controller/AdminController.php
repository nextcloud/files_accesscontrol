<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
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

namespace OCA\FilesAccessControl\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AdminController extends Controller {

	/** @var EventDispatcher */
	protected $eventDispatcher;
	/** @var IL10N */
	protected $l10n;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param EventDispatcherInterface $eventDispatcher
	 * @param IL10N $l10n
	 */
	public function __construct($appName,
								IRequest $request,
								EventDispatcherInterface $eventDispatcher,
								IL10N $l10n) {
		parent::__construct($appName, $request);

		$this->eventDispatcher = $eventDispatcher;
		$this->l10n = $l10n;
	}

	/**
	 * @return TemplateResponse
	 */
	public function index() {
		$this->eventDispatcher->dispatch('OCP\WorkflowEngine::loadAdditionalSettingScripts');
		\OCP\Util::addScript($this->appName, 'admin');
		return new TemplateResponse('workflowengine', 'admin', [
			'appid' => $this->appName,
			'heading' => $this->l10n->t('Files Access Control'),
		], 'blank');
	}
}
