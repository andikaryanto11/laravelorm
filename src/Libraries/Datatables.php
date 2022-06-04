<?php

namespace LaravelOrm\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use Exception;

class Datatables
{
    /**
     * @var IncomingRequest
     */
    protected $request;

    /**
     * @var array
     */
    protected $filter     = [];

    /**
     * @var bool
     */
    protected $useIndex   = true;

    /**
     * @var bool
     */
    protected $isModel = false;
    /**
     * @var mixed
     */
    protected $model;

    /**
     * @var string
     */
    protected $dtRowClass;

    /**
     * @var string
     */
    protected $dtRowId;

    /**
     * @var int
     */
    protected $columnCounter  = 0;

    /**
     * @var array
     */
    protected $column         = [];

    /**
     * @var array
     */
    protected $dtTableColumns = [];

    /**
     * @var bool
     */
    protected $returnEntity   = false;

    /**
     * @var int
     */
    protected $currentPage    = null;

    /**
     * @var int
     */
    protected $pageSize       = null;

    /**
     * @var array
     */
    protected $output = [
        'draw'            => null,
        'recordsTotal'    => null,
        'recordsFiltered' => null,
        'data'            => null,
    ];

    public function __construct($filter = [], $returnEntity = true, $useIndex = true, $model = null)
    {
        $this->request = \Config\Services::request();

        $this->model  = $model;
        $this->isModel = $this->returnEntity ? true : false;

        if (!empty($filter)) {
            $this->filter = $filter;
        }

        if (!$useIndex) {
            $this->useIndex = false;
        } else {
            if (!is_numeric($this->request->getPost('columns')[0]['data'])) {
                $this->dtTableColumns = $this->request->getPost('columns');
                $this->useIndex       = false;
            } else {
                $this->useIndex = true;
            }
        }

        $this->returnEntity = $returnEntity;
    }


    /**
     * Set post parameter to model fetch function parameter
     */
    public function setParams()
    {
        $params               = [];
        $params['join']       = isset($this->filter['join']) ? $this->filter['join'] : null;
        $params['where']      = isset($this->filter['where']) ? $this->filter['where'] : null;
        $params['whereIn']    = isset($this->filter['whereIn']) ? $this->filter['whereIn'] : null;
        $params['orWhereIn']  = isset($this->filter['orWhereIn']) ? $this->filter['orWhereIn'] : null;
        $params['orWhere']    = isset($this->filter['orWhere']) ? $this->filter['orWhere'] : null;
        $params['whereNotIn'] = isset($this->filter['whereNotIn']) ? $this->filter['whereNotIn'] : null;
        $params['like']       = isset($this->filter['like']) ? $this->filter['like'] : null;
        $params['orLike']     = isset($this->filter['orLike']) ? $this->filter['orLike'] : null;
        $params['group']      = isset($this->filter['group']) ? $this->filter['group'] : null;

        if ($this->request->getPost('length') !== -1) {
            $this->currentPage = $this->request->getPost('start') / $this->request->getPost('length') + 1;
            $this->pageSize    = (int)$this->request->getPost('length');
            $params['limit']   = [
                'page' => $this->currentPage,
                'size' => $this->pageSize,
            ];
        }

        if ($this->request->getPost('search') && $this->request->getPost('search')['value'] !== '') {
            $searchValue = $this->request->getPost('search')['value'];

            foreach ($this->column as $column) {
                if (!empty($column['column'])) {
                    $strparam = 'orLike';

                    if ($column['searchable']) {
                        $col = explode('.', $column['column']);
                        if (count($col) === 3) {
                            $params['group'][$strparam][$col[0] . '.' . $col[1]] = $searchValue;
                        } elseif (count($col) === 2) {
                            $params['group'][$strparam][$col[0] . '.' . $col[1]] = $searchValue;
                        } else {
                            $params['group'][$strparam][$column['column']] = $searchValue;
                        }
                    }
                }
            }
        }

        if (!empty($this->request->getPost('customSearch'))) {
            $searchValue = $this->request->getPost('customSearch');

            foreach ($this->column as $column) {
                if (!empty($column['column'])) {
                    $strparam = 'orLike';

                    if ($column['searchable']) {
                        $col = explode('.', $column['column']);
                        if (count($col) === 3) {
                            $params['group'][$strparam][$col[0] . '.' . $col[1]] = $searchValue;
                        } elseif (count($col) === 2) {
                            $params['group'][$strparam][$col[0] . '.' . $col[1]] = $searchValue;
                        } else {
                            $params['group'][$strparam][$column['column']] = $searchValue;
                        }
                    }
                }
            }
        }

        if (!empty($this->request->getPost('customFilter'))) {
            $searchValue = $this->request->getPost('customFilter');
            $strparam = 'and';
            if (empty($params['where'])) {
                $params["where"] = [
                    1 => 1
                ];
            }
            foreach ($searchValue as $key => $value) {
                if (!empty($value)) {
                    $params['group'][$strparam][$key] = $value;
                }
            }
        }

        if ($this->request->getPost('order') && count($this->request->getPost('order'))) {
            $order = $this->request->getPost('order')[0];

            if (isset($this->column[$order['column']]) && $this->column[$order['column']]['orderable']) {
                $col          = explode('.', $this->column[$order['column']]['column']);
                $actualColumn = '';
                if (count($col) === 3) {
                    $actualColumn = $col[0] . '.' . $col[1];
                } elseif (count($col) === 2) {
                    $actualColumn = $col[0] . '.' . $col[1];
                } else {
                    $actualColumn = $col[0];
                }

                $params['order'] = [
                    $actualColumn => $order['dir'] === 'asc' ? 'ASC' : 'DESC',
                ];
            }
        }
        return $params;
    }

    /**
     * Populate the data to store to datatables.net
     */

    public function populate()
    {
        try {
            $params = $this->setParams();
            $result = $this->model::findAll($params, $this->returnEntity, $this->getColumnsOnly());

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
        return $this->model::count($params, $this->returnEntity, $this->getColumnsOnly());
    }

    /**
     * Set Column You want to return
     *
     * @param string  $column
     * @param string  $foreignKey     Nullable
     * @param Closure $callback       Nullable
     * @param boolean $searchable     Nullable
     * @param boolean $orderable      Nullable
     * @param boolean $isdefaultorder Nullable
     */
    public function addColumn($column, $foreignKey = null, $callback = null, $searchable = true, $orderable = true, $isdefaultorder = false, $ispassedBack = true)
    {
        $columns = [
            'column'         => $column,
            'foreignKey'     => $foreignKey,
            'callback'       => $callback,
            'searchable'     => $searchable,
            'orderable'      => $orderable,
            'isdefaultorder' => $isdefaultorder,
            'ispassedback'   => $ispassedBack,
        ];
        array_push($this->column, $columns);
        $this->columnCounter++;
        return $this;
    }

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
                        $row['DT_RowId'] = $data->$selectedColumn;
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
     * Add row class name for datatable.net
     * @param string $className
     */
    public function addDtRowClass($className)
    {
        $this->dtRowClass = $className;
        return $this;
    }

    /**
     * Add row id name for datatable.net
     * @param string $className
     */
    public function addDtRowId($columName)
    {
        $this->dtRowId = $columName;
        return $this;
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
            return $data->$columnname;
        } elseif (count($col) === 2) {
            $columnname = $col[1];
            return $data->$columnname;
        } else {
            $columnname = $column['column'];
            return $data->$columnname;
        }
    }

    /**
     * Not Used Yet
     */
    public function getColumns()
    {
        return $this->column;
    }

    /**
     * Collect all columns to select in database query
     */
    public function getColumnsOnly()
    {
        $columns = [];
        foreach ($this->column as $column) {
            if (!empty($column['column'])) {
                $col = explode('.', $column['column']);
                if (count($col) === 3) {
                    $columns[] = $col[0] . '.' . $col[1] . ' ' . $col[2];
                } else {
                    $columns[] = $column['column'];
                }
            }
        }
        return $columns;
    }
}
