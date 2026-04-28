<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControl\Tests\Unit;

use OCA\FilesAccessControl\Operation;
use OCA\WorkflowEngine\Entity\File;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\ForbiddenException;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class OperationTest extends TestCase {
	private IManager&MockObject $manager;
	private IRuleMatcher&MockObject $ruleMatcher;
	private IMountPoint&MockObject $mountPoint;
	private IStorage&MockObject $storage;
	private Operation&MockObject $operation;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(IManager::class);
		$this->ruleMatcher = $this->createMock(IRuleMatcher::class);
		$this->storage = $this->createMock(IStorage::class);
		$this->mountPoint = $this->createMock(IMountPoint::class);

		$this->mountPoint->method('getMountPoint')->willReturn('/mount/');
		$this->mountPoint->method('getStorage')->willReturn($this->storage);

		// Make getNode() return null by having the cache return nothing
		$cache = $this->createMock(ICache::class);
		$cache->method('get')->willReturn(null);
		$this->storage->method('getCache')->willReturn($cache);

		$this->manager->method('getRuleMatcher')->willReturn($this->ruleMatcher);
		$this->ruleMatcher->method('setFileInfo');
		$this->ruleMatcher->method('setOperation');

		$this->operation = $this->getMockBuilder(Operation::class)
			->setConstructorArgs([
				$this->manager,
				$this->createMock(IL10N::class),
				$this->createMock(IURLGenerator::class),
				$this->createMock(File::class),
				$this->createMock(IMountManager::class),
				$this->createMock(IRootFolder::class),
				$this->createMock(LoggerInterface::class),
			])
			->onlyMethods(['isBlockablePath', 'isCreatingSkeletonFiles', 'translatePath'])
			->getMock();

		$this->operation->method('isCreatingSkeletonFiles')->willReturn(false);
		$this->operation->method('translatePath')->willReturn('path');
	}

	public function testCheckFileAccessWithNoMatchingFlowsReturnsNull(): void {
		$this->operation->method('isBlockablePath')->willReturn(true);
		$this->ruleMatcher->method('getFlows')->willReturn([]);

		$result = $this->operation->checkFileAccess('path', $this->mountPoint, false);

		$this->assertNull($result);
	}

	public function testCheckFileAccessWithNonBlockablePathReturnsNull(): void {
		$this->operation->method('isBlockablePath')->willReturn(false);
		$this->ruleMatcher->expects($this->never())->method('getFlows');

		$result = $this->operation->checkFileAccess('path', $this->mountPoint, false);

		$this->assertNull($result);
	}

	public function testCheckFileAccessWithDenyOperationThrowsForbidden(): void {
		$this->operation->method('isBlockablePath')->willReturn(true);
		$this->ruleMatcher->method('getFlows')->willReturn([
			['operation' => 'deny'],
		]);

		$this->expectException(ForbiddenException::class);
		$this->operation->checkFileAccess('path', $this->mountPoint, false);
	}

	public function testCheckFileAccessWithDenyOperationAfterGrantedPermissionsStillThrows(): void {
		$this->operation->method('isBlockablePath')->willReturn(true);
		$this->ruleMatcher->method('getFlows')->willReturn([
			['operation' => json_encode(['permissions' => Constants::PERMISSION_ALL])],
			['operation' => 'deny'],
		]);

		$this->expectException(ForbiddenException::class);
		$this->operation->checkFileAccess('path', $this->mountPoint, false, null, Constants::PERMISSION_READ);
	}

	public function testCheckFileAccessWithSufficientPermissionsReturnsComputedPermissions(): void {
		$this->operation->method('isBlockablePath')->willReturn(true);
		$grantedPermissions = Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE;
		$this->ruleMatcher->method('getFlows')->willReturn([
			['operation' => json_encode(['permissions' => $grantedPermissions])],
		]);

		$result = $this->operation->checkFileAccess('path', $this->mountPoint, false, null, Constants::PERMISSION_READ);

		$this->assertSame($grantedPermissions, $result);
	}

	public function testCheckFileAccessWithPermissionsAccumulatedAcrossRules(): void {
		$this->operation->method('isBlockablePath')->willReturn(true);
		$this->ruleMatcher->method('getFlows')->willReturn([
			['operation' => json_encode(['permissions' => Constants::PERMISSION_READ])],
			['operation' => json_encode(['permissions' => Constants::PERMISSION_UPDATE])],
		]);

		$result = $this->operation->checkFileAccess('path', $this->mountPoint, false, null, Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE);

		$this->assertSame(Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE, $result);
	}

	public function testCheckFileAccessWithInsufficientPermissionsThrowsForbidden(): void {
		$this->operation->method('isBlockablePath')->willReturn(true);
		$this->ruleMatcher->method('getFlows')->willReturn([
			['operation' => json_encode(['permissions' => Constants::PERMISSION_READ])],
		]);

		$this->expectException(ForbiddenException::class);
		$this->operation->checkFileAccess('path', $this->mountPoint, false, null, Constants::PERMISSION_UPDATE);
	}

	public function testCheckFileAccessWithZeroRequiredPermissionsReturnsComputedPermissions(): void {
		$this->operation->method('isBlockablePath')->willReturn(true);
		$this->ruleMatcher->method('getFlows')->willReturn([
			['operation' => json_encode(['permissions' => Constants::PERMISSION_READ])],
		]);

		$result = $this->operation->checkFileAccess('path', $this->mountPoint, false, null, 0);

		$this->assertSame(Constants::PERMISSION_READ, $result);
	}

	public function testCheckFileAccessWithInvalidJsonOperationIsIgnored(): void {
		$this->operation->method('isBlockablePath')->willReturn(true);
		$this->ruleMatcher->method('getFlows')->willReturn([
			['operation' => 'not-valid-json'],
			['operation' => json_encode(['permissions' => Constants::PERMISSION_READ])],
		]);

		$result = $this->operation->checkFileAccess('path', $this->mountPoint, false, null, Constants::PERMISSION_READ);

		$this->assertSame(Constants::PERMISSION_READ, $result);
	}
}
