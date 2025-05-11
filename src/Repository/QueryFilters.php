<?php

namespace src\Repository;

use Doctrine\ORM\QueryBuilder;

class QueryFilters {
    private QueryBuilder $qb;
    private array $filter = [];


    public function __construct(QueryBuilder $qb, array $filter = []) {
        $this->qb = $qb;
        $this->qb->where('1=1');
        $this->filter = $filter;
    }

    public function like(string $filter_field, ?string $db_mask = null): self{
        if (empty($this->filter[$filter_field])) return $this;

        $this->qb
        ->andWhere(($db_mask ?? $filter_field) .' LIKE :'.$filter_field)
        ->setParameter($filter_field, '%'.$this->filter[$filter_field].'%');
        return $this;
    }

    public function exact(string $filter_field, ?string $db_mask = null): self {
        if (empty($this->filter[$filter_field])) return $this;
        $this->qb
        ->andWhere(($db_mask ?? $filter_field) .' = :'.$filter_field)
        ->setParameter($filter_field, $this->filter[$filter_field]);
        return $this;
    }

    public function range(string $filter_field, ?string $db_mask=null): self {
        if (empty($this->filter[$filter_field])) return $this;

        $lowerbound = $this->filter[$filter_field]['from'] ?? "";
        $upperbound = $this->filter[$filter_field]['to'] ?? "";

        if ($lowerbound){
            $this->qb
            ->andWhere(($db_mask ?? $filter_field) .' >= :'.$filter_field.'_from')
            ->setParameter($filter_field.'_from', $lowerbound);
        }

        if ($upperbound){
            $this->qb
            ->andWhere(($db_mask ?? $filter_field) .' <= :'.$filter_field.'_to')
            ->setParameter($filter_field.'_to', $upperbound);
        }
        return $this;
    }
}

?>