<?php

namespace DbModel;

class ErrorLogStatusModel extends \DbLib\DbTableModel
{
    protected $tableName = 'error_log_status';

    public function fetchByErrorHash(string $errorHash)
    {
        return $this->get('id', ['error_log_hash' => $errorHash]);
    }
}