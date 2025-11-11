<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAccessControl\Tests\Unit;

use OCA\FilesAccessControl\Operation;
use OCA\FilesAccessControl\StorageWrapper;
use OCP\Files\ForbiddenException;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class StorageWrapperTest extends TestCase {
	protected IStorage&MockObject $storage;
	protected Operation&MockObject $operation;

	/** @var IMountPoint|MockObject */
	protected $mountPoint;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = $this->createMock(IStorage::class);
		$this->operation = $this->createMock(Operation::class);

		$this->mountPoint = $this->createMock(IMountPoint::class);
		$this->mountPoint->method('getMountPoint')
			->willReturn('mountPoint');
		$this->mountPoint->method('getStorage')
			->willReturn($this->storage);
	}

	protected function getInstance(array $methods = []): StorageWrapper&MockObject {
		return $this->getMockBuilder(StorageWrapper::class)
			->setConstructorArgs([
				[
					'storage' => $this->storage,
					'mountPoint' => 'mountPoint',
					'mount' => $this->mountPoint,
					'operation' => $this->operation,
				]
			])
			->onlyMethods($methods)
			->getMock();
	}

	public static function dataCheckFileAccess(): array {
		return [
			['path1', true],
			['path2', false],
		];
	}

	/**
	 * @dataProvider dataCheckFileAccess
	 */
	public function testCheckFileAccess(string $path, bool $isDir): void {
		$storage = $this->getInstance();

		$this->operation->expects($this->once())
			->method('checkFileAccess')
			->with($path, $this->mountPoint, $isDir);

		self::invokePrivate($storage, 'checkFileAccess', [$path, $isDir]);
	}

	public static function dataSinglePath(): array {
		$methods = ['mkdir', 'rmdir', 'file_get_contents', 'unlink', 'getDirectDownload'];

		$tests = [];
		foreach ($methods as $method) {
			if ($method !== 'file_get_contents' && $method !== 'getDirectDownload') {
				$tests[] = [$method, 'path1', true, null];
			}
			$tests[] = [$method, 'path2', false, null];
			$tests[] = [$method, 'path3', true, new ForbiddenException('Access denied', false)];
			$tests[] = [$method, 'path4', false, new ForbiddenException('Access denied', false)];
		}
		return $tests;
	}

	/**
	 * @dataProvider dataSinglePath
	 */
	public function testSinglePath(string $method, string $path, bool $return, ?\Exception $expected): void {
		$storage = $this->getInstance(['checkFileAccess']);


		if (is_null($expected)) {
			$storage->expects($this->once())
				->method('checkFileAccess')
				->with($path);

			$this->storage->expects($this->once())
				->method($method)
				->with($path)
				->willReturn($return);

			$this->assertSame($return, self::invokePrivate($storage, $method, [$path]));
		} else {
			$storage->expects($this->once())
				->method('checkFileAccess')
				->with($path)
				->willThrowException($expected);

			$this->storage->expects($this->never())
				->method($method)
				->with($path)
				->willReturn($return);

			try {
				self::invokePrivate($storage, $method, [$path]);
				$this->fail('Should throw an exception before this');
			} catch (\Exception $e) {
				$this->assertSame($expected, $e);
			}
		}
	}

	public static function dataSinglePathOverWritten(): array {
		$methods = ['isCreatable', 'isReadable', 'isUpdatable', 'isDeletable'];

		$tests = [];
		foreach ($methods as $method) {
			$tests[] = [$method, 'path1', true, null, true];
			$tests[] = [$method, 'path2', false, null, false];
			$tests[] = [$method, 'path3', true, new ForbiddenException('Access denied', false), false];
			$tests[] = [$method, 'path4', false, new ForbiddenException('Access denied', false), false];
		}
		return $tests;
	}

	/**
	 * @dataProvider dataSinglePathOverWritten
	 */
	public function testSinglePathOverWritten(string $method, string $path, bool $return, ?\Exception $checkAccess, bool $expected): void {
		$storage = $this->getInstance(['checkFileAccess']);


		if ($checkAccess === null) {
			$storage->expects($this->once())
				->method('checkFileAccess')
				->with($path);

			$this->storage->expects($this->once())
				->method($method)
				->with($path)
				->willReturn($return);
		} else {
			$storage->expects($this->once())
				->method('checkFileAccess')
				->with($path)
				->willThrowException($checkAccess);

			$this->storage->expects($this->never())
				->method($method)
				->with($path)
				->willReturn($return);
		}

		$this->assertSame($expected, self::invokePrivate($storage, $method, [$path]));
	}
}
