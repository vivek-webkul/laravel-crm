<?php

namespace Webkul\UI\DataGrid\Traits;

use Carbon\Carbon;

trait ProvideTabFilters
{
    /**
     * Tab filters.
     *
     * @var array
     */
    protected $tabFilters = [];

    /**
     * Custom tab filter listing.
     *
     * @var array
     */
    protected $customTabFilters = ['type', 'duration', 'scheduled'];

    /**
     * Prepare tab filters. Optional method.
     *
     * @return array
     */
    public function prepareTabFilters()
    {
    }

    /**
     * Add tab filter.
     *
     * @param  array  $filterConfig
     * @return void
     */
    public function addTabFilter($filterConfig)
    {
        if (! (($filterConfig['value_type'] ?? false) == 'lookup')) {
            foreach ($filterConfig['values'] as $valueIndex => $value) {
                $filterConfig['values'][$valueIndex]['name'] = trans($filterConfig['values'][$valueIndex]['name']);
            }
        }

        $this->tabFilters[] = $filterConfig;
    }

    /**
     * Resolve custom tab filters column.
     *
     * @param  string  $key
     * @return string
     */
    public function resolveCustomTabFiltersColumn($key)
    {
        switch ($key) {
            case 'duration':
                return $this->filterMap['created_at'] ?? 'created_at';

            case 'scheduled':
                return $this->filterMap['schedule_from'] ?? 'schedule_from';

            default:
                return $this->filterMap[$key] ?? $key;
        }
    }

    /**
     * Resolve custom tab filter query.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  string                          $key
     * @param  array                           $info
     * @return void
     */
    public function resolveCustomTabFiltersQuery($collection, $key, $info)
    {
        $value = array_values($info)[0];

        $startDate = Carbon::now()->format('Y-m-d');

        $column = $this->resolveCustomTabFiltersColumn($key);

        switch ($value) {
            case 'yesterday':
                $collection->whereDate(
                    $column,
                    Carbon::yesterday()->format('Y-m-d')
                );
                break;

            case 'today':
                $collection->whereDate(
                    $column,
                    Carbon::today()->format('Y-m-d')
                );
                break;

            case 'tomorrow':
                $collection->whereDate(
                    $column,
                    Carbon::tomorrow()->format('Y-m-d')
                );
                break;

            case 'this_week':
                $endDate = Carbon::now()->addDays(7)->format('Y-m-d');

                $collection->whereBetween(
                    $column,
                    [$startDate, $endDate]
                );
                break;

            case 'this_month':
                $endDate = Carbon::now()->addDays(30)->format('Y-m-d');

                $collection->whereBetween(
                    $column,
                    [$startDate, $endDate]
                );
                break;

            default:
                if ($value != 'all') {
                    if ($key == 'duration') {
                        $dates = explode(',', $value);

                        if (! empty($dates) && count($dates) == 2) {
                            if ($dates[1] == '') {
                                $dates[1] = Carbon::today()->format('Y-m-d');
                            }

                            $collection->whereDate($column, '>=', $dates[0]);

                            $collection->whereDate($column, '<=', $dates[1]);
                        }
                    } else {
                        $collection->where($column, $value);
                    }
                }
                break;
        }
    }

    /**
     * Filter collection from tab filter.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  string                          $key
     * @param  array                           $info
     * @return void
     */
    public function filterCollectionFromTabFilter($collection, $key, $info)
    {
        foreach ($this->tabFilters as $filterIndex => $filter) {
            if (in_array($key, $this->customTabFilters)) {
                foreach ($filter['values'] as $filterValueIndex => $filterValue) {
                    if (array_keys($info)[0] == 'bw' && $filterValue['key'] == 'custom') {
                        $this->tabFilters[$filterIndex]['values'][$filterValueIndex]['isActive'] = true;
                    } else {
                        $this->tabFilters[$filterIndex]['values'][$filterValueIndex]['isActive'] = ($filterValue['key'] == array_values($info)[0]);
                    }
                }

                $this->resolveCustomTabFiltersQuery($collection, $key, $info);
            }
        }
    }
}
