<?php

namespace GrantHolle\PowerSchool\Api;

class Paginator
{
    protected int $page;
    protected int $pageSize;

    protected RequestBuilder $builder;

    public function __construct(RequestBuilder $builder, int $page = 1, int $pageSize = 100)
    {
        $this->builder = $builder;
        $this->page = $page;
        $this->pageSize = $pageSize;
    }

    public function page()
    {
        $this->builder->pageSize($this->pageSize)
            ->page($this->page);

        $results = $this->builder->send(false);

        $records = $results->record ?? [];

        $this->page += 1;

        return empty($records)
            ? false
            : $records;
    }
}
