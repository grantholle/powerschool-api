<?php

namespace GrantHolle\PowerSchool\Api;

class Paginator
{
    protected int $page = 1;

    protected RequestBuilder $builder;

    public function __construct(RequestBuilder $builder, int $pageSize = 100)
    {
        $this->builder = $builder->pageSize($pageSize);
    }

    public function page(): ?Response
    {
        $results = $this->builder
            ->page($this->page)
            ->send(false);

        // This means that PS sent back a single record
        // and should be wrapped in an array
        if (!$results->isEmpty() && !$results[0]) {
            $results->setData([$results->data]);
        }

        if ($results->isEmpty()) {
            $this->page = 1;
            return null;
        }

        $this->page += 1;

        return $results;
    }
}
