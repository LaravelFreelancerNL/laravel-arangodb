<?php

declare(strict_types=1);

namespace LaravelFreelancerNL\Aranguent\Query\Concerns;

use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use LaravelFreelancerNL\Aranguent\Query\Grammar;

trait BuildsSearches
{
    /**
     * Search an ArangoSearch view.
     * @param array<mixed>|Expression|string $fields
     * @param string $searchText
     * @param string|null $analyzer
     * @return IlluminateQueryBuilder
     */
    public function searchView(
        array|Expression|string $fields,
        string $searchText,
        string $analyzer = null
    ): IlluminateQueryBuilder {
        assert($this->grammar instanceof Grammar);

        if(!is_array($fields)) {
            $fields = Arr::wrap($fields);
        }
        $fields = $this->grammar->convertJsonFields($fields);
        $fields = $this->convertIdToKey($fields);

        $searchText = $this->bindValue($searchText, 'search');

        $locale = App::getLocale();
        $analyzerLocales = ['de','en','es','fi','fr','it','nl','no','pt','ru','sv','zh'];

        $analyzer = 'text_en';
        if (in_array($locale, $analyzerLocales)) {
            $analyzer = 'text_' . $locale;
        }

        $this->search = [
            'fields' => $fields,
            'searchText' => $searchText,
            'analyzer' => $analyzer
        ];

        return $this;
    }


    /**
     * Add an "order by best matching" clause to the query.
     * To be used with the searchView function
     *
     * @param string $direction
     *
     * @return IlluminateQueryBuilder
     */
    public function orderByBestMatching($direction = 'DESC'): IlluminateQueryBuilder
    {
        $alias = $this->getTableAlias($this->from);
        $column = new Expression('BM25(' . $alias . ')');

        $this->orderBy($column, $direction);

        return $this;
    }

    /**
     * Add an "order by best matching" clause to the query.
     * To be used with the searchView function
     *
     * @param string $direction
     *
     * @return IlluminateQueryBuilder
     */
    public function orderByFrequency($direction = 'DESC'): IlluminateQueryBuilder
    {
        $alias = $this->getTableAlias($this->from);
        $column = new Expression('TFIDF(' . $alias . ')');

        $this->orderBy($column, $direction);

        return $this;
    }
}
