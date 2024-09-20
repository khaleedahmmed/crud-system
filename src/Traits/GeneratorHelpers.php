<?php

namespace W88\CrudSystem\Traits;
use Illuminate\Support\Str;
use W88\CrudSystem\Field;

trait GeneratorHelpers
{
    /* ======================== Checks ======================== */
    protected function hasCreateRoute(): bool
    {
        return isset($this->config['dashboardApi']['create']) && $this->config['dashboardApi']['create'] === true;
    }

    protected function hasProfileRoute(): bool
    {
        return isset($this->config['dashboardApi']['profile']) && $this->config['dashboardApi']['profile'] === true;
    }

    protected function hasUpdateRoute(): bool
    {
        return isset($this->config['dashboardApi']['update']) && $this->config['dashboardApi']['update'] === true;
    }

    protected function hasDeleteRoute(): bool
    {
        return isset($this->config['dashboardApi']['delete']) && $this->config['dashboardApi']['delete'] === true;
    }

    protected function hasAddLogs(): bool
    {
        return isset($this->config['options']['addLogs']) && $this->config['options']['addLogs'] === true;
    }

    protected function hasPermissions(): bool
    {
        return isset($this->config['options']['permissions']) && $this->config['options']['permissions'] === true;
    }

    protected function hasSoftDeletes(): bool
    {
        return isset($this->config['options']['softDeletes']) && $this->config['options']['softDeletes'] === true;
    }

    protected function hasTableSearch(): bool
    {
        return isset($this->config['options']['tableSettings']['tableSearch']) && $this->config['options']['tableSettings']['tableSearch'] === true;
    }

    protected function hasTableFilter(): bool
    {
        return isset($this->config['options']['tableSettings']['tableFilter']) && $this->config['options']['tableSettings']['tableFilter'] === true;
    }
    
    protected function hasTableExport(): bool
    {
        return isset($this->config['options']['tableSettings']['tableExport']) && $this->config['options']['tableSettings']['tableExport'] === true;
    }

    /* ======================== Getters ======================== */
    protected function getModelNamespace(): string
    {
        return $this->moduleNamespace . '\app\Models';
    }

    protected function getControllerName(): string
    {
        return $this->modelName . 'Controller';
    }

    protected function getControllerNamespace(): string
    {
        return "{$this->moduleNamespace}\app\Http\Controllers\\{$this->versionNamespace}";
    }

    protected function getServiceName(): string
    {
        return $this->modelName . 'Service';
    }

    protected function getServiceNameCamel(): string
    {
        return Str::camel($this->getServiceName());
    }

    protected function getServiceNamespace(): string
    {
        return "{$this->moduleNamespace}\app\Services";
    }

    protected function getRequestName(): string
    {
        return $this->modelName . 'Request';
    }

    protected function getRequestNamespace(): string
    {
        return "{$this->moduleNamespace}\app\Http\Requests\\{$this->versionNamespace}";
    }

    protected function getResourceName(): string
    {
        return $this->modelName . 'Resource';
    }

    protected function getResourceNamespace(): string
    {
        return "{$this->moduleNamespace}\app\Http\Resources\\{$this->versionNamespace}";
    }
    
    protected function getSeederName(): string
    {
        return $this->modelName . 'Seeder';
    }
    
    protected function getSeederOption()
    {
        return $this->config['options']['seeder'] ?? false;
    }
    
    protected function getLookupOption()
    {
        return $this->config['options']['lookup'] ?? false;
    }

    protected function getActivationRouteOption()
    {
        return $this->config['dashboardApi']['activation'] ?? false;
    }
    
    protected function getFields(): array
    {
        return array_merge($this->config['fields'] ?? [], $this->appendActivationField());
    }

    protected function appendActivationField(): array
    {
        $fields = [];
        $activationRouteOption = $this->getActivationRouteOption();
        $activationColumn = $activationRouteOption['column'] ?? 'is_active';
        $activationDefault = $activationRouteOption['default'] ?? true;
        if ($activationRouteOption) {
            $fields[$activationColumn] = [
                'type' => 'boolean',
                'label' => 'Active',
                'default' => $activationDefault,
                'validation' => 'nullable|boolean',
            ];
        }
        return $fields;
    }
    
    protected function getModelRelations(): array
    {
        $relations = array_merge($this->appendRelationFields(), $this->config['relations'] ?? []);
        return collect($relations)->filter(fn ($relation) => isset($relation['type']) && in_array($relation['type'], $this->getAllowedRelations()))->toArray();
    }

    protected function appendRelationFields(): array
    {
        $relations = [];
        foreach ($this->getFields() as $name => $field) {
            if (isset($field['relation']['model'])) {
                $isEndById = Str::endsWith($name, '_id');
                $relationName = $isEndById ? Str::beforeLast($name, '_id') : $name;
                $relations[$relationName] = [
                    'type' => $field['relation']['type'] ?? 'belongsTo',
                    'model' => $field['relation']['model'],
                ];
                if (!$isEndById) $relations[$relationName]['foreignKey'] = $name;
            }
        }
        return $relations;
    }

    protected function getBooleanFields(): array
    {
        return collect($this->getFields())->filter(fn ($field) => $field['type'] === 'boolean')->toArray();
    }

    protected function getFileFields(): array
    {
        return collect($this->getFields())->filter(fn ($field) => Field::hasFile($field))->toArray();
    }

    protected function getTranslatableFields(): array
    {
        return collect($this->getFields())->filter(fn ($field) => Field::hasTranslatable($field))->toArray();
    }

    protected function getCastFields(): array
    {
        return collect($this->getFields())->map(function ($field) {
            $field['cast'] = array_key_exists($field['type'], Field::jsonFields()) ? 'array' : ($field['type'] === 'boolean' ? 'boolean' : null);
            return $field;
        })->filter(fn ($field) => $field['cast'] !== null)->toArray();
    }

    protected function getPermissionsTranslated(): array
    {
        $modelTitle = Str::title($this->modelNameKebab);
        $permissions = [
            "view-list-{$this->modelNameKebab}" => "View {$modelTitle} List"
        ];
        if ($this->hasTableExport()) $permissions["export-list-{$this->modelNameSnake}"] = "Export {$modelTitle} List";
        if ($this->hasProfileRoute()) $permissions["view-profile-{$this->modelNameSnake}"] = "View {$modelTitle} Profile";
        if ($this->hasCreateRoute()) $permissions["create-{$this->modelNameSnake}"] = "Create {$modelTitle}";
        if ($this->hasUpdateRoute()) $permissions["edit-{$this->modelNameSnake}"] = "Edit {$modelTitle}";
        if ($this->hasDeleteRoute()) $permissions["delete-{$this->modelNameSnake}"] = "Delete {$modelTitle}";
        if ($this->hasSoftDeletes()) {
            $permissions["force-delete-{$this->modelNameSnake}"] = "Delete Forever {$modelTitle}";
            $permissions["restore-{$this->modelNameSnake}"] = "Restore {$modelTitle}";
            $permissions["view-trashed-{$this->modelNameSnake}-list"] = "View Trashed {$modelTitle} List";
        }
        if ($this->getActivationRouteOption()) $permissions["activation-{$this->modelNameSnake}"] = "Activation {$modelTitle}";
        return $permissions;
    }

    protected function getAllowedRelations(): array
    {
        return [
            'belongsTo',
            'hasOne',
            'hasMany',
            'belongsToMany',
            'morphTo',
            'morphOne',
            'morphMany',
            'morphToMany',
            'morphedByMany',
        ];
    }

    /* ======================== Helpers ======================== */
    protected function isPhpCode($string) {
        $patterns = [
            '/\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*/', // PHP variables like $this
            '/::/', // Static method or constant calls like Rule::unique
            '/->/', // Object method or property access like $this->post
            '/\(.*\)/', // Function calls with parentheses
            '/\s*new\s+/', // Object instantiation
        ];
        // Check if any of the patterns match the string
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $string)) {
                return true; // Contains PHP code
            }
        }
        return false; // Doesn't contain PHP code
    }

    /* ======================== Helpers ======================== */
    protected function makePolymorphic($name) {
        if (preg_match('/([aeiou])([bcdfghjklmnpqrstvwxyz])$/i', $name)) $name = $name . substr($name, -1);
        return $name . 'able';
    }
}