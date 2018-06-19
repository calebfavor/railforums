<?php

namespace Railroad\Railforums\Repositories\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait SoftDelete
{
    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        return $this->newQuery()
                    ->where('id', $id)
                    ->update([
                        'deleted_at' => Carbon::now()->toDateTimeString()
                    ]);
    }
}
