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

class DinamicAttributeClass
{
    public function __construct()
    {
        $this->number = rand() + rand();
    }
}

class DinamicAttributeDependencyClass
{
    public function __construct(DinamicAttributeClass $dac)
    {
        $this->dac = $dac;
    }
}

class ContainerAwareClass
{
    use Codeburner\Container\ContainerAwareTrait;
}
