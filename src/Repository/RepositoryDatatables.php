<?php

namespace LaravelOrm\Repository;

use LaravelOrm\Libraries\Datatables;
use Exception;

class RepositoryDatatables extends Datatables
{
    /**
     *
     * @var Repository
     */
    protected $model;

    /**
     * Get the data
     *
     * @param array $data Of Eloquent Object
     */
    private function output($datas)
    {
        $out = [];
        $i   = ($this->currentPage * $this->pageSize) - $this->pageSize;
        foreach ($datas as $data) {
            $row = [];
            foreach ($this->column as $column) {
                if ($column['ispassedback']) {
                    $rowdata = null;

                    if (!is_null($column['callback'])) {
                        $fn      = $column['callback'];
                        $rowdata = $fn($data, $i);
                    } else {
                        $rowdata = $this->getColValue($column, $data);
                    }

                    if ($this->useIndex) {
                        $row[] = $rowdata;
                    } else {
                        $selectedColumn = '';
                        $col            = explode('.', $column['column']);
                        if (count($col) === 3) {
                            $selectedColumn = $col[2];
                        } elseif (count($col) === 2) {
                            $selectedColumn = $col[1];
                        } else {
                            $selectedColumn = $col[0];
                        }
                        $row[$selectedColumn] = $rowdata;
                    }

                    if ($this->dtRowId && $this->dtRowId === $column['column']) {
                        $col = explode('.', $column['column']);
                        if (count($col) === 3) {
                            $selectedColumn = $col[2];
                        } elseif (count($col) === 2) {
                            $selectedColumn = $col[1];
                        } else {
                            $selectedColumn = $col[0];
                        }
                        $row['DT_RowId'] = $data->{'get' . $selectedColumn}();
                    }

                    $row['DT_RowClass'] = $this->dtRowClass;
                }
            }
            $i++;
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Get value if foreignkey filed not empty otherwise will return from Closure
     *
     * @param string $column
     * @param object $data   Of intended Instace Eloquent
     */
    private function getColValue($column, $data)
    {

        $col        = explode('.', $column['column']);
        $columnname = null;
        if (count($col) === 3) {
            $columnname = $col[2];
            return $data->{'get' . $columnname}();
        } elseif (count($col) === 2) {
            $columnname = $col[1];
            return $data->{'get' . $columnname}();
        } else {
            $columnname = $column['column'];
            return $data->{'get' . $columnname}();
        }
    }

    /**
     * Populate the data to store to datatables.net
     */
    public function populate()
    {
        try {
            $params = $this->setParams();
            $result = $this->model->collect($params);

            $this->output['draw']            = !empty($this->request->getPost('draw')) ? intval($this->request->getPost('draw')) : 0;
            $this->output['recordsTotal']    = intval(count($result));
            $this->output['recordsFiltered'] = intval($this->allData($params));
            $this->output['data']            = $this->output($result);
        } catch (Exception $e) {
            $this->output['error'] = $e->getMessage();
        }

        return $this->output;
    }

    /**
     * Count All data in Eloquent table mapping
     *
     * @param array $filter
     */
    private function allData($filter = [])
    {
        $params = [
            'join'       => isset($filter['join']) ? $filter['join'] : null,
            'where'      => isset($filter['where']) ? $filter['where'] : null,
            'whereIn'    => isset($filter['whereIn']) ? $filter['whereIn'] : null,
            'orWhere'    => isset($filter['orWhere']) ? $filter['orWhere'] : null,
            'whereNotIn' => isset($filter['whereNotIn']) ? $filter['whereNotIn'] : null,
            'like'       => isset($filter['like']) ? $filter['like'] : null,
            'orLike'     => isset($filter['orLike']) ? $filter['orLike'] : null,
            'group'      => isset($filter['group']) ? $filter['group'] : null,
        ];
        return $this->model->count($params);
    }
}
