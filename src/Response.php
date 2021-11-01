<?php

namespace GrantHolle\PowerSchool\Api;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Response implements \Iterator, \ArrayAccess
{
    public ?array $data;
    public array $expansions = [];
    public array $extensions = [];
    protected int $index = 0;
    protected ?string $tableName;

    public function __construct(array $data, string $key)
    {
        $this->tableName = strtolower($data['name'] ?? null);

        $this->data = $this->inferData($data, strtolower($key));
    }

    protected function inferData(array $data, string $key): array
    {
        if (isset($data['@expansions'])) {
            $this->setExt($data, 'expansions');
            unset($data['@expansions']);
        }

        if (isset($data['@extensions'])) {
            $this->setExt($data, 'extensions');
            unset($data['@extensions']);
        }

        if (empty($data)) {
            return [];
        }

        if (isset($data[$key])) {
            return $data[$key];
        }

        if ($nested = Arr::get($data, $key . 's')) {
            return $this->inferData($nested, $key);
        }

        if (count(array_keys($data)) === 1) {
            return $this->inferData(Arr::first($data), '');
        }

        return $data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    protected function setExt(array $data, string $property)
    {
        $this->$property = $this->splitCommaString(Arr::get($data, "@$property"));
    }

    protected function splitCommaString(?string $string): array
    {
        if (!$string) {
            return [];
        }

        $parts = explode(',', $string);

        return array_map(fn ($s) => trim($s), $parts);
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function current()
    {
        $current = $this->data[$this->index] ?? null;

        if (!$current || !Arr::has($current, 'tables')) {
            return $current;
        }

        return Arr::get($current, "tables.{$this->tableName}");
    }

    public function next()
    {
        $this->index++;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return isset($this->data[$this->index]);
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return Arr::get($this->data, $offset);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(): string
    {
        return json_encode($this->data);
    }

    public function collect(): Collection
    {
        return collect($this->data);
    }

    public function __serialize(): array
    {
        return [
            'data' => $this->data,
            'table_name' => $this->tableName,
            'expansions' => $this->expansions,
            'extensions' => $this->extensions,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->data = $data['data'] ?? [];
        $this->tableName = $data['table_name'] ?? null;
        $this->expansions = $data['expansions'] ?? [];
        $this->extensions = $data['extensions'] ?? [];
    }
}
