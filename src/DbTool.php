<?php
namespace hzkoala\DevTool;

use Illuminate\Support\Facades\DB;

final class DbTool {

    /**
     * 保存(新增/修改)
     *
     * @param string $modelName
     * @param array $fields
     * @param string $id
     * @return object
     */
    public static function save($modelName, $fields, $id = '') {
        # action
        // 判断新增还是修改
        if(!$id) {
            $model = new $modelName();
        } else {
            $model = $modelName::find($id);
        }

        // 添加属性
        foreach($fields as $k => $v) {
            $model->$k = $v;
        }
        $model->save();

        # return
        return $model;
    }


    /**
     * 数据查询
     *
     * @param array $queryCond
     * @return array
     */
    public static function query($queryCond) {
        # check
        GlobalTool::checkException($queryCond['table']);

        # action
        // table
        $query = DB::table($queryCond['table']);

        // where
        if($queryCond['where'] && is_array($queryCond['where'])) {
            foreach($queryCond['where'] as $whereCond) {
                if(strtolower($whereCond[1]) == 'in') {
                    $query->whereIn($whereCond[0], array_values($whereCond[2]));
                } else {
                    $query->where($whereCond[0], $whereCond[1], $whereCond[2]);
                }
            }
        }

        // groupBy
        if($queryCond['groupBy'] && is_array($queryCond['groupBy'])) {
            foreach($queryCond['groupBy'] as $groupBy) {
                $query->groupBy($groupBy);
                $select .= $groupBy . ', ';
            }
        }

        // orderBy
        if($queryCond['orderBy'] && is_array($queryCond['orderBy'])) {
            foreach($queryCond['orderBy'] as $orderBy) {
                $query->orderBy($orderBy[0], $orderBy[1]);
            }
        }

        // select
        $queryCond['select'] = $queryCond['select'] ?: '*';
        $select = $select . $queryCond['select'];
        $query->select(DB::raw($select));

        # return
        return $query->get();
    }


    /**
     * 存在则返回ID, 否则新建
     *
     * @param $modelName
     * @param $field
     * @param $uniqueKey
     * @return object
     */
    public static function saveOnField($modelName, $field, $uniqueKey) {
        $uniqueField = [];
        foreach($uniqueKey as $k) {
            $uniqueField[$k] = $field[$k];
        }
        $model = $modelName::updateOrCreate($uniqueField, $field);

        return $model;
    }
}