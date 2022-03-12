<?php

namespace GrantHolle\PowerSchool\Api;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Response implements \Iterator, \ArrayAccess
{
    public ?array $data;
    public array $originalData = [];
    public array $expansions = [];
    public array $extensions = [];
    public array $meta = [];
    protected int $index = 0;
    protected ?string $tableName;

    public function __construct(array $data, string $key)
    {
        $this->tableName = strtolower($data['name'] ?? '');

        $this->data = $this->inferData($data, strtolower($key));
        DebugLogger::log(fn () => ray($this->data)->purple()->label('Response data'));
    }

    protected function inferData(array $data, string $key): array
    {
        if (empty($data)) {
            return [];
        }

        if ($nested = Arr::get($data, $key . 's')) {
            return $this->inferData($nested, $key);
        }

        $keys = array_keys($data);

        // Remove anything that isn't the desired key
        // from data, but preserving as a property or meta
        foreach ($keys as $dataKey) {
            if ($dataKey === $key) {
                continue;
            }

            $this->setMeta($data, $dataKey);
            unset($data[$dataKey]);
        }

        if (isset($data[$key])) {
            return $data[$key];
        }

        if (count(array_keys($data)) === 1) {
            $first = Arr::first($data);

            // If this is an array, keep drilling
            if (is_array($first)) {
                return $this->inferData($first, '');
            }
        }

        return $data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function squashTableResponse(): static
    {
        if (!$this->tableName) {
            return $this;
        }
        $isAssoc = Arr::isAssoc($this->data);

        if ($isAssoc) {
            $this->data = [$this->data];
        }

        $this->data = array_map(
            fn (array $datum) => $datum['tables'][$this->tableName],
            $this->data
        );

        if ($isAssoc) {
            $this->data = Arr::first($this->data);
        }

        return $this;
    }

    protected function setMeta(array $data, string $property): static
    {
        $clean = $this->cleanProperty($property);
        $value = Arr::get($data, $property);

        if (in_array($clean, ['extensions', 'expansions'])) {
            $this->$clean = $this->splitCommaString($value);
            return $this;
        }

        $this->meta[$clean] = $value;

        return $this;
    }

    protected function cleanProperty(string $property): string
    {
        return preg_replace("/[^a-zA-Z0-9_]/u", '', $property);
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

    public function count(): int
    {
        return count($this->data);
    }

    public function current(): mixed
    {
        $current = $this->data[$this->index] ?? null;

        if (!$current || !Arr::has($current, 'tables')) {
            return $current;
        }

        return Arr::get($current, "tables.{$this->tableName}");
    }

    public function next(): void
    {
        $this->index++;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return isset($this->data[$this->index]);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return Arr::get($this->data, $offset);
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    public function __get(string $name): mixed
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
