<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\FileTypeMapper;

class DataProviderHelperFactory
{

	private ReflectionProvider $reflectionProvider;

	private FileTypeMapper $fileTypeMapper;

	private Parser $parser;

	private PHPUnitVersion $PHPUnitVersion;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		FileTypeMapper $fileTypeMapper,
		Parser $parser,
		PHPUnitVersion $PHPUnitVersion
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->fileTypeMapper = $fileTypeMapper;
		$this->parser = $parser;
		$this->PHPUnitVersion = $PHPUnitVersion;
	}

	public function create(): DataProviderHelper
	{
		return new DataProviderHelper($this->reflectionProvider, $this->fileTypeMapper, $this->parser, $this->PHPUnitVersion);
	}

}
