<?php

namespace W88\CrudSystem\Generators\ClientApi;

use W88\CrudSystem\Generators\Backend\ResourceGenerator as BackendResourceGenerator;

class ResourceGenerator extends BackendResourceGenerator
{

    protected function getGeneratorDirectory(): string
    {
        return "{$this->modulePath}/app/Resources/{$this->versionNamespace}/{$this->clientDirectory}";
    }

    protected function getLocalResourceNamespace(): string
    {
        return "{$this->getResourceNamespace()}\\{$this->clientDirectory}";
    }

    protected function getTimestampsFields(): string
    {
        return '';
    }

}
