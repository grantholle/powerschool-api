<?php

namespace GrantHolle\PowerSchool\Api;

class Paginator
{
    protected int $page;
    protected int $pageSize;
    protected string $key;

    protected RequestBuilder $builder;

    public function __construct(RequestBuilder $builder, int $page = 1, int $pageSize = 100, string $key = 'record')
    {
        $this->builder = $builder;
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->key = $key;
    }

    public function page()
    {
        $this->builder->pageSize($this->pageSize)
            ->page($this->page);

        $results = $this->builder->send(false);

        if ($this->key === 'record') {
            $records = $results->record ?? [];
        } else {
            // API endpoints are organized by {plural}->{singular}
            // So something /course, is $response->courses->course = array
            // When there aren't any results, it seems it's still {plural} just
            // no singular key is present after that. Just one of many poor
            // design choices of terrible software
            $baseKey = $this->key . 's';
            $records = $results->{$baseKey}->{$this->key} ?? [];

            if (!is_array($records)) {
                $records = [$records];
            }
        }

        $this->page += 1;

        return empty($records)
            ? false
            : $records;
    }
}
