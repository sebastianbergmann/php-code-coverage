<?php

use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpunit;

class A extends \PHPUnit\Framework\TestCase {
	#[RequiresPhp('<=8.2.0')]
	public function testFoo() {}
}

class B extends \PHPUnit\Framework\TestCase {
	#[RequiresPhp('<=8.0.0')]
	public function testFoo() {}
}

class C extends \PHPUnit\Framework\TestCase {
	#[RequiresPhp('^8.0.0')]
	public function testFoo() {}
}

class D extends \PHPUnit\Framework\TestCase {
	#[RequiresPhp('^8.1.0')]
	public function testFoo() {}
}

class E extends \PHPUnit\Framework\TestCase {
	#[RequiresPhpunit('^12.0.0')]
	public function testFoo() {}
}

class F extends \PHPUnit\Framework\TestCase {
	#[RequiresPhpunit('^11.0.0')]
	public function testFoo() {}
}
