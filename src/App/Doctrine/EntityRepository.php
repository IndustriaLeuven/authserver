<?php

namespace App\Doctrine;

use vierbergenlars\Bundle\RadRestBundle\Doctrine\EntityRepository as BaseRepository;
use App\Search\SearchGrammar;
use App\Search\SearchFieldException;
use vierbergenlars\Bundle\RadRestBundle\Doctrine\QueryBuilderPageDescription;

class EntityRepository extends BaseRepository
{
    public function create()
    {
        $n = $this->getClassName();
        return new $n();
    }

    public function search($terms)
    {
        $parser = new SearchGrammar();
        $blocks = $parser->parse($terms);

        $queryBuilder = $this->createQueryBuilder('u');
        $and = $queryBuilder->expr()->andX();

        foreach($blocks as $i=>$block) {
            if(!in_array($block['name'], $this->fieldSearchWhitelist)) {
                $this->handleUnknownSearchField($block);
            }

            if(strpos($block['value'], '*') !== false) {
                $and->add($queryBuilder->expr()->like('u.'.$block['name'], '?'.$i));
                $queryBuilder->setParameter($i, str_replace('*', '%', $block['value']));
            } else {
                $and->add($queryBuilder->expr()->eq('u.'.$block['name'], '?'.$i));
                $queryBuilder->setParameter($i, $block['value']);
            }
        }

        if($and->count()) {
            $queryBuilder->where($and);
        }
        return new QueryBuilderPageDescription($queryBuilder);
    }

    protected function handleUnknownSearchField(array &$block)
    {
        throw new SearchFieldException($block['name'], $this->fieldSearchWhitelist);
    }
}
