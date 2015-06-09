<?php

class TwoDependenciesClass
{
	public function __construct(OneDependencyClass $a, stdClass $b)
	{
		$this->odc = $a;
		$this->std = $b;
	}
}

class OneDependencyClass
{
	public function __construct(stdClass $a)
	{
		$this->std = $a;	
	}
}
