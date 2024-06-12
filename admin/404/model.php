<?php
Class Log404 extends \SkillDo\Model\Model {

    static string $table = 'log404';

    static public function insert($insertData = [], object|null $oldObject = null): int|SKD_Error
    {
        $update = false;

        if (!empty($insertData['id'])) {

            $id = (int)$insertData['id'];

            $update = true;

            $oldObject = ($oldObject == null || !have_posts($oldObject)) ? static::get(Qr::set($id)) : $oldObject;

            if (!$oldObject) return new SKD_Error('invalid_id', __('ID không chính xác.'));
        }

        $columnsTable = [
            'path' => ['string'],
            'to' => ['string'],
            'type' => ['string', '301'],
            'redirect' => ['string'],
            'ip' => ['string'],
            'hit' => ['int', 0],
        ];

        $columnsTable = apply_filters('columns_db_' . self::$table, $columnsTable);

        $data = DataInsert::process()->beforeInsert($columnsTable, $insertData, $update);

        $data = apply_filters('pre_insert_' . self::$table . '_data', $data, $insertData, $oldObject);

        $model = model(self::$table);

        if ($update) {

            $data['updated'] = gmdate('Y-m-d H:i:s', time() + 7 * 3600);

            $model->update($data, Qr::set($id));

        }
        else {

            $data['created'] = gmdate('Y-m-d H:i:s', time() + 7 * 3600);

            $id = $model->add($data);
        }

        return $id;
    }

    static public function delete($redirectID = 0): array|bool
    {
        $redirectID = (int)Str::clear($redirectID);

        if($redirectID == 0) return false;

        $model = model(self::$table);

        do_action('delete_log404', $redirectID);

        if($model->delete(Qr::set('id', $redirectID))) {

            do_action('delete_log404_success', $redirectID);

            return [$redirectID];
        }

        return false;
    }

    static public function deleteList($redirectID = [])   {
        if(have_posts($redirectID)) {
            if(model(self::$table)::delete(Qr::set()->whereIn('id', $redirectID))) {
                do_action('delete_log404_list_trash_success', $redirectID );
                return $redirectID;
            }
        }
        return false;
    }
}