<?php

namespace MartinLechene\AuditSuite\Services;

class AuditContext
{
    private array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function set(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function getProjectPath(): ?string
    {
        return $this->get('project_path', base_path());
    }

    public function getRoutes(): array
    {
        return $this->get('routes', []);
    }

    public function getConfig(): array
    {
        return $this->get('config', []);
    }
}

